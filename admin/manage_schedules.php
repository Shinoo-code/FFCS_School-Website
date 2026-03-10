<?php
// manage_schedules.php
require_once __DIR__ . '/../api/session.php';
require_once '../api/db_connect.php'; // Ensure path is correct

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Fetch existing schedule patterns
$schedules = [];
try {
    // Assuming a table named 'schedule_patterns' with columns 'id', 'schedule_key', 'display_text'
    $stmt = $pdo->query("SELECT id, schedule_key, display_text FROM schedule_patterns ORDER BY display_text ASC");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Could not retrieve schedule patterns: " . $e->getMessage();
    error_log("Error fetching schedule patterns: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule Patterns - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/manage_feedback.css">
</head>
<body>

<div class="page-container" style="max-width: 800px;">
    <h2 class="page-title">Manage Schedule Patterns</h2>

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

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Add New Schedule Pattern</h5></div>
        <div class="card-body">
             <form action="../api/schedules/handler.php" method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add_schedule">
                <div class="col-md-5">
                    <label for="schedule_key" class="form-label">Schedule Key*</label>
                    <input type="text" id="schedule_key" name="schedule_key" class="form-control" placeholder="e.g., MWF, TTh, Mon" required pattern="[A-Za-z]+" title="Use letters only (e.g., MWF, TTh)">
                    <small class="form-text text-muted">Short code used internally (no spaces).</small>
                </div>
                <div class="col-md-5">
                    <label for="display_text" class="form-label">Display Text*</label>
                    <input type="text" id="display_text" name="display_text" class="form-control" placeholder="e.g., Monday, Wednesday, Friday" required>
                     <small class="form-text text-muted">How it appears in dropdowns.</small>
               </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                </div>
            </form>
        </div>
    </div>


    <h3 class="mt-4">Existing Schedule Patterns</h3>
    <?php if (empty($schedules)): ?>
        <p class="text-center text-muted">No schedule patterns defined yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Schedule Key</th>
                        <th>Display Text</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['id']) ?></td>
                            <td><?= htmlspecialchars($schedule['schedule_key']) ?></td>
                            <td><?= htmlspecialchars($schedule['display_text']) ?></td>
                            <td>
                                <form action="../api/schedules/handler.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule pattern? It might be in use.');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Schedule">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="back-container mt-4">
        <a href="../dashboard.php#dashboard-section" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if(bsAlert) bsAlert.close();
        }, 5000);
    });
</script>
</body>
</html>