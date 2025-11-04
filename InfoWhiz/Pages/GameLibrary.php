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
    <title>Game Library</title>
    <link rel="stylesheet" href="../Styles/Games.css?v=5">
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
            <div class="hero"> <h1>Welcome to the Game Library</h1>
                 <p>Select a game below to play it:</p>
            </div>

            <div class="game-grid">
                <div class="game-card">
                    <a href="https://store.steampowered.com/app/570/Dota_2/" target="_blank" class="game-link">
                        <img src="https://scontent.fmnl30-1.fna.fbcdn.net/v/t1.15752-9/566466353_1184564000205097_3094656324810828270_n.jpg?_nc_cat=107&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeEVJ5q-02i7B3DmmdEYvMKvVSyDBpJe4OlVLIMGkl7g6bm0yV4AhX5j9SM-X91qunySUkPaWs2M-Gu4xVF3OEuG&_nc_ohc=WXh38XG_w_wQ7kNvwHFl9MN&_nc_oc=Adls-nB8GkUbXAAqIkUgCpwXoZu2y5WcwTYaYnCdg2zKPJJjj6fZh2FxdWk3bZcEmQs&_nc_zt=23&_nc_ht=scontent.fmnl30-1.fna&oh=03_Q7cD3gG53izfinYiB_o7AY0api8QX3Iyg8EfpCgzgxbW7AUL8g&oe=69282C1C" alt="Dota 2 Thumbnail" class="game-image">
                        <div class="game-content">
                            <div class="game-title">Dota 2</div>
                            <div class="game-description">A fast-paced multiplayer online battle arena (MOBA) game where two teams compete to destroy the opponent's base. Features deep strategy, heroes, and esports-level gameplay.</div>
                        </div>
                    </a>
                </div>
                <div class="game-card">
                    <a href="https://www.chess.com/play" target="_blank" class="game-link">
                        <img src="https://pampangasbest.store/cdn/shop/products/SARAP-HOTDOG-250G-2_503x503.jpg?v=1754549547" class="game-image">
                        <div class="game-content">
                            <div class="game-title">Chess (Online)</div>
                            <div class="game-description">The classic strategy board game played online. Challenge players worldwide, improve your skills with tutorials, and enjoy timed matches.</div>
                        </div>
                    </a>
                </div>
                <div class="game-card">
                    <a href="https://www.roblox.com/games" target="_blank" class="game-link">
                        <img src="https://scontent.fmnl30-3.fna.fbcdn.net/v/t1.15752-9/553748920_1536980377457002_1757514920177431586_n.jpg?_nc_cat=105&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGbq2yYydnDZtlviuqY2SXG812gznkRMt3zXaDOeREy3ZLRpvxmNtVGbjqcEVKiisV962te2H73zDgrlhmS2fWA&_nc_ohc=y3EuFAGOmIYQ7kNvwEWk0YV&_nc_oc=AdmD_UUFlY26EVWt8Q6kfWYbq6slZq49IFtAlt-3o32nVDWWcjz2XYAXAFOWnFDrW5g&_nc_zt=23&_nc_ht=scontent.fmnl30-3.fna&oh=03_Q7cD3gEqL4siN-t9KVody97HVl2z4_OoEgjkQwsjz9T5imcpMQ&oe=69281A0C" alt="Roblox Thumbnail" class="game-image">
                        <div class="game-content">
                            <div class="game-title">Roblox Games</div>
                            <div class="game-description">A platform with user-created games and experiences. Explore adventures, simulations, and mini-games in a vast, creative community.</div>
                        </div>
                    </a>
                </div>
                </div>
        </main> </div> <script src="../Scripts/HomePage.js?v=4"></script>
</body>
</html>