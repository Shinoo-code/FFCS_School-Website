<?php
// manage_teachers.php - UPDATED WITH GRADE LEVEL
require_once __DIR__ . '/../api/session.php';
require_once '../api/db_connect.php'; // Ensure path is correct

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Define Grade Levels (Consistent with manage_sections.php)
$grade_levels_options = ["Kindergarten", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"];

// --- ACTION HANDLER for ADD/DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_teacher') {
        $teacher_name = trim($_POST['teacher_name'] ?? '');
        $grade_level_assigned = $_POST['grade_level'] ?? null; // Get selected grade level

        if (!empty($teacher_name) && !empty($grade_level_assigned)) { // Check both fields
            try {
                // Check if name already exists
                $stmt_check = $pdo->prepare("SELECT id FROM teachers_list WHERE teacher_name = ?");
                $stmt_check->execute([$teacher_name]);
                if ($stmt_check->fetch()) {
                    $error_message = "Teacher name '" . htmlspecialchars($teacher_name) . "' already exists.";
                } else {
                    // Insert name AND grade level
                    $stmt_insert = $pdo->prepare("INSERT INTO teachers_list (teacher_name, grade_level_assigned) VALUES (?, ?)");
                    if ($stmt_insert->execute([$teacher_name, $grade_level_assigned])) {
                        $success_message = "Teacher '" . htmlspecialchars($teacher_name) . "' for Grade Level " . htmlspecialchars($grade_level_assigned) . " added successfully!";
                    } else {
                        $error_message = "Failed to add teacher.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
                error_log("Error adding teacher name: " . $e->getMessage());
            }
        } else {
            $error_message = "Teacher Name and Assigned Grade Level are required."; // Updated error message
        }
    } elseif ($action === 'delete_teacher') {
        // Delete logic remains the same
        $teacher_id_to_delete = $_POST['teacher_id'] ?? null;
        if (!empty($teacher_id_to_delete)) {
             try {
                 $stmt_delete = $pdo->prepare("DELETE FROM teachers_list WHERE id = ?");
                 if ($stmt_delete->execute([$teacher_id_to_delete])) {
                     if ($stmt_delete->rowCount() > 0) {
                         $success_message = "Teacher deleted successfully!";
                     } else {
                         $error_message = "Teacher not found or already deleted.";
                     }
                 } else {
                     $error_message = "Failed to delete teacher.";
                 }
             } catch (PDOException $e) {
                 $error_message = "Database error: " . $e->getMessage();
                 error_log("Error deleting teacher name: " . $e->getMessage());
             }
         } else {
             $error_message = "Invalid teacher ID for deletion.";
         }
    }
    // Redirect to avoid form resubmission on refresh
    $redirect_url = "manage_teachers.php";
    if ($success_message) $redirect_url .= "?success=" . urlencode($success_message);
    if ($error_message) $redirect_url .= ($success_message ? "&" : "?") . "error=" . urlencode($error_message);
    header("Location: " . $redirect_url);
    exit;
}
// --- END ACTION HANDLER ---


// --- Fetch Teacher Names AND Grade Level from the table ---
$teachers_list = [];
try {
    // Select the new grade_level_assigned column
    $stmt = $pdo->query("SELECT id, teacher_name, grade_level_assigned, created_at FROM teachers_list ORDER BY teacher_name ASC");
    $teachers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message .= " Could not retrieve teacher list: " . $e->getMessage(); // Append error
    error_log("Error fetching teacher names: " . $e->getMessage());
}
// --- END FETCH ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teacher Names - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="../css/manage_users.css"> </head>
<body>

<div class="user-management-container">
    <h2>Manage Teacher Names</h2>
    <p class="text-muted text-center mb-4">Add, view, or remove teacher names used for subject assignments.</p>

    <div class="message-area">
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
    </div>

    
    <h3 class="mt-4">Add New Teacher Name</h3>
    <form action="manage_teachers.php" method="POST" class="mb-4 p-3 border rounded bg-light">
        <input type="hidden" name="action" value="add_teacher">
        <div class="row g-3 align-items-end">
            <div class="col-md-5 form-group">
                <label for="teacher_name" class="form-label">Teacher Name:*</label>
                <input type="text" id="teacher_name" name="teacher_name" class="form-control" placeholder="Enter full name" required>
            </div>
            
            <div class="col-md-4 form-group">
                <label for="grade_level" class="form-label">Assign to Grade Level:*</label>
                <select id="grade_level" name="grade_level" class="form-select" required>
                    <option value="" disabled selected>Select Grade...</option>
                    <?php foreach ($grade_levels_options as $grade): ?>
                        <option value="<?= htmlspecialchars($grade) ?>"><?= is_numeric($grade) ? "Grade " . $grade : $grade ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
             <div class="col-md-3 d-grid">
                <button type="submit" class="btn-submit-user"><i class="fas fa-plus-circle"></i> Add Teacher</button>
            </div>
        </div>
    </form>
    

    <hr style="margin: 30px 0;">

    
    <div class="mb-4 text-center">
        <p class="form-text">Need to manage admin accounts? Go to the main <a href="manage_users.php" class="btn btn-secondary btn-sm"><i class="fas fa-users-cog"></i> User Management Page</a>.</p>
    </div>
    

    <hr>

    <h3>Current Teacher List</h3>
    <?php if (empty($teachers_list)): ?>
        
        <p class="text-center text-muted">No teachers added yet.</p>
    <?php else: ?>
        <div class="table-scrollable">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Teacher Name</th>
                        <th>Assigned Grade Level</th> <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers_list as $teacher): ?>
                        <tr data-teacher-id="<?= htmlspecialchars($teacher['id']) ?>">
                            <td><?= htmlspecialchars($teacher['id']) ?></td>
                            <td><?= htmlspecialchars($teacher['teacher_name']) ?></td>
                            
                            <td><?= htmlspecialchars($teacher['grade_level_assigned'] ?? 'Not Set') ?></td> <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($teacher['created_at']))) ?></td>
                            <td>
                                <form action="manage_teachers.php" method="POST" onsubmit="return confirm('Are you sure you want to delete the teacher: <?= htmlspecialchars(addslashes($teacher['teacher_name'])) ?>? This might affect subject assignments.');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_teacher">
                                    <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacher['id']) ?>">
                                    <button type="submit" class="btn-action delete-user-btn" title="Delete Teacher">
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
        
        <a href="../dashboard.php#dashboard-section" class="btn-back-to-dashboard">
            <i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if(bsAlert) bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>