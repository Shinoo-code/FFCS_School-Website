<?php
require_once __DIR__ . '/../api/session.php';
require '../api/db_connect.php';

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
   header("Location: dashboard.php?error=unauthorized");
   exit;
}

$instructions_content = '';
$school_years_content = '';
$feedback_message = '';
$feedback_type = '';

if (isset($_GET['success'])) {
    $feedback_message = "Enrollment form content updated successfully!";
    $feedback_type = 'success';
}

try {
    // Fetch instructions
    $stmt_instructions = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt_instructions->execute(['enrollment_instructions']);
    $instructions_content = $stmt_instructions->fetchColumn();

    // Fetch school years
    $stmt_years = $pdo->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt_years->execute(['enrollment_school_years']);
    $school_years_db = $stmt_years->fetchColumn();
    // Convert comma-separated string to a multi-line string for the textarea
    $school_years_content = str_replace(',', "\n", $school_years_db);

} catch (PDOException $e) {
    $feedback_message = "Error fetching content: " . $e->getMessage();
    $feedback_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollment Form - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/manage_welcome.css"> </head>
<body>
    <div class="manage-welcome-container page-container">
        <h3 class="text-center">Edit Enrollment Form Content</h3>

        <?php if ($feedback_message): ?>
            <div class="alert alert-<?= $feedback_type ?> alert-dismissible fade show text-center" role="alert">
                <?= htmlspecialchars($feedback_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="update_enrollment_instructions.php">
            
            <div class="card mb-4">
                <div class="card-header"><strong>Manage School Year Options</strong></div>
                <div class="card-body">
                    <label for="school_years_content" class="form-label">Available School Years</label>
                    <textarea class="form-control" id="school_years_content" name="school_years_content" rows="4"><?= htmlspecialchars($school_years_content) ?></textarea>
                    <div class="form-text">Enter one school year per line (e.g., 2025-2026). This will populate the dropdown on the public enrollment form.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Edit Form Instructions</strong></div>
                 <div class="card-body">
                    <label for="instructions_content" class="form-label">Instructional Text</label>
                    <p class="text-muted small">You can use basic HTML tags like `&lt;p&gt;`, `&lt;strong&gt;` for bold, and `&lt;ul&gt;` with `&lt;li&gt;` for bullet points.</p>
                    <textarea class="form-control" id="instructions_content" name="instructions_content" rows="15"><?= htmlspecialchars($instructions_content) ?></textarea>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>

        <div class="back-container mt-4">
            <a href="../dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>