<?php
// Use centralized session and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path if necessary

// Ensure only admins can perform this action
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../manage_feedback.php?error_message=" . urlencode("Unauthorized action."));
    exit;
}

if (isset($_GET['id'])) {
    $feedback_id = (int)$_GET['id'];

    if ($feedback_id > 0) {
        try {
            // Optional: Delete associated profile image if it exists
            $stmt_select_img = $pdo->prepare("SELECT profile_image_path FROM parent_feedback WHERE id = :id");
            $stmt_select_img->bindParam(':id', $feedback_id, PDO::PARAM_INT);
            $stmt_select_img->execute();
            $image_info = $stmt_select_img->fetch(PDO::FETCH_ASSOC);

            if ($image_info && !empty($image_info['profile_image_path'])) {
                $image_file_path = '../../' . $image_info['profile_image_path']; // Path relative to this script's location
                if (file_exists($image_file_path)) {
                    @unlink($image_file_path);
                }
            }

            // Delete the feedback entry
            $stmt_delete = $pdo->prepare("DELETE FROM parent_feedback WHERE id = :id");
            $stmt_delete->bindParam(':id', $feedback_id, PDO::PARAM_INT);
            $stmt_delete->execute();

            if ($stmt_delete->rowCount() > 0) {
                header("Location: ../../manage_feedback.php?success_message=" . urlencode("Feedback deleted successfully."));
            } else {
                header("Location: ../../manage_feedback.php?error_message=" . urlencode("Feedback not found or already deleted."));
            }
            exit;

        } catch (PDOException $e) {
            error_log("Error deleting feedback: " . $e->getMessage());
            header("Location: ../../manage_feedback.php?error_message=" . urlencode("Database error deleting feedback."));
            exit;
        }
    } else {
        header("Location: ../../manage_feedback.php?error_message=" . urlencode("Invalid feedback ID."));
        exit;
    }
} else {
    header("Location: ../../manage_feedback.php?error_message=" . urlencode("Missing feedback ID."));
    exit;
}
?>