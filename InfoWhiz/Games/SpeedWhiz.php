<?php
// InfoWhiz: SpeedWhiz Game ⚡
// ----- HOMEPAGE SESSION VALIDATION (MERGED) -----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STRICT SESSION VALIDATION - Prevents URL access without login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing session data
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: index.php');
    exit;
}

// Additional security checks
$timeout = 30 * 60; // 30 minutes timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: index.php?error=session_expired');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// ----- GAME API & FILE LOGIC (MERGED) -----
$apiKey = "AIzaSyBMqLHAjRLoYZK1vmYqNAOfe4A8uf_Z3-8";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_pdf'])) {
    if (isset($_SESSION['current_pdf'])) {
        if (file_exists($_SESSION['current_pdf'])) {
            unlink($_SESSION['current_pdf']);
        }
        unset($_SESSION['current_pdf']);
        echo json_encode(['success' => true, 'message' => 'PDF removed successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No PDF to remove.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_upload'])) {
    $uploadDir = "../uploads/";
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['pdf_upload']['name']);
    $filePath = $uploadDir . $fileName;
    
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($fileType !== 'pdf') {
        echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed.']);
        exit;
    }
    
    if (move_uploaded_file($_FILES['pdf_upload']['tmp_name'], $filePath)) {
        $_SESSION['current_pdf'] = $filePath;
        echo json_encode(['success' => true, 'message' => 'PDF uploaded successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload PDF.']);
    }
    exit;
}

