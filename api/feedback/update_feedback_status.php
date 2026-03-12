<?php
// Use centralized session and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path if necessary

// Ensure only admins can perform this action
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../admin/manage_feedback.php?error_message=" . urlencode("Unauthorized action."));
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $feedback_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $new_status = null;

    if ($action === 'approve') {
        $new_status = 1;
    } elseif ($action === 'unapprove') {
        $new_status = 0;
    }

    if ($new_status !== null && $feedback_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE parent_feedback SET is_approved = :status WHERE id = :id");
            $stmt->bindParam(':status', $new_status, PDO::PARAM_INT);
            $stmt->bindParam(':id', $feedback_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                header("Location: ../../admin/manage_feedback.php?success_message=" . urlencode("Feedback status updated successfully."));
            } else {
                header("Location: ../../admin/manage_feedback.php?error_message=" . urlencode("Feedback not found or status already set."));
            }
            exit;

        } catch (PDOException $e) {
            error_log("Error updating feedback status: " . $e->getMessage());
            header("Location: ../../admin/manage_feedback.php?error_message=" . urlencode("Database error updating status."));
            exit;
        }
    } else {
        header("Location: ../../admin/manage_feedback.php?error_message=" . urlencode("Invalid action or feedback ID."));
        exit;
    }
} else {
    header("Location: ../../admin/manage_feedback.php?error_message=" . urlencode("Missing parameters."));
    exit;
}
?>