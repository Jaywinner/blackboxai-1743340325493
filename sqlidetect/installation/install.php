<?php
// SQLiDetect Installation Script
require_once('../config/globals.php');

// Check if already installed
if(file_exists('../config/installed.flag')) {
    die("SQLiDetect is already installed. Remove config/installed.flag to reinstall.");
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create database connection
        $db = new PDO(
            "mysql:host={$_POST['hostname']};charset=utf8",
            $_POST['username'],
            $_POST['password']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $db->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['databasename']}`");
        $db->exec("USE `{$_POST['databasename']}`");

        // Execute schema script
        $schema = file_get_contents('schema.sql');
        $db->exec($schema);

        // Save configuration
        $config = <<<EOT
<?php
\$db_host = '{$_POST['hostname']}';
\$db_user = '{$_POST['username']}';
\$db_password = '{$_POST['password']}';
\$db_name = '{$_POST['databasename']}';
\$db_type = '{$_POST['db_type']}';
?>
EOT;
        file_put_contents('../config/database.php', $config);
        
        // Create installed flag
        file_put_contents('../config/installed.flag', '');
        
        header('Location: ../register.php');
        exit;
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect Installation</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="install-container">
        <h1>SQLiDetect Installation</h1>
        
        <?php if(isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Database Host:</label>
                <input type="text" name="hostname" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>Database Username:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Database Password:</label>
                <input type="password" name="password">
            </div>
            
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="databasename" value="sqlidetect" required>
            </div>
            
            <div class="form-group">
                <label>Application Database Type:</label>
                <select name="db_type" required>
                    <option value="MySQL">MySQL</option>
                    <option value="PostgreSQL">PostgreSQL</option>
                </select>
            </div>
            
            <button type="submit">Install</button>
        </form>
    </div>
</body>
</html>