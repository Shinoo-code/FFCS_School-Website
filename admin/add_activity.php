<?php
// add_activity.php - UPDATED for DATE handling
include '../api/db_connect.php'; // Establishes $pdo

$redirect_url = 'manage_activities.php'; // Redirect back to the manage page

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_url . "?error_msg=" . urlencode("Invalid request method."));
    exit;
}

// --- Get Data ---
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null; // Can be empty
$category = $_POST['category'] ?? '';
$image = $_FILES['image'] ?? null;

// --- Basic Validation ---
if (empty($title) || empty($description) || empty($start_date) || empty($category)) {
    header("Location: " . $redirect_url . "?error_msg=" . urlencode("Title, Description, Start Date, and Category are required."));
    exit;
}
if (!$image || $image['error'] !== UPLOAD_ERR_OK || empty($image['name'])) {
     header("Location: " . $redirect_url . "?error_msg=" . urlencode("Image upload is required."));
     exit;
}

// --- Date Validation ---
if (!empty($end_date) && $end_date < $start_date) {
    header("Location: " . $redirect_url . "?error_msg=" . urlencode("End Date cannot be before Start Date."));
    exit;
}
// If end_date is empty, treat it as NULL for the database
$end_date_for_db = !empty($end_date) ? $end_date : null;


// --- Image Upload Handling ---
$uploadDir = 'uploads/activities/'; // Relative to *this* script's location
$image_name_for_db = null; // Will store only the filename

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
         header("Location: " . $redirect_url . "?error_msg=" . urlencode("Failed to create upload directory."));
         exit;
    }
}

$imageName = basename($image['name']);
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
$ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedTypes)) {
     header("Location: " . $redirect_url . "&error_msg=" . urlencode("Invalid image file type. Allowed: " . implode(', ', $allowedTypes)));
     exit;
}

// Generate unique filename
$safe_basename = preg_replace("/[^a-zA-Z0-9._-]/", "_", pathinfo($imageName, PATHINFO_FILENAME));
$uniqueName = uniqid('act_', true) . '_' . $safe_basename . '.' . $ext;
$targetFilePath = $uploadDir . $uniqueName;

if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
    $image_name_for_db = $uniqueName; // Save only the filename
} else {
    header("Location: " . $redirect_url . "&error_msg=" . urlencode("Error uploading image. Check permissions."));
    exit;
}

// --- Database Insert ---
try {
    // Note the inclusion of start_date and end_date
    $stmt = $pdo->prepare("INSERT INTO activities (title, description, start_date, end_date, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    $params = [$title, $description, $start_date, $end_date_for_db, $category, $image_name_for_db];
    $stmt->execute($params);

    // Redirect back to manage page with success message
    header("Location: " . $redirect_url . "?success_msg=" . urlencode("Activity added successfully!"));
    exit;

} catch (PDOException $e) {
    // Clean up uploaded file if DB insert fails
    if ($image_name_for_db && file_exists($targetFilePath)) {
        @unlink($targetFilePath);
    }
    error_log("Error adding activity: " . $e->getMessage());
    header("Location: " . $redirect_url . "?error_msg=" . urlencode("Database error adding activity."));
    exit;
}
?>