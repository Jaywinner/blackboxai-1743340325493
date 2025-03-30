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

// Pagination setup
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get total attacks count
$total_attacks = $db->query("SELECT COUNT(*) FROM ATTACK")->fetchColumn();
$total_pages = ceil($total_attacks / $per_page);

// Get attacks with pagination
$attacks = [];
try {
    $stmt = $db->prepare("
        SELECT 
            a.id, 
            a.attack_query, 
            a.black_list_id,
            b.ip, 
            b.last_attack_time,
            i.injection_name
        FROM ATTACK a
        JOIN BLACK_LIST b ON a.black_list_id = b.id
        JOIN INJECTIONS i ON a.injection_id = i.id
        ORDER BY b.last_attack_time DESC
        LIMIT :offset, :per_page
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $attacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load attack log: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - Attack Log</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>SQL Injection Attack Log</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="attacks.php" class="active">Attack Log</a>
                <a href="blocked.php">Blocked IPs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>

        <main class="dashboard-content">
            <?php if(isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>IP Address</th>
                            <th>Attack Type</th>
                            <th>Malicious Query</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attacks as $attack): ?>
                        <tr>
                            <td><?= htmlspecialchars($attack['last_attack_time']) ?></td>
                            <td><?= htmlspecialchars($attack['ip']) ?></td>
                            <td><?= htmlspecialchars($attack['injection_name']) ?></td>
                            <td class="query-cell">
                                <div class="query-preview">
                                    <?= htmlspecialchars(substr($attack['attack_query'], 0, 50)) ?>...
                                </div>
                                <div class="query-full" style="display:none">
                                    <?= htmlspecialchars($attack['attack_query']) ?>
                                </div>
                            </td>
                            <td>
                                <button class="view-query">View Full</button>
                                <a href="blocked.php?ip=<?= urlencode($attack['ip']) ?>" class="block-btn">Block IP</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Toggle full query view
    document.querySelectorAll('.view-query').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const preview = row.querySelector('.query-preview');
            const full = row.querySelector('.query-full');
            
            if(preview.style.display === 'none') {
                preview.style.display = 'block';
                full.style.display = 'none';
                this.textContent = 'View Full';
            } else {
                preview.style.display = 'none';
                full.style.display = 'block';
                this.textContent = 'Hide';
            }
        });
    });
    </script>
</body>
</html>