<?php
// InfoWhiz: School Bot 🤖
// Combined PHP + HTML file

$apiKey = "AIzaSyBMqLHAjRLoYZK1vmYqNAOfe4A8uf_Z3-8";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent";

// Store chat history in session
session_start();
if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}

// Handle reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $_SESSION['chat'] = [];
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    $userInput = trim($_POST['prompt']);
    
    // Add user message to session
    $_SESSION['chat'][] = ['role' => 'user', 'text' => htmlspecialchars($userInput)];
    
    // Prepare data for Gemini API
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $userInput]
                ]
            ]
        ]
    ];
    
    // cURL request with timeout for faster response
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
            // Remove ** markdown formatting
            $botResponse = preg_replace('/\*\*(.*?)\*\*/', '$1', $botResponse);
        } else {
            $botResponse = "⚠️ Error: Unable to get response from AI. API returned: " . $httpCode;
            if (isset($result['error']['message'])) {
                $botResponse .= " - " . $result['error']['message'];
            }
        }
    }
    
    // Add bot reply to session
    $_SESSION['chat'][] = ['role' => 'bot', 'text' => $botResponse];
    
    // Return JSON response for AJAX
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
  <link rel="stylesheet" href="../Styles/PDFUpload.css?v=">
</head>
<body>
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
            <div class="nav-item active"> Chat Bot </div>
               
            
            <div class="nav-item"  onclick="Home()"> Home </div>
           
        </aside>

        <main class="main-content">
            <div class="chat-container" id="chatContainer">
                <!-- Welcome message -->
                <div class="welcome-message" id="welcomeMessage">
                    <div class="welcome-icon">🤖</div>
                    <h2>Hello! I'm InfoWhiz, your AI School Assistant</h2>
                    <p>I am an A.I assistant powered by Gemini, integrated by Carpio Rebienald and Maglaqui Nicole.</p>
                    
                    <div class="suggestion-chips">
                        <div class="chip" onclick="insertSuggestion('Explain quantum physics basics')">Physics</div>
                        <div class="chip" onclick="insertSuggestion('Help with algebra problems')">Math</div>
                        <div class="chip" onclick="insertSuggestion('Explain photosynthesis')">Biology</div>
                        <div class="chip" onclick="insertSuggestion('Help me write an essay')">Writing</div>
                    </div>
                </div>
                
                <!-- Display chat history from PHP session -->
                <?php if (!empty($_SESSION['chat'])): ?>
                    <script>
                        // Hide welcome message if there's chat history
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

    <script src="../Scripts/PDFUpload.js"></script>
       

</body>
</html>