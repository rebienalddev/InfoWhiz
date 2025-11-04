<?php
// Start session only if not already started
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
    header('Location: Login.php?error=session_expired');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfoWhiz - Learn Smarter</title>
    <link rel="stylesheet" href="../Styles/HomePage.css?v=3">
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
            <div class="hero">
                <h1>Welcome to InfoWhiz, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                
        
                
                <p>Your comprehensive platform for interactive learning. Track progress, access resources, and master new skills through engaging games.</p>

                <p>Technology has transformed education by introducing interactive methods like game-based learning, which boosts engagement, motivation, and problem-solving skills. To support this, InfoWhiz was developed for SHS students at STI College Bacoor, offering subject-based games that cater to diverse learning styles, foster collaboration, and make studying both effective and enjoyable.</p>

                <button class="cta-button" onclick="ChatBot2()">Get Started</button>
            </div>
        </main>
    </div>
    
    <script src="../Scripts/HomePage.js?v=3"></script>
</body>
</html>