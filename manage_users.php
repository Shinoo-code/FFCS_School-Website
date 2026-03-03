<?php
require_once __DIR__ . '/api/session.php';
require 'api/db_connect.php';

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
   header("Location: dashboard.php?error=unauthorized_user_management");
   exit;
}

$faculty_users = [];
try {
    $current_faculty_id = $_SESSION['faculty_id'] ?? null;
    if ($current_faculty_id) {
        $stmt = $pdo->prepare("SELECT id, email, display_name, role, created_at FROM faculty WHERE id != :current_faculty_id ORDER BY created_at DESC");
        $stmt->bindParam(':current_faculty_id', $current_faculty_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT id, email, display_name, role, created_at FROM faculty ORDER BY created_at DESC");
    }
    $faculty_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching faculty users: " . $e->getMessage());
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Accounts - Faculty Dashboard</title>
    <link rel="stylesheet" href="css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="user-management-container">
        <h2>User Account Management</h2>

        <div class="message-area">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="error-alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <h3>Create New Admin Account</h3>
        <form action="add_faculty.php" method="POST">
            <div class="form-group">
                <label for="display_name">Display Name:</label>
                <input type="text" id="display_name" name="display_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password (min. 8 characters):</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <input type="hidden" name="role" value="admin">

            <button type="submit" class="btn-submit-user"><i class="fas fa-plus-circle"></i> Create Account</button>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Existing User Accounts</h3>
        <?php if (empty($faculty_users)): ?>
            <p>No other user accounts found.</p>
        <?php else: ?>
            <div class="table-scrollable">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Display Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_users as $user): ?>
                            <tr data-user-id="<?= htmlspecialchars($user['id']) ?>">
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['display_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($user['created_at']))) ?></td>
                                <td>
                                    <div class="action-buttons-group">
                                        <a href="edit_user.php?id=<?= htmlspecialchars($user['id']) ?>" class="btn-action edit-user-btn" title="Edit User">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn-action delete-user-btn" title="Delete User" data-id="<?= htmlspecialchars($user['id']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="back-container">
            <a href="dashboard.php#dashboard-section" class="btn-back-to-dashboard">
                <i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const successAlert = document.getElementById('success-alert');
    const errorAlert = document.getElementById('error-alert');
    if (successAlert) {
        setTimeout(() => { const bsAlert = bootstrap.Alert.getOrCreateInstance(successAlert); if(bsAlert) bsAlert.close(); }, 3000);
    }
    if (errorAlert) {
        setTimeout(() => { const bsAlert = bootstrap.Alert.getOrCreateInstance(errorAlert); if(bsAlert) bsAlert.close(); }, 5000);
    }

    const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
    deleteUserButtons.forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.dataset.id;
            const userEmail = this.dataset.email;

            if (confirm(`Are you sure you want to delete the user: ${userEmail} (ID: ${userId})? This action cannot be undone.`)) {
                fetch('api/auth/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'User deleted successfully.');
                        document.querySelector(`tr[data-user-id='${userId}']`).remove();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete user.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while trying to delete the user.');
                });
            }
        });
    });
});
</script>

</body>
</html>