<?php
// update_activity.php - UPDATED for DATE handling
include 'api/db_connect.php'; // Establishes $pdo

$id = null; // Initialize id
$redirect_url = 'manage_activities.php'; // Default redirect

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_url . "?error_msg=" . urlencode("Invalid request method."));
    exit;
}

// --- Get Data ---
$id = $_POST['id'] ?? null;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null; // Can be empty
$category = $_POST['category'] ?? '';

// Update redirect URL to point back to the edit page if ID exists
if ($id) {
    $redirect_url = "edit_activity.php?id=" . $id;
}

// --- Basic Validation ---
if (empty($id) || !is_numeric($id)) {
    header("Location: manage_activities.php?error_msg=" . urlencode("Invalid or missing activity ID.")); // Redirect to list page if ID is bad
    exit;
}
if (empty($title) || empty($description) || empty($start_date) || empty($category)) {
    header("Location: " . $redirect_url . "&error_msg=" . urlencode("Title, Description, Start Date, and Category are required."));
    exit;
}

// --- Date Validation ---
if (!empty($end_date) && $end_date < $start_date) {
    header("Location: " . $redirect_url . "&error_msg=" . urlencode("End Date cannot be before Start Date."));
    exit;
}
// If end_date is empty, treat it as NULL for the database
$end_date_for_db = !empty($end_date) ? $end_date : null;

// --- Fetch Current Image Path ---
$current_image_path = null;
try {
    $stmt_current = $pdo->prepare("SELECT image_path FROM activities WHERE id = ?");
    $stmt_current->execute([$id]);
    $current_image_path = $stmt_current->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching current image path: " . $e->getMessage());
    // Continue without deleting old image maybe? Or redirect with error
    header("Location: " . $redirect_url . "&error_msg=" . urlencode("Database error fetching current image."));
    exit;
}

// --- Image Upload Handling ---
$uploadDir = 'uploads/activities/'; // Relative to *this* script's location
$new_image_name_for_db = null; // Will store only the filename

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
             header("Location: " . $redirect_url . "&error_msg=" . urlencode("Failed to create upload directory."));
             exit;
        }
    }

    $image = $_FILES['image'];
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
        $new_image_name_for_db = $uniqueName; // Save only the filename

        // Delete old image file if it exists and is different
        if ($current_image_path && $current_image_path !== $new_image_name_for_db) {
             $old_file_path = $uploadDir . $current_image_path;
             if (file_exists($old_file_path)) {
                 @unlink($old_file_path); // Use @ to suppress errors if deletion fails
             }
        }
    } else {
        header("Location: " . $redirect_url . "&error_msg=" . urlencode("Failed to upload new image. Check permissions."));
        exit;
    }
}

// --- Database Update ---
try {
    if ($new_image_name_for_db !== null) {
        // Update with new image filename
        $stmt = $pdo->prepare("UPDATE activities SET title=?, description=?, start_date=?, end_date=?, category=?, image_path=? WHERE id=?");
        $params = [$title, $description, $start_date, $end_date_for_db, $category, $new_image_name_for_db, $id];
    } else {
        // Update without changing the image_path (keep existing one)
        $stmt = $pdo->prepare("UPDATE activities SET title=?, description=?, start_date=?, end_date=?, category=? WHERE id=?");
        $params = [$title, $description, $start_date, $end_date_for_db, $category, $id];
    }

    $stmt->execute($params);

    // Redirect back to edit page with success message
    header("Location: edit_activity.php?id=" . $id . "&success_msg=" . urlencode("Activity updated successfully!"));
    exit;

} catch (PDOException $e) {
    error_log("Error updating activity: " . $e->getMessage());
    header("Location: edit_activity.php?id=" . $id . "&error_msg=" . urlencode("Database error saving changes."));
    exit;
}
?>