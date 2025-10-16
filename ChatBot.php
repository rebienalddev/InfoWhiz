<?php
// InfoWhiz: School Bot 🤖
// Combined PHP + HTML file with PDF upload capability

$apiKey = "AIzaSyBMqLHAjRLoYZK1vmYqNAOfe4A8uf_Z3-8";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent";

// Store chat history in session
session_start();
if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}
// Handle PDF removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_pdf'])) {
    if (isset($_SESSION['current_pdf'])) {
        // Delete the actual file from server
        if (file_exists($_SESSION['current_pdf'])) {
            unlink($_SESSION['current_pdf']);
        }
        // Remove from session
        unset($_SESSION['current_pdf']);
        echo json_encode(['success' => true, 'message' => 'PDF removed successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No PDF to remove.']);
    }
    exit;
}
// Handle reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $_SESSION['chat'] = [];
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle PDF upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_upload'])) {
    $uploadDir = "uploads/";
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['pdf_upload']['name']);
    $filePath = $uploadDir . $fileName;
    
    // Check if file is a PDF
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($fileType !== 'pdf') {
        echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['pdf_upload']['tmp_name'], $filePath)) {
        $_SESSION['current_pdf'] = $filePath;
        echo json_encode(['success' => true, 'message' => 'PDF uploaded successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload PDF.']);
    }
    exit;
}

// Handle AJAX requests for chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    $userInput = trim($_POST['prompt']);

    // Add user message to session
    $_SESSION['chat'][] = ['role' => 'user', 'text' => htmlspecialchars($userInput)];

    // Prepare data for Gemini API
    $data = [
        "contents" => [
            [
                "parts" => []
            ]
        ]
    ];

    // Check if there's an uploaded PDF to include
    if (isset($_SESSION['current_pdf']) && file_exists($_SESSION['current_pdf'])) {
        $pdfData = base64_encode(file_get_contents($_SESSION['current_pdf']));
        
        $data["contents"][0]["parts"] = [
            ["text" => $userInput],
            [
                "inline_data" => [
                    "mime_type" => "application/pdf",
                    "data" => $pdfData
                ]
            ]
        ];
    } else {
        $data["contents"][0]["parts"] = [
            ["text" => $userInput]
        ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url?key=$apiKey");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $botResponse = "⚠️ Error connecting to Gemini API: " . $error;
    } else {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $botResponse = $result['candidates'][0]['content']['parts'][0]['text'];
            $botResponse = preg_replace('/\*\*(.*?)\*\*/', '$1', $botResponse);
        } else {
            $botResponse = "⚠️ Error: Unable to get response from AI. API returned: " . $httpCode;
            if (isset($result['error']['message'])) {
                $botResponse .= " - " . $result['error']['message'];
            }
        }
    }

    $_SESSION['chat'][] = ['role' => 'bot', 'text' => $botResponse];

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'response' => $botResponse]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfoWhiz | AI School Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Styles/ChatBot.css?v=3">
</head>
<body>
    <link rel="stylesheet" href="../Styles/ChatBot.css?v=3">
    <header class="header">
        <div class="logo">
            <div class="logo-icon">IW</div>
            <div class="logo-text">InfoWhiz</div>
        </div>
        <div class="header-right">
            <div class="status">
                <div class="status-dot"></div>
                <span>AI Assistant Online</span>
            </div>
            <button class="reset-button" id="resetButton">
                <span>Reset Chat</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 12C3 7.03 7.03 3 12 3C16.97 3 21 7.03 21 12C21 16.97 16.97 21 12 21" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <path d="M3 12L6 9M3 12L6 15M3 12H7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <div class="nav-item active">Chat Bot</div>
            <div class="nav-item" onclick="Home()">Home</div>
            
            <!-- PDF Upload Section -->
            <div class="pdf-upload-section">
                <h3>Upload PDF</h3>
                <form id="pdfUploadForm" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" id="pdfFile" name="pdf_upload" accept=".pdf" class="file-input">
                        <label for="pdfFile" class="file-input-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Choose PDF File
                        </label>
                    </div>
                    <button type="submit" class="upload-button">Upload PDF</button>
                </form>
                <div id="uploadStatus"></div>
                <?php if (isset($_SESSION['current_pdf'])): ?>
                    <div class="current-pdf">
                        <strong>Current PDF:</strong> 
                        <?php echo basename($_SESSION['current_pdf']); ?>
                        <button type="button" class="remove-pdf" onclick="removePDF()">×</button>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <div class="chat-container" id="chatContainer">
                <div class="welcome-message" id="welcomeMessage">
                    <div class="welcome-icon">🤖</div>
                    <h2>Hello! I'm InfoWhiz, your AI School Assistant</h2>
                    <p>I am an A.I assistant powered by Gemini, integrated by Carpio Rebienald and Maglaqui Nicole.</p>
                    
                    <?php if (isset($_SESSION['current_pdf'])): ?>
                        <div class="pdf-notice">
                            📚 Currently analyzing: <strong><?php echo basename($_SESSION['current_pdf']); ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="suggestion-chips">
                        <div class="chip" onclick="insertSuggestion('Summarize this document')">Summarize</div>
                        <div class="chip" onclick="insertSuggestion('Explain the main concepts')">Explain</div>
                        <div class="chip" onclick="insertSuggestion('What are the key points?')">Key Points</div>
                        <div class="chip" onclick="insertSuggestion('Help me understand this document')">Help Me Understand</div>
                    </div>
                </div>

                <?php if (!empty($_SESSION['chat'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const welcomeMessage = document.getElementById('welcomeMessage');
                            if (welcomeMessage) {
                                welcomeMessage.style.display = 'none';
                            }
                        });
                    </script>
                    <?php foreach ($_SESSION['chat'] as $msg): ?>
                        <div class="message <?php echo $msg['role'] === 'user' ? 'user-message' : 'bot-message'; ?>">
                            <?php echo $msg['text']; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>

            <form class="input-container" id="chatForm" method="POST">
                <textarea class="input-field" id="messageInput" name="prompt" placeholder="Type your question or topic..." rows="1" required></textarea>
                <button type="submit" class="send-button" id="sendButton">
                    <span>Send</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </main>
    </div>

    <script src="../Scripts/ChatBot.js?v=2"></script>
     

</body>

</html>