// --- SPEEDWHIZ GAME DATA LOGIC (from your original file) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_game_data') {
    header('Content-Type: application/json');

    // 1. Check if PDF exists
    if (!isset($_SESSION['current_pdf']) || !file_exists($_SESSION['current_pdf'])) {
        echo json_encode(['success' => false, 'error' => 'Please upload a PDF from the sidebar to start the game.']);
        exit;
    }

    // 2. Get game settings
    $wordCount = isset($_POST['wordCount']) ? (int)$_POST['wordCount'] : 10;
    
    // 3. Create the prompt for the AI
    $userInput = "First, detect the primary language of the attached PDF (e.g., English or Tagalog).
    Second, extract $wordCount of the *most important* keywords or terms from the document.
    Third, provide a short definition for each term.
    
    The words and definitions MUST be in the same language as the PDF.
    
    Your response MUST be only a valid JSON array of objects. 
    Each object must have a 'word' key and a 'definition' key. 
    Do not include any other text, explanation, or markdown formatting like ```json.
    
    Example (if English):
    [{\"word\": \"Photosynthesis\", \"definition\": \"Process plants use to convert light energy into chemical energy.\"}]
    
    Example (if Tagalog):
    [{\"word\": \"Balarila\", \"definition\": \"Ang pag-aaral ng istruktura at mga alituntunin ng isang wika.\"}]";

    // 4. Prepare data for Gemini API
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $userInput],
                    [
                        "inline_data" => [
                            "mime_type" => "application/pdf",
                            "data" => base64_encode(file_get_contents($_SESSION['current_pdf']))
                        ]
                    ]
                ]
            ]
        ]
    ];

    // 5. cURL request
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

    // 6. Process the response
    if ($response === false) {
        echo json_encode(['success' => false, 'error' => "Error connecting to Gemini API: " . $error]);
        exit;
    }

    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $botResponse = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // --- START: **FIXED** JSON PARSING ---
        
        // 7. Find the start '[' and end ']' of the JSON array
        $jsonStart = strpos($botResponse, '[');
        $jsonEnd = strrpos($botResponse, ']');
        
        $gameData = null;
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            // Extract the JSON string
            $jsonString = substr($botResponse, $jsonStart, ($jsonEnd - $jsonStart) + 1);
            
            // 8. Try to decode the extracted JSON
            $gameData = json_decode($jsonString, true);
        }
        
        // --- END: **FIXED** JSON PARSING ---

        if (is_array($gameData) && !empty($gameData)) {
            // Success! Send the word list to the game
            echo json_encode(['success' => true, 'words' => $gameData]);
        } else {
            // AI response was not valid JSON
            echo json_encode(['success' => false, 'error' => 'The AI failed to return valid game data. It might be busy or the PDF content is not suitable. Please try again.']);
        }
    } else {
        // API error
        $botResponse = "Error: Unable to get response from AI. API returned: " . $httpCode;
        if (isset($result['error']['message'])) {
            $botResponse .= " - " . $result['error']['message'];
        }
        echo json_encode(['success' => false, 'error' => $botResponse]);
    }
    exit;
}

// Function to clean response (from your file)
function cleanAndFormatResponse($text) {
    $text = strip_tags($text);
    $text = preg_replace('/(\n\s*){2,}/', "\n\n", $text);
    $text = html_entity_decode($text);
    $text = preg_replace('/\n\s*([•\-]|\d+\.)\s*/', "\n• ", $text);
    $text = trim($text);
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfoWhiz | SpeedWhiz Game</title>
    
    <link href="[https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap](https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap)" rel="stylesheet">
    <link rel="stylesheet" href="../Styles/HomePage.css?v=3">
    <link rel="stylesheet" href="../GameStyles/SpeedWhiz.css?v=1">

</head>

<body>
    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    
    <div class="overlay" id="overlay"></div>

    <div class="container">
        
        <nav class="sidebar" id="sidebar">
            <div class="logo">
                <h1>InfoWhiz</h1>
                <div class="user-welcome">
                    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </div>
            </div>
            
            <div class="nav-item nav-parent" onclick="return toggleSubNav(this, event);">
                <span>Game Library</span>
            </div>
            <div class="sub-nav">
                <div class="sub-nav-item" onclick="CheezeWhiz()">CheezeWhiz</div>
                <div class="sub-nav-item" onclick="HangWhiz()">HangWhiz</div>
                <div class="sub-nav-item" onclick="SpeedWhiz()">SpeedWhiz</div>
            </div>
            
            <div class="nav-item" onclick="User()">User Profile</div>
            <div class="nav-item" onclick="ChatBot()">Chat Bot</div> 
            <div class="nav-item" onclick="Progress()">Progress Page</div>
            <div class="nav-item" onclick="PDFLibrary()">PDF Library</div>

            <div class="pdf-upload-section" style="padding: 1.5rem; margin-top: 1rem; border-top: 1px solid #e0e0e0;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #333; margin-bottom: 1rem;">Upload PDF</h3>
                <form id="pdfUploadForm" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" id="pdfFile" name="pdf_upload" accept=".pdf" class="file-input" style="opacity: 0; position: absolute; width: 0.1px; height: 0.1px;">
                        <label for="pdfFile" class="file-input-label" style="display: block; padding: 10px; background: #f4f4f4; border: 2px dashed #ccc; border-radius: 8px; cursor: pointer; text-align: center; font-weight: 500; color: #555;">
                            Choose PDF File
                        </label>
                    </div>
                    <button type="submit" class="upload-button" style="width: 100%; padding: 10px; background: #1976d2; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; margin-top: 10px;">Upload PDF</button>
                </form>
                <div id="uploadStatus" style="margin-top: 10px; font-size: 0.875rem;"></div>
                <?php if (isset($_SESSION['current_pdf'])): ?>
                    <div class="current-pdf" style="margin-top: 10px; padding: 10px; background: #e8f0fe; border-radius: 8px; font-size: 0.875rem; position: relative; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <strong>Current:</strong> 
                        <?php echo htmlspecialchars(basename($_SESSION['current_pdf'])); ?>
                        <button type="button" class="remove-pdf" onclick="removePDF()" style="background: none; border: none; color: #c00; font-size: 1.2rem; cursor: pointer; position: absolute; top: 5px; right: 5px;">×</button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="logout-section">
                <form method="POST" action="../Functions/logout.php" class="logout-form">
                    <button type="submit" class="btn-logout">
                        <span class="logout-icon">🚪</span>
                        Logout
                    </button>
                </form>
            </div>
        </nav>

        <main class="main-content">
            <div class="game-container">
                
                <div id="game-setup" class="game-screen active">
                    <h1>⚡ SpeedWhiz</h1>
                    <p>Test your typing speed with key terms from your PDF.</p>
                    
                    <?php if (isset($_SESSION['current_pdf'])): ?>
                        <form id="game-setup-form">
                            <div class="form-group">
                                <label for="wordCount">Number of Words:</label>
                                <input type="number" id="wordCount" name="wordCount" min="5" max="50" value="10">
                            </div>
                            <div class="form-group">
                                <label for="timePerWord">Seconds per Word:</label>
                                <input type="number" id="timePerWord" name="timePerWord" min="5" max="60" value="15">
                            </div>
                            <button type="submit" class="game-button" id="start-game-btn">Start Game</button>
                            <div class="game-loader" id="game-loader"></div>
                            <div class="game-error" id="game-setup-error"></div>
                        </form>
                    <?php else: ?>
                        <div class="game-error" style="display: block; margin-top: 20px;">
                            Please upload a PDF document using the sidebar to begin.
                        </div>
                    <?php endif; ?>
                </div>

                <div id="game-play" class="game-screen">
                    <div class="game-header">
                        <div class="game-stat">
                            <span>Time Left</span>
                            <div id="timer-display">15</div>
                        </div>
                        <div class="game-stat">
                            <span>Score</span>
                            <div id="score-display">0</div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="game-definition" id="definition-display">
                            This is the definition of the word you need to type.
                        </div>
                        <div class="game-word" id="word-display">
                            WordToType
                        </div>
                    </div>

                    <input type="text" id="typing-input" class="game-input" placeholder="Type the un-jumbled word..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                </div>

                <div id="game-over" class="game-screen">
                    <h2>Game Over!</h2>
                    <p>Your final score is:</p>
                    <div id="final-score-display" class="final-score">0 / 0</div>
                    <button id="play-again-btn" class="game-button">Play Again</button>
                </div>

            </div>
        </main>
    </div>

    <script src="../Scripts/HomePage.js?v=2"></script>
    <script src="../GameScripts/SpeedWhiz.js?v=2"></script>
</body>
</html>