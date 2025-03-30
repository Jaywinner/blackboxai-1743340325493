<?php
session_start();
require_once('config/globals.php');
require_once('config/database.php');

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
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

// Process login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    if(empty($_POST['username']) || empty($_POST['password'])) {
        $errors[] = "Username and password are required";
    } else {
        try {
            $stmt = $db->prepare("
                SELECT id, user_name, pass_word 
                FROM USERS 
                WHERE user_name = ?
            ");
            $stmt->execute([$_POST['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && password_verify($_POST['password'], $user['pass_word'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['user_name'];
                
                // Update last login
                $stmt = $db->prepare("
                    UPDATE USERS 
                    SET last_login = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);
                
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $errors[] = "Invalid username or password";
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
    <title>SQLiDetect - Admin Login</title>
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <h1>Admin Login</h1>
        
        <?php if(!empty($errors)): ?>
        <div class="error-message">
            <?php foreach($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>