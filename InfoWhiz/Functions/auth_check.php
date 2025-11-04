<?php
function checkAuth() {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../Pages/Login.php');
        exit;
    }
    
    // Validate session consistency
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        header('Location: ../Pages/Login.php?error=session_invalid');
        exit;
    }
    
    // Optional: Session timeout (30 minutes)
    $timeout = 30 * 60; // 30 minutes in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        session_destroy();
        header('Location: ../Pages/Login.php?error=session_expired');
        exit;
    }
    
    // Update login time for active session
    $_SESSION['login_time'] = time();
    
    return true;
}

function requireAuth() {
    return checkAuth();
}
?>