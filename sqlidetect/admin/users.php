<?php
session_start();
require_once('../config/globals.php');
require_once('../config/database.php');

// Check authentication and admin privileges
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

// Handle user actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_user'])) {
        // Add new user
        try {
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
            $message = "User added successfully";
        } catch(PDOException $e) {
            $error = "Failed to add user: " . $e->getMessage();
        }
    }
    elseif(isset($_POST['delete_user'])) {
        // Delete user
        try {
            $stmt = $db->prepare("DELETE FROM USERS WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User deleted successfully";
        } catch(PDOException $e) {
            $error = "Failed to delete user: " . $e->getMessage();
        }
    }
    elseif(isset($_POST['reset_password'])) {
        // Reset password
        try {
            $stmt = $db->prepare("
                UPDATE USERS 
                SET pass_word = ? 
                WHERE id = ?
            ");
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt->execute([$hashed_password, $_POST['user_id']]);
            $message = "Password reset successfully";
        } catch(PDOException $e) {
            $error = "Failed to reset password: " . $e->getMessage();
        }
    }
}

// Get all users
$users = [];
try {
    $stmt = $db->query("
        SELECT id, first_name, last_name, user_name, email, last_login
        FROM USERS
        ORDER BY last_name, first_name
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQLiDetect - User Management</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>User Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="attacks.php">Attack Log</a>
                <a href="blocked.php">Blocked IPs</a>
                <a href="settings.php">Settings</a>
                <a href="users.php" class="active">Users</a>
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

            <div class="user-management">
                <div class="add-user-form">
                    <h2>Add New User</h2>
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name:</label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name:</label>
                                <input type="text" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username:</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit" name="add_user">Add User</button>
                    </form>
                </div>

                <div class="users-list">
                    <h2>System Users</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['user_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never' ?></td>
                                <td class="actions">
                                    <button class="reset-password-btn" data-user-id="<?= $user['id'] ?>">Reset Password</button>
                                    <form method="post" class="delete-form">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Reset Password</h2>
            <form method="post" id="resetPasswordForm">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required>
                </div>
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
    // Password reset modal functionality
    const modal = document.getElementById('passwordModal');
    const resetButtons = document.querySelectorAll('.reset-password-btn');
    const closeModal = document.querySelector('.close-modal');
    const modalUserId = document.getElementById('modalUserId');

    resetButtons.forEach(button => {
        button.addEventListener('click', function() {
            modalUserId.value = this.dataset.userId;
            modal.style.display = 'block';
        });
    });

    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Confirm before deleting user
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to delete this user?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
