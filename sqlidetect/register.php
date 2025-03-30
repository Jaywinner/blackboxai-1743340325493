<?php
require_once('config/globals.php');
require_once('config/database.php');

// Check if installed
if(!file_exists('config/installed.flag')) {
    header('Location: installation/install.php');
    exit;
}

// Database connection
try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_password
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Process registration
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $errors = [];
    if(empty($_POST['first_name'])) $errors[] = "First name is required";
    if(empty($_POST['last_name'])) $errors[] = "Last name is required";
    if(empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if(empty($_POST['username'])) $errors[] = "Username is required";
    if(empty($_POST['password']) || strlen($_POST['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if($_POST['password'] !== $_POST['password_confirm']) {
        $errors[] = "Passwords do not match";
    }

    if(empty($errors)) {
        try {
            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM USERS WHERE user_name = ?");
            $stmt->execute([$_POST['username']]);
            if($stmt->rowCount() > 0) {
                $errors[] = "Username already exists";
            } else {
                // Create admin user
                $stmt = $db->prepare("
                    INSERT INTO USERS 
                    (first_name, last_name, user_name, pass_word, email) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['username'],
                    $hashed_password,
                    $_POST['email']
                ]);
                
                // Redirect to login
                header('Location: login.php');
                exit;
            }
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - Admin Registration</title>
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <h1>Create Admin Account</h1>
        
        <?php if(!empty($errors)): ?>
        <div class="error-message">
            <?php foreach($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="password_confirm" required>
            </div>
            
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>