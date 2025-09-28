<?php
include '../Functions/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['username' => $username]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['grade_level'] = $user['grade_level'];
                    $_SESSION['strand'] = $user['strand'];
                    
                    header('Location: ../Pages/index.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'No account found with that username.';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="../Styles/Login.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST" action="">
            <h2>User Login </h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
            
            <div class="register-link">
                <p>Don't have an account? <a href="../Pages/Registration.php">Register here</a></p>
            </div>
        </form>
    </div>
</body>
</html>