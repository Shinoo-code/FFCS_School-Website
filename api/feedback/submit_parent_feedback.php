<?php
require_once '../db_connect.php'; // Adjust path as necessary

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_name = trim($_POST['parent_name'] ?? '');
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $rating = isset($_POST['rating']) && is_numeric($_POST['rating']) ? (int)$_POST['rating'] : null;
    $profile_image_path = null;

    // Basic validation
    if (empty($parent_name) || empty($feedback_text)) {
        header("Location: ../../submit_feedback.php?status=error&message=" . urlencode("Name and feedback text are required."));
        exit;
    }

    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        $rating = null; // Invalid rating, so set to null
    }

    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/feedback_profiles/'; // Create this directory if it doesn't exist, relative to this script
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $allowed_types = ['image/jpeg', 'image/png'];
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../../submit_feedback.php?status=error&message=" . urlencode("Invalid image type. Only JPG and PNG are allowed."));
            exit;
        }
        if ($file_size > $max_size) {
            header("Location: ../../submit_feedback.php?status=error&message=" . urlencode("Image file is too large. Max 2MB."));
            exit;
        }

        $filename = basename($_FILES['profile_image']['name']);
        $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
        $extension = pathinfo($safe_filename, PATHINFO_EXTENSION);
        $unique_filename = uniqid('profile_', true) . '.' . $extension;
        $target_path = $upload_dir . $unique_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
            $profile_image_path = 'uploads/feedback_profiles/' . $unique_filename; // Path to store in DB, relative to project root
        } else {
            // Image upload failed, but feedback can still be submitted without an image
            // Log error if needed: error_log("Failed to move uploaded file: " . $_FILES['profile_image']['error']);
        }
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO parent_feedback (parent_name, feedback_text, rating, profile_image_path, is_approved, date_submitted) 
             VALUES (:parent_name, :feedback_text, :rating, :profile_image_path, 0, NOW())"
        );

        $stmt->bindParam(':parent_name', $parent_name);
        $stmt->bindParam(':feedback_text', $feedback_text);
        $stmt->bindParam(':rating', $rating, $rating === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':profile_image_path', $profile_image_path, $profile_image_path === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        
        $stmt->execute();

        header("Location: ../../submit_feedback.php?status=success");
        exit;

    } catch (PDOException $e) {
        error_log("Feedback submission PDOException: " . $e->getMessage());
        header("Location: ../../submit_feedback.php?status=error&message=" . urlencode("Database error occurred."));
        exit;
    }

} else {
    // Not a POST request, redirect or show error
    header("Location: ../../submit_feedback.php?status=error&message=" . urlencode("Invalid request method."));
    exit;
}
?>