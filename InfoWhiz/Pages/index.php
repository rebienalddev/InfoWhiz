<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../Functions/config.php';

// Security headers to prevent various attacks
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Check if user is already logged in, redirect if true
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../Pages/HomePage.php');
    exit;
}

$error = '';

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Input validation
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } elseif (strlen($username) > 50 || strlen($password) > 255) {
            $error = 'Invalid input length.';
        } else {
            try {
                // Use prepared statements to prevent SQL injection
                $sql = "SELECT * FROM users WHERE username = :username";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['username' => $username]);
                
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch();
                    
                    // Verify password with timing attack protection
                    if (password_verify($password, $user['password'])) {
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['grade_level'] = $user['grade_level'];
                        $_SESSION['strand'] = $user['strand'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        
                        // Generate new CSRF token for next request
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        header('Location: ../Pages/HomePage.php');
                        exit;
                    } else {
                        // Use generic error message to prevent user enumeration
                        $error = 'Invalid username or password.';
                        // Add delay to prevent timing attacks
                        usleep(rand(100000, 300000));
                    }
                } else {
                    // Use generic error message to prevent user enumeration
                    $error = 'Invalid username or password.';
                    // Add delay to prevent timing attacks
                    usleep(rand(100000, 300000));
                }
            } catch(PDOException $e) {
                // Log the error but show generic message to user
                error_log("Login error: " . $e->getMessage());
                $error = 'System error. Please try again later.';
            }
        }
    }
}

// Generate new CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - InfoWhiz</title>
    <link rel="stylesheet" href="../Styles/index.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST" action="">
            <h2>Student Login</h2>
            
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required 
                       maxlength="50"
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" 
                       required 
                       maxlength="255"
                       autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login">Login</button>
            
            <div class="register-link">
                <p>Don't have an account? <a href="../Pages/Registration.php">Register here</a></p>
            </div>
        </form>
    </div>

    <script>
        // Client-side validation
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (username.length === 0 || password.length === 0) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return false;
            }
            
            if (username.length > 50) {
                e.preventDefault();
                alert('Username is too long.');
                return false;
            }
            
            return true;
        });

        // Clear error message when user starts typing
        document.getElementById('username').addEventListener('input', clearError);
        document.getElementById('password').addEventListener('input', clearError);
        
        function clearError() {
            const errorElement = document.querySelector('.error-message');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>