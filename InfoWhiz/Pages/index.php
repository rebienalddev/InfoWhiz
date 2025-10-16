<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfoWhiz - Learn Smarter</title>
    <link rel="stylesheet" href="../Styles/Index.css?v=">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    
    <!-- Overlay for mobile menu -->
    <div class="overlay" id="overlay"></div>
    
    <div class="container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="sidebar">
            <div class="logo">
                <h1>InfoWhiz</h1>
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
            <div class="nav-item" onclick="PDFUpload()">Chat Bot</div>
            <div class="nav-item" onclick="Progress()">Progress Page</div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="hero">
                <h1>Welcome to InfoWhiz!</h1>
                <p>Your comprehensive platform for interactive learning. Track progress, access resources, and master new skills through engaging games.</p>

                <p>Technology has transformed education by introducing interactive methods like game-based learning, which boosts engagement, motivation, and problem-solving skills. To support this, InfoWhiz was developed for SHS students at STI College Bacoor, offering subject-based games that cater to diverse learning styles, foster collaboration, and make studying both effective and enjoyable.</p>

                <button class="cta-button" onclick="PDFUpload()">Get Started</button>
            </div>
     
    <script src="../Scripts/Index.js?"></script>
</body>
</html>