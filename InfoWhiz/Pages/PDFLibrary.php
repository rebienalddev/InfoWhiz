<?php
// PDFLibrary.php

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STRICT SESSION VALIDATION - Prevents URL access without login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Additional security checks
$timeout = 30 * 60;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: Login.php?error=session_expired');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Use ../ to go up one directory from 'Pages/'
$uploadDir = "../uploads/";

// --- Handle Delete Request (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    header('Content-Type: application/json');
    $fileName = $_POST['delete_file'];
    
    $fileBaseName = basename($fileName);
    $filePath = $uploadDir . $fileBaseName;

    if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadDir)) === 0) {
        if (unlink($filePath)) {
            echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not delete file. Check permissions.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found or invalid path.']);
    }
    exit;
}

// --- Load Files for Display ---
$files = [];
if (is_dir($uploadDir)) {
    $files = glob($uploadDir . "*.pdf");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Library | InfoWhiz</title>
    <link rel="stylesheet" href="../Styles/HomePage.css?v=3">
    <link rel="stylesheet" href="../Styles/PDFLibrary.css?v=6">
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
            
            <div class="nav-item" onclick="HomePage()">Home Page</div>
            <div class="nav-item" onclick="GameLibrary()">Game Library</div>
            <div class="nav-item" onclick="ChatBot()">Chat Bot</div>
            <div class="nav-item" onclick="PDFLibrary()">PDF Library</div>


            <!-- Logout Section -->
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
            <div class="pdf-library-content">
                <header class="library-header">
                    <h1>📚 PDF Library</h1>
                    <p>All your uploaded documents in one place</p>
                    <div class="file-count">
                        <?php echo count($files); ?> PDF<?php echo count($files) !== 1 ? 's' : '' ?> found
                    </div>
                </header>

                <div id="pdf-library-container">
                    <?php if (!is_dir($uploadDir)): ?>
                        <div class="library-message error">
                            <strong>⚠️ Error:</strong> Upload directory not found
                        </div>
                    <?php elseif (empty($files)): ?>
                        <div class="library-message empty">
                            <div class="empty-icon">📄</div>
                            <h3>No PDFs Yet</h3>
                            <p>Upload some PDF files to get started</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <?php
                            $fullFileName = basename($file);
                            preg_match('/^[^_]+_(.*)$/', $fullFileName, $matches);
                            $displayName = $matches[1] ?? $fullFileName;
                            $fileSize = filesize($file);
                            $fileSizeFormatted = $fileSize > 1024 * 1024 
                                ? round($fileSize / (1024 * 1024), 1) . ' MB' 
                                : round($fileSize / 1024, 1) . ' KB';
                            ?>
                            <div class="pdf-item" data-filename="<?php echo htmlspecialchars($fullFileName); ?>">
                                <div class="pdf-icon">📄</div>
                                <div class="pdf-info">
                                    <span class="pdf-name" title="<?php echo htmlspecialchars($fullFileName); ?>">
                                        <?php echo htmlspecialchars($displayName); ?>
                                    </span>
                                    <span class="pdf-size"><?php echo $fileSizeFormatted; ?></span>
                                </div>
                                <div class="pdf-actions">
                                    <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" class="pdf-action view">
                                        View
                                    </a>
                                    <button class="pdf-action delete">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Scripts/HomePage.js?v=5"></script>
    <script src="../Scripts/PDFLibrary.js?v=3"></script>
</body>
</html>