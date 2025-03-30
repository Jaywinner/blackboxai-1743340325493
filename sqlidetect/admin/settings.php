<?php
session_start();
require_once('../config/globals.php');
require_once('../config/database.php');

// Check authentication
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
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

// Handle settings update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE SETTING_VALUES 
            SET settings_value = ? 
            WHERE id = ?
        ");
        
        // Update temporary block count
        $stmt->execute([$_POST['temp_block_count'], 1]);
        // Update reset time
        $stmt->execute([$_POST['reset_time'], 2]);
        // Update permanent block count
        $stmt->execute([$_POST['perm_block_count'], 3]);

        $message = "Settings updated successfully.";
    } catch(PDOException $e) {
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

// Fetch current settings
$current_settings = [];
try {
    $stmt = $db->query("SELECT * FROM SETTING_VALUES");
    $current_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load settings: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - Settings</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>System Settings</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="attacks.php">Attack Log</a>
                <a href="blocked.php">Blocked IPs</a>
                <a href="settings.php" class="active">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>

        <main class="dashboard-content">
            <?php if(isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if(isset($message)): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Temporary Block After (Attempts):</label>
                    <input type="number" name="temp_block_count" value="<?= htmlspecialchars($current_settings[0]['settings_value']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Reset Time (Minutes):</label>
                    <input type="number" name="reset_time" value="<?= htmlspecialchars($current_settings[1]['settings_value']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Permanent Block After (Resets):</label>
                    <input type="number" name="perm_block_count" value="<?= htmlspecialchars($current_settings[2]['settings_value']) ?>" required>
                </div>
                
                <button type="submit">Update Settings</button>
            </form>
        </main>
    </div>
</body>
</html>