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

// Handle IP blocking/unblocking
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['unblock_ip'])) {
        try {
            $stmt = $db->prepare("
                UPDATE BLACK_LIST 
                SET block_status = 3, 
                    reset_cnt = reset_cnt + 1,
                    blk_count = 0
                WHERE ip = ?
            ");
            $stmt->execute([$_POST['ip_address']]);
            $message = "IP address {$_POST['ip_address']} has been unblocked";
        } catch(PDOException $e) {
            $error = "Failed to unblock IP: " . $e->getMessage();
        }
    }
    elseif(isset($_POST['block_ip']) && !empty($_POST['custom_ip'])) {
        try {
            // Check if IP exists
            $stmt = $db->prepare("SELECT id FROM BLACK_LIST WHERE ip = ?");
            $stmt->execute([$_POST['custom_ip']]);
            
            if($stmt->rowCount() > 0) {
                // Update existing record
                $stmt = $db->prepare("
                    UPDATE BLACK_LIST 
                    SET block_status = 4,
                        last_attack_time = NOW()
                    WHERE ip = ?
                ");
            } else {
                // Create new record
                $stmt = $db->prepare("
                    INSERT INTO BLACK_LIST 
                    (ip, last_attack_time, block_status, blk_count, reset_cnt)
                    VALUES (?, NOW(), 4, 0, 0)
                ");
            }
            $stmt->execute([$_POST['custom_ip']]);
            $message = "IP address {$_POST['custom_ip']} has been blocked";
        } catch(PDOException $e) {
            $error = "Failed to block IP: " . $e->getMessage();
        }
    }
}

// Get blocked IPs
$blocked_ips = [];
try {
    $stmt = $db->query("
        SELECT 
            id, ip, last_attack_time, 
            block_status, blk_count, reset_cnt
        FROM BLACK_LIST
        WHERE block_status IN (2,4)
        ORDER BY last_attack_time DESC
    ");
    $blocked_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load blocked IPs: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - Blocked IPs</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Blocked IP Addresses</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="attacks.php">Attack Log</a>
                <a href="blocked.php" class="active">Blocked IPs</a>
                <a href="settings.php">Settings</a>
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

            <div class="block-ip-form">
                <h2>Manually Block IP</h2>
                <form method="post">
                    <input type="text" name="custom_ip" placeholder="Enter IP address" required>
                    <button type="submit" name="block_ip">Block IP</button>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Last Attack</th>
                            <th>Block Type</th>
                            <th>Block Count</th>
                            <th>Reset Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($blocked_ips as $ip): ?>
                        <tr>
                            <td><?= htmlspecialchars($ip['ip']) ?></td>
                            <td><?= htmlspecialchars($ip['last_attack_time']) ?></td>
                            <td>
                                <?= $ip['block_status'] == 2 ? 'Temporary' : 'Permanent' ?>
                            </td>
                            <td><?= $ip['blk_count'] ?></td>
                            <td><?= $ip['reset_cnt'] ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="ip_address" value="<?= htmlspecialchars($ip['ip']) ?>">
                                    <button type="submit" name="unblock_ip" class="unblock-btn">Unblock</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    // Confirm before unblocking
    document.querySelectorAll('.unblock-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to unblock this IP?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>