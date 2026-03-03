<?php
require_once __DIR__ . '/api/session.php';
require_once 'api/db_connect.php';

// Ensure only admins can access this page
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success_message'] ?? '';
$error_message = $_GET['error_message'] ?? '';

// Fetch all feedback entries
try {
    $stmt = $pdo->query("SELECT * FROM parent_feedback ORDER BY date_submitted DESC");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching parent feedback: " . $e->getMessage());
    $feedbacks = [];
    $error_message = "Could not retrieve feedback data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parent Feedback - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/manage_feedback.css">
</head>
<body>

<div class="page-container">
    <h2 class="page-title">Manage Parent Feedback</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="feedback-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Parent Name</th>
                    <th>Date Submitted</th> <th>Feedback</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No feedback submissions yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td><?= htmlspecialchars($fb['id']) ?></td>
                            <td>
                                <?php if (!empty($fb['profile_image_path']) && file_exists($fb['profile_image_path'])): ?>
                                    <img src="<?= htmlspecialchars($fb['profile_image_path']) ?>?t=<?= time() // Cache buster ?>" alt="Profile" class="profile-image-sm">
                                <?php else: ?>
                                    <span class="profile-placeholder-sm"><i class="fas fa-user-circle"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($fb['parent_name']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($fb['date_submitted']))) ?></td>
                            <td style="max-width: 300px; white-space: normal;"><?= nl2br(htmlspecialchars($fb['feedback_text'])) ?></td>
                            <td class="rating-display">
                                <?php if (!empty($fb['rating']) && is_numeric($fb['rating'])): ?>
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fas fa-star <?= $i < intval($fb['rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-label <?= $fb['is_approved'] ? 'status-approved' : 'status-pending' ?>">
                                    <?= $fb['is_approved'] ? 'Approved' : 'Pending' ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons-group">
                                    <?php if ($fb['is_approved']): ?>
                                        <a href="api/feedback/update_feedback_status.php?id=<?= $fb['id'] ?>&action=unapprove" class="btn btn-sm btn-unapprove" title="Unapprove Feedback"><i class="fas fa-times-circle"></i> Disapprove</a>
                                    <?php else: ?>
                                        <a href="api/feedback/update_feedback_status.php?id=<?= $fb['id'] ?>&action=approve" class="btn btn-sm btn-approve" title="Approve Feedback"><i class="fas fa-check-circle"></i> Approve</a>
                                    <?php endif; ?>
                                    <a href="api/feedback/delete_feedback.php?id=<?= $fb['id'] ?>" class="btn btn-sm btn-delete" title="Delete Feedback" onclick="return confirm('Are you sure you want to delete this feedback? This cannot be undone.');"><i class="fas fa-trash-alt"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="back-container">
        <a href="dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
                 const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                 if (bsAlert) bsAlert.close();
            } else {
                alert.style.display = 'none';
            }
        }, 5000);
    });
</script>
</body>
</html>