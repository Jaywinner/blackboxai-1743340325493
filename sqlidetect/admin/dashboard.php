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

// Get statistics
$stats = [];
try {
    // Total attacks
    $stmt = $db->query("SELECT COUNT(*) FROM ATTACK");
    $stats['total_attacks'] = $stmt->fetchColumn();

    // Recent attacks (last 7 days)
    $stmt = $db->query("
        SELECT COUNT(*) 
        FROM ATTACK a
        JOIN BLACK_LIST b ON a.black_list_id = b.id
        WHERE b.last_attack_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['recent_attacks'] = $stmt->fetchColumn();

    // Blocked IPs
    $stmt = $db->query("
        SELECT COUNT(*) 
        FROM BLACK_LIST 
        WHERE block_status IN (2,4)
    ");
    $stats['blocked_ips'] = $stmt->fetchColumn();

    // Attack types
    $stmt = $db->query("
        SELECT i.injection_name, COUNT(*) as count
        FROM ATTACK a
        JOIN INJECTIONS i ON a.injection_id = i.id
        GROUP BY i.injection_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stats['top_attack_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Failed to load statistics: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - Admin Dashboard</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>SQLiDetect Dashboard</h1>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="attacks.php">Attack Log</a>
                <a href="blocked.php">Blocked IPs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>

        <main class="dashboard-content">
            <?php if(isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Attacks</h3>
                    <p><?= $stats['total_attacks'] ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Recent Attacks (7 days)</h3>
                    <p><?= $stats['recent_attacks'] ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Blocked IPs</h3>
                    <p><?= $stats['blocked_ips'] ?></p>
                </div>
            </div>

            <div class="chart-container">
                <h2>Top Attack Types</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Attack Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['top_attack_types'] as $type): ?>
                        <tr>
                            <td><?= htmlspecialchars($type['injection_name']) ?></td>
                            <td><?= $type['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>