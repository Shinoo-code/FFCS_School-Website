<?php
// update_hero.php

require '../api/db_connect.php';

$heading = $_POST['heading'];
$subheading = $_POST['subheading'];
$years = $_POST['years_excellence'];
$students = $_POST['students_enrolled'];

// Handle image upload if exists
$image_path_to_save_in_db = null; // This will hold the path to be saved in DB

// Fetch current image path from DB to manage it and for fallback
$stmt_current_img = $pdo->query("SELECT image_path FROM hero_section WHERE id = 1 LIMIT 1");
$current_db_image_path = $stmt_current_img ? $stmt_current_img->fetchColumn() : null;
$image_path_to_save_in_db = $current_db_image_path; // Default to current image

if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['hero_image']['name'])) {
    $upload_dir = 'FFCS Pics/'; // Ensure this directory exists and is writable relative to this script's location
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
            // Failed to create directory
            header("Location: manage_hero.php?update_error=" . urlencode("Failed to create image upload directory."));
            exit;
        }
    }

    $filename = basename($_FILES['hero_image']['name']);
    // Sanitize filename to prevent directory traversal and other issues
    $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
    $new_target_file_path = $upload_dir . uniqid('hero_', true) . '_' . $safe_filename; // Add unique prefix to avoid overwrites

    if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $new_target_file_path)) {
        $image_path_to_save_in_db = $new_target_file_path; // Set to new image path for DB update
        // Optionally, delete the old image if it's different and not a default/placeholder
        if ($current_db_image_path && $current_db_image_path !== $image_path_to_save_in_db && file_exists($current_db_image_path) && $current_db_image_path !== 'FFCS Pics/default_hero.jpg' /* Check against your default placeholder */) {
            @unlink($current_db_image_path); // Suppress error if unlink fails for some reason
        }
    } else {
        // Handle file move error
        header("Location: manage_hero.php?update_error=" . urlencode("Image upload failed. Could not move file."));
        exit;
    }
}
// If no new image was uploaded, $image_path_to_save_in_db remains the $current_db_image_path (or null if none existed)

// Prepare the update statement
// We will always try to update all text fields.
// The image_path field will be updated only if a new image was successfully uploaded OR if it was already set.
$sql_parts = [
    "heading = :heading",
    "subheading = :subheading",
    "years_excellence = :years",
    "students_enrolled = :students"
];
$params = [
    ':heading' => $heading,
    ':subheading' => $subheading,
    ':years' => $years,
    ':students' => $students,
    ':id' => 1 // Assuming hero section always has id = 1
];

// Only add image_path to SQL if it's not null (either new or existing valid path)
if ($image_path_to_save_in_db !== null) {
    $sql_parts[] = "image_path = :image_path";
    $params[':image_path'] = $image_path_to_save_in_db;
} elseif ($image_path_to_save_in_db === null && $current_db_image_path !== null) {
    // This case handles if current_db_image_path was null and no new image was uploaded
    // We might want to set it to NULL in the DB if it was previously set and now no image is desired.
    // For now, if $image_path_to_save_in_db is null, we don't update the image_path column,
    // effectively leaving it as is in the DB. If you want to explicitly set it to NULL:
    // $sql_parts[] = "image_path = NULL";
}


$sql = "UPDATE hero_section SET " . implode(", ", $sql_parts) . " WHERE id = :id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    // MODIFIED: Redirect back to manage_hero.php with a success parameter
    header("Location: manage_hero.php?updated=1");
} catch (PDOException $e) {
    error_log("Error updating hero section: " . $e->getMessage());
    header("Location: manage_hero.php?update_error=" . urlencode("Database operation failed: " . $e->getMessage()));
}
exit;
?>
