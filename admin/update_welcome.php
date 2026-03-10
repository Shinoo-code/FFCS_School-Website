<?php
require '../api/db_connect.php'; // Ensures $pdo is available

// Sanitize POST inputs (basic example, consider more robust validation)
$welcome_title = htmlspecialchars(trim($_POST['welcome_title'] ?? ''));
$welcome_subtitle = htmlspecialchars(trim($_POST['welcome_subtitle'] ?? ''));
$welcome_paragraph = htmlspecialchars(trim($_POST['welcome_paragraph'] ?? ''));
$reason1_title = htmlspecialchars(trim($_POST['reason1_title'] ?? ''));
$reason1_desc = htmlspecialchars(trim($_POST['reason1_desc'] ?? ''));
$reason2_title = htmlspecialchars(trim($_POST['reason2_title'] ?? ''));
$reason2_desc = htmlspecialchars(trim($_POST['reason2_desc'] ?? ''));
$reason3_title = htmlspecialchars(trim($_POST['reason3_title'] ?? ''));
$reason3_desc = htmlspecialchars(trim($_POST['reason3_desc'] ?? ''));

$image_path_to_save = null;

// Fetch current image path from DB to manage it
$stmt_current = $pdo->prepare("SELECT welcome_image_path FROM welcome_section WHERE id = 1");
$stmt_current->execute();
$current_db_data = $stmt_current->fetch(PDO::FETCH_ASSOC);
$existing_image_path = $current_db_data ? $current_db_data['welcome_image_path'] : null;

// Handle image upload
if (isset($_FILES['welcome_image']) && $_FILES['welcome_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'FFCS Pics/'; // Make sure this directory exists and is writable by the web server
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) { // Create directory if it doesn't exist
            header("Location: manage_welcome.php?update_error=Failed to create upload directory.");
            exit;
        }
    }

    $filename = basename($_FILES['welcome_image']['name']);
    // Sanitize filename to prevent directory traversal and other issues
    $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    $new_target_path = $upload_dir . uniqid('welcome_', true) . '_' . $safe_filename; // Add unique prefix to avoid overwrites

    if (move_uploaded_file($_FILES['welcome_image']['tmp_name'], $new_target_path)) {
        $image_path_to_save = $new_target_path;
        // Optionally, delete the old image if it's different and not a default/placeholder
        if ($existing_image_path && $existing_image_path !== $image_path_to_save && file_exists($existing_image_path) /* && $existing_image_path !== 'path/to/default.png' */) {
            unlink($existing_image_path);
        }
    } else {
        // Handle file move error
        header("Location: manage_welcome.php?update_error=Image upload failed.");
        exit;
    }
} else {
    // No new image uploaded, retain the existing one
    $image_path_to_save = $existing_image_path;
}

// Check if a record with id = 1 exists
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM welcome_section WHERE id = 1");
$stmt_check->execute();
$record_exists = $stmt_check->fetchColumn() > 0;

if ($record_exists) {
    // Record exists, UPDATE it
    $sql = "UPDATE welcome_section SET 
                welcome_title = :welcome_title,
                welcome_subtitle = :welcome_subtitle,
                welcome_paragraph = :welcome_paragraph,
                welcome_image_path = :image_path,
                reason1_title = :reason1_title,
                reason1_desc = :reason1_desc,
                reason2_title = :reason2_title,
                reason2_desc = :reason2_desc,
                reason3_title = :reason3_title,
                reason3_desc = :reason3_desc
            WHERE id = 1";
} else {
    // Record does not exist, INSERT a new one with id = 1
    $sql = "INSERT INTO welcome_section 
                (id, welcome_title, welcome_subtitle, welcome_paragraph, welcome_image_path, 
                 reason1_title, reason1_desc, reason2_title, reason2_desc, reason3_title, reason3_desc) 
            VALUES 
                (1, :welcome_title, :welcome_subtitle, :welcome_paragraph, :image_path, 
                 :reason1_title, :reason1_desc, :reason2_title, :reason2_desc, :reason3_title, :reason3_desc)";
}

$params = [
    ':welcome_title' => $welcome_title,
    ':welcome_subtitle' => $welcome_subtitle,
    ':welcome_paragraph' => $welcome_paragraph,
    ':image_path' => $image_path_to_save, 
    ':reason1_title' => $reason1_title,
    ':reason1_desc' => $reason1_desc,
    ':reason2_title' => $reason2_title,
    ':reason2_desc' => $reason2_desc,
    ':reason3_title' => $reason3_title,
    ':reason3_desc' => $reason3_desc
];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    header("Location: manage_welcome.php?updated=1");
} catch (PDOException $e) {
    // Log error and redirect with a generic error message
    error_log("Error updating/inserting welcome section: " . $e->getMessage());
    header("Location: manage_welcome.php?update_error=Database operation failed.");
}
exit;
?>