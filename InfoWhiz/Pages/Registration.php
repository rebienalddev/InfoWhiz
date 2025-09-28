<?php
include '../Functions/config.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = trim($_POST['student_id']);
    $grade_level = $_POST['grade_level'];
    $strand = trim($_POST['strand']);
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($student_id) || empty($grade_level) || empty($strand)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } else {
        try {
            // Check if user exists
            $sql = "SELECT * FROM users WHERE username = :username OR student_id = :student_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['username' => $username, 'student_id' => $student_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username or Student ID already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $sql = "INSERT INTO users (username, password, student_id, grade_level, strand) 
                        VALUES (:username, :password, :student_id, :grade_level, :strand)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([
                    'username' => $username,
                    'password' => $hashed_password,
                    'student_id' => $student_id,
                    'grade_level' => $grade_level,
                    'strand' => $strand
                ])) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
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
    <title>Student Registration</title>
    <link rel="stylesheet" href="../Styles/Registration.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" id="registerForm" method="POST" action="">
            <h2>User Registration</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required minlength="3">
                <div class="form-hint">Minimum 3 characters</div>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <div class="form-hint">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="student_id">Student ID:</label>
                <input type="text" id="student_id" name="student_id" 
                       value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="grade_level">Grade Level:</label>
                <select id="grade_level" name="grade_level" required>
                    <option value="">Select Grade Level</option>
                    <option value="11" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                    <option value="12" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="strand">Strand:</label>
                <select id="strand" name="strand" required>
                    <option value="">Select Strand</option>
                    <option value="STEM" <?php echo (isset($_POST['strand']) && $_POST['strand'] == 'STEM') ? 'selected' : ''; ?>>STEM</option>
                    <option value="HUMSS" <?php echo (isset($_POST['strand']) && $_POST['strand'] == 'HUMSS') ? 'selected' : ''; ?>>HUMSS</option>
                    <option value="ABM" <?php echo (isset($_POST['strand']) && $_POST['strand'] == 'ABM') ? 'selected' : ''; ?>>ABM</option>
                    <option value="TVL" <?php echo (isset($_POST['strand']) && $_POST['strand'] == 'TVL') ? 'selected' : ''; ?>>ICT</option>
                </select>
            </div>
            
            <button type="submit" class="btn-login">Create Account</button>
            
            <div class="register-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
    
    <script src="../Scripts/Registration.js"></script>
</body>
</html>