<?php
// manage_time_slots.php
require_once __DIR__ . '/api/session.php';
require_once 'api/db_connect.php'; // Ensure path is correct

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Fetch existing time slots
$time_slots = [];
try {
    // Assuming a table 'time_slots' with columns 'id', 'time_value', 'display_text', 'start_minutes', 'end_minutes'
    // Order by start time
    $stmt = $pdo->query("SELECT id, time_value, display_text, start_minutes, end_minutes FROM time_slots ORDER BY start_minutes ASC");
    $time_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Could not retrieve time slots: " . $e->getMessage();
    error_log("Error fetching time slots: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time Slots - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/manage_feedback.css">
    <style>
        /* Small style adjustments if needed */
        .time-value-input { font-family: monospace; }
    </style>
</head>
<body>

<div class="page-container" style="max-width: 800px;">
    <h2 class="page-title">Manage Time Slots</h2>

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
        <div class="card-header"><h5 class="mb-0">Add New Time Slot</h5></div>
        <div class="card-body">
            <form action="api/timeslots/handler.php" method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add_timeslot">
                <div class="col-md-5">
                    <label for="time_value" class="form-label">Time Value (Start-End Minutes)*</label>
                    <input type="text" id="time_value" name="time_value" class="form-control time-value-input" placeholder="e.g., 540-600" required pattern="\d+-\d+" title="Format: start_minutes-end_minutes (e.g., 540-600 for 9-10 AM)">
                    <small class="form-text text-muted">Minutes from midnight (0-1439).</small>
                </div>
                <div class="col-md-5">
                    <label for="display_text" class="form-label">Display Text*</label>
                    <input type="text" id="display_text" name="display_text" class="form-control" placeholder="e.g., 9:00 AM - 10:00 AM" required>
                     <small class="form-text text-muted">How it appears in dropdowns.</small>
               </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                </div>
            </form>
        </div>
    </div>


    <h3 class="mt-4">Existing Time Slots</h3>
    <?php if (empty($time_slots)): ?>
        <p class="text-center text-muted">No time slots defined yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time Value</th>
                        <th>Display Text</th>
                        <th>Start (min)</th>
                        <th>End (min)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $slot): ?>
                        <tr>
                            <td><?= htmlspecialchars($slot['id']) ?></td>
                            <td class="time-value-input"><?= htmlspecialchars($slot['time_value']) ?></td>
                            <td><?= htmlspecialchars($slot['display_text']) ?></td>
                            <td><?= htmlspecialchars($slot['start_minutes']) ?></td>
                            <td><?= htmlspecialchars($slot['end_minutes']) ?></td>
                            <td>
                                <form action="api/timeslots/handler.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this time slot? It might be in use.');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_timeslot">
                                    <input type="hidden" name="timeslot_id" value="<?= htmlspecialchars($slot['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Time Slot">
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
        <a href="dashboard.php#dashboard-section" class="btn-back">
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