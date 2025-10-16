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
    <link rel="stylesheet" href="../Styles/ChatBot.css?v=2">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            const chatContainer = document.getElementById('chatContainer');
            const typingIndicator = document.getElementById('typingIndicator');
            const sendButton = document.getElementById('sendButton');
            const resetButton = document.getElementById('resetButton');
            const welcomeMessage = document.getElementById('welcomeMessage');
            const pdfUploadForm = document.getElementById('pdfUploadForm');
            const uploadStatus = document.getElementById('uploadStatus');
            
            // Auto-resize textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Handle PDF upload
            pdfUploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const fileInput = document.getElementById('pdfFile');
                if (!fileInput.files[0]) {
                    showUploadStatus('Please select a PDF file.', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('pdf_upload', fileInput.files[0]);
                
                showUploadStatus('Uploading PDF...', 'loading');
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showUploadStatus(data.message, 'success');
                        fileInput.value = '';
                        // Reload to show the current PDF
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showUploadStatus(data.error, 'error');
                    }
                } catch (error) {
                    showUploadStatus('Upload failed. Please try again.', 'error');
                    console.error('Upload error:', error);
                }
            });
            
            function showUploadStatus(message, type) {
                uploadStatus.textContent = message;
                uploadStatus.className = 'upload-status ' + type;
            }
            
            // Handle reset button
            resetButton.addEventListener('click', async function() {
                if (confirm('Are you sure you want to reset the chat? This will clear all messages.')) {
                    try {
                        const formData = new FormData();
                        formData.append('reset', 'true');
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Clear all messages
                            const messages = chatContainer.querySelectorAll('.message');
                            messages.forEach(msg => msg.remove());
                            
                            // Show welcome message again
                            if (welcomeMessage) {
                                welcomeMessage.style.display = 'block';
                            }
                        }
                    } catch (error) {
                        console.error('Error resetting chat:', error);
                        alert('Failed to reset chat. Please try again.');
                    }
                }
            });
            
            // Handle form submission with AJAX
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message) return;
                
                // Add user message to chat immediately
                addMessage(message, 'user');
                
                // Clear input and reset height
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Disable send button and show typing indicator
                sendButton.disabled = true;
                typingIndicator.style.display = 'block';
                scrollToBottom();
                
                try {
                    // Send message to Gemini AI via PHP backend
                    const formData = new FormData();
                    formData.append('prompt', message);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Hide typing indicator and add response
                        typingIndicator.style.display = 'none';
                        addMessage(data.response, 'bot');
                    } else {
                        throw new Error('API returned error');
                    }
                } catch (error) {
                    // Handle errors
                    typingIndicator.style.display = 'none';
                    addMessage("Sorry, I encountered an error. Please try again.", 'bot');
                    console.error('Error:', error);
                } finally {
                    // Re-enable send button
                    sendButton.disabled = false;
                    messageInput.focus();
                }
            });
            
            // Add message to chat
            function addMessage(text, sender) {
                // Remove welcome message if it's the first real message
                if (welcomeMessage && welcomeMessage.style.display !== 'none') {
                    welcomeMessage.style.display = 'none';
                }
                
                const messageElement = document.createElement('div');
                messageElement.classList.add('message');
                messageElement.classList.add(sender === 'user' ? 'user-message' : 'bot-message');
                messageElement.textContent = text;
                
                chatContainer.appendChild(messageElement);
                scrollToBottom();
            }
            
            // Scroll to bottom of chat
            function scrollToBottom() {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            // Insert suggestion into input field
            window.insertSuggestion = function(text) {
                messageInput.value = text;
                messageInput.focus();
                messageInput.style.height = 'auto';
                messageInput.style.height = (messageInput.scrollHeight) + 'px';
            };
            
            // Allow sending with Enter key (but allow Shift+Enter for new line)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    chatForm.dispatchEvent(new Event('submit'));
                }
            });
            
            // Focus input on load
            messageInput.focus();
        });

        function removePDF() {
            if (confirm('Remove the current PDF?')) {
                // You can implement PDF removal logic here
                // For now, just reload the page which will clear the session PDF
                window.location.href = '?remove_pdf=true';
            }
        }

        function Home() {
            window.location.href = '../Pages/index.php';
        }

        // Mobile menu functionality
        function initMobileMenu() {
            // Create mobile menu button
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.className = 'mobile-menu-btn';
            mobileMenuBtn.innerHTML = `
                <span></span>
                <span></span>
                <span></span>
            `;
            
            // Insert mobile menu button at the beginning of logo
            const logo = document.querySelector('.logo');
            logo.insertBefore(mobileMenuBtn, logo.firstChild);
            
            // Create sidebar overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            const sidebar = document.querySelector('.sidebar');
            
            function toggleMobileMenu() {
                mobileMenuBtn.classList.toggle('active');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }
            
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
            overlay.addEventListener('click', toggleMobileMenu);
            
            // Close mobile menu when clicking on nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        toggleMobileMenu();
                    }
                });
            });
            
            // Close menu on window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
                    mobileMenuBtn.classList.remove('active');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Initialize mobile menu when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initMobileMenu();
        });
    </script>
    <STYLE>
        /* White and Blue Professional Theme for InfoWhiz */
:root {
    --primary-blue: #2563eb;
    --primary-blue-dark: #1d4ed8;
    --primary-blue-light: #3b82f6;
    --secondary-blue: #1e40af;
    --accent-blue: #60a5fa;
    --white: #ffffff;
    --off-white: #f8fafc;
    --light-gray: #f1f5f9;
    --medium-gray: #e2e8f0;
    --dark-gray: #64748b;
    --text-dark: #1e293b;
    --text-light: #475569;
    --success: #10b981;
    --warning: #f59e0b;
    --error: #ef4444;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    background: linear-gradient(135deg, var(--off-white) 0%, var(--white) 100%);
    color: var(--text-dark);
    line-height: 1.6;
    min-height: 100vh;
}

/* Header Styles */
.header {
    background: var(--white);
    border-bottom: 1px solid var(--medium-gray);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: var(--shadow-md);
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-light);
}

.status-dot {
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.reset-button {
    background: var(--primary-blue);
    color: var(--white);
    border: none;
    padding: 0.75rem 1.25rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.reset-button:hover {
    background: var(--primary-blue-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

/* Container Layout */
.container {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 80px);
}

/* Sidebar Styles */
.sidebar {
    width: 300px;
    background: var(--white);
    border-right: 1px solid var(--medium-gray);
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    box-shadow: var(--shadow-sm);
}

.nav-item {
    padding: 1rem 1.25rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    color: var(--text-light);
    cursor: pointer;
    transition: var(--transition);
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: var(--light-gray);
    color: var(--primary-blue);
}

.nav-item.active {
    background: var(--light-gray);
    color: var(--primary-blue);
    border-left-color: var(--primary-blue);
    font-weight: 600;
}

/* PDF Upload Section */
.pdf-upload-section {
    background: var(--off-white);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--medium-gray);
}

.pdf-upload-section h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.file-input-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.file-input-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: var(--white);
    border: 2px dashed var(--medium-gray);
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
    color: var(--text-light);
    justify-content: center;
}

.file-input-label:hover {
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.upload-button {
    width: 100%;
    background: var(--primary-blue);
    color: var(--white);
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.upload-button:hover {
    background: var(--primary-blue-dark);
    transform: translateY(-1px);
}

.upload-status {
    margin-top: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
}

.upload-status.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.upload-status.error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--error);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.upload-status.loading {
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary-blue);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.current-pdf {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--white);
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--medium-gray);
    font-size: 0.875rem;
    display: flex;
    justify-content: between;
    align-items: center;
}

.current-pdf strong {
    color: var(--text-dark);
    margin-right: 0.5rem;
}

.remove-pdf {
    background: none;
    border: none;
    color: var(--error);
    font-size: 1.25rem;
    cursor: pointer;
    margin-left: auto;
    padding: 0.25rem;
    border-radius: 4px;
    transition: var(--transition);
}

.remove-pdf:hover {
    background: rgba(239, 68, 68, 0.1);
}

/* Main Content Area */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 2rem;
    gap: 1.5rem;
}

.chat-container {
    flex: 1;
    background: var(--white);
    border-radius: var(--border-radius);
    border: 1px solid var(--medium-gray);
    padding: 2rem;
    overflow-y: auto;
    max-height: calc(100vh - 300px);
    box-shadow: var(--shadow-sm);
}

/* Welcome Message */
.welcome-message {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-dark);
}

.welcome-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
}

.welcome-message h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-message p {
    font-size: 1.125rem;
    color: var(--text-light);
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.pdf-notice {
    background: rgba(37, 99, 235, 0.1);
    border: 1px solid rgba(37, 99, 235, 0.2);
    color: var(--primary-blue);
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius-sm);
    margin: 2rem auto;
    max-width: 500px;
    font-weight: 500;
}

/* Suggestion Chips */
.suggestion-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
    margin-top: 2rem;
}

.chip {
    background: var(--white);
    border: 1px solid var(--medium-gray);
    color: var(--text-light);
    padding: 0.75rem 1.25rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.chip:hover {
    background: var(--primary-blue);
    color: var(--white);
    border-color: var(--primary-blue);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Message Styles */
.message {
    margin-bottom: 1.5rem;
    padding: 1.25rem 1.5rem;
    border-radius: var(--border-radius);
    line-height: 1.6;
    max-width: 80%;
    box-shadow: var(--shadow-sm);
    animation: messageSlide 0.3s ease-out;
}

@keyframes messageSlide {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-message {
    background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
    color: var(--white);
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.bot-message {
    background: var(--off-white);
    color: var(--text-dark);
    border: 1px solid var(--medium-gray);
    margin-right: auto;
    border-bottom-left-radius: 4px;
}

/* Typing Indicator */
.typing-indicator {
    display: none;
    padding: 1rem 1.5rem;
    background: var(--off-white);
    border-radius: var(--border-radius);
    border: 1px solid var(--medium-gray);
    margin-bottom: 1.5rem;
    max-width: 120px;
}

.typing-dots {
    display: flex;
    gap: 0.5rem;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: var(--primary-blue);
    border-radius: 50%;
    animation: typingBounce 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

/* Input Container */
.input-container {
    background: var(--white);
    border: 1px solid var(--medium-gray);
    border-radius: var(--border-radius);
    padding: 1.25rem;
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    box-shadow: var(--shadow-md);
}

.input-field {
    flex: 1;
    border: none;
    outline: none;
    resize: none;
    font-family: inherit;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--text-dark);
    background: transparent;
    max-height: 120px;
    min-height: 24px;
}

.input-field::placeholder {
    color: var(--dark-gray);
}

.send-button {
    background: var(--primary-blue);
    color: var(--white);
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 100px;
    justify-content: center;
}

.send-button:hover:not(:disabled) {
    background: var(--primary-blue-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.send-button:disabled {
    background: var(--dark-gray);
    cursor: not-allowed;
    transform: none;
}

/* Mobile Menu */
.mobile-menu-btn {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.mobile-menu-btn span {
    width: 20px;
    height: 2px;
    background: var(--text-dark);
    transition: var(--transition);
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 98;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header {
        padding: 1rem;
    }
    
    .logo-text {
        font-size: 1.25rem;
    }
    
    .status {
        display: none;
    }
    
    .container {
        flex-direction: column;
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        left: -100%;
        height: 100vh;
        width: 280px;
        z-index: 99;
        transition: var(--transition);
        box-shadow: var(--shadow-lg);
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .mobile-menu-btn {
        display: flex;
    }
    
    .mobile-menu-btn.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }
    
    .mobile-menu-btn.active span:nth-child(2) {
        opacity: 0;
    }
    
    .mobile-menu-btn.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    .main-content {
        padding: 1rem;
    }
    
    .chat-container {
        padding: 1.5rem;
        max-height: calc(100vh - 250px);
    }
    
    .message {
        max-width: 90%;
    }
    
    .welcome-message {
        padding: 2rem 1rem;
    }
    
    .welcome-message h2 {
        font-size: 1.5rem;
    }
    
    .suggestion-chips {
        gap: 0.5rem;
    }
    
    .chip {
        padding: 0.625rem 1rem;
        font-size: 0.8rem;
    }
    
    .input-container {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 0.75rem 1rem;
    }
    
    .logo-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .reset-button {
        padding: 0.625rem 1rem;
        font-size: 0.8rem;
    }
    
    .send-button {
        min-width: 80px;
        padding: 0.75rem 1rem;
    }
    
    .message {
        padding: 1rem;
        font-size: 0.9rem;
    }
}

/* Scrollbar Styling */
.chat-container::-webkit-scrollbar {
    width: 6px;
}

.chat-container::-webkit-scrollbar-track {
    background: var(--light-gray);
    border-radius: 3px;
}

.chat-container::-webkit-scrollbar-thumb {
    background: var(--medium-gray);
    border-radius: 3px;
}

.chat-container::-webkit-scrollbar-thumb:hover {
    background: var(--dark-gray);
}

/* Focus States for Accessibility */
button:focus-visible,
.file-input-label:focus-visible,
.input-field:focus-visible {
    outline: 2px solid var(--primary-blue);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .header,
    .sidebar,
    .input-container {
        display: none;
    }
    
    .chat-container {
        max-height: none;
        box-shadow: none;
        border: none;
    }
}
        </STYLE>

</body>

</html>