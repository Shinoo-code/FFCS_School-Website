<?php
require_once __DIR__ . '/../api/session.php'; // centralized session initialization
include '../api/db_connect.php'; // Establishes $pdo connection

// Optional: Add an admin role check if only admins can delete announcements
// if (!isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
//     header("Location: manage_announcements.php?error_message=" . urlencode("Unauthorized action."));
//     exit;
// }

// Check if the 'delete' GET parameter is set and is a numeric ID
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $announcement_id_to_delete = (int)$_GET['delete'];

    if ($announcement_id_to_delete > 0) {
        try {
            // Prepare the SQL DELETE statement
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");

            // Bind the ID parameter
            $stmt->bindParam(':id', $announcement_id_to_delete, PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                // Check if any row was actually deleted
                if ($stmt->rowCount() > 0) {
                    // Redirect back to the manage page with a success message
                    header("Location: manage_announcements.php?deleted=1"); // deleted=1 is caught by manage_announcements.php
                    exit;
                } else {
                    // No rows affected, meaning announcement with that ID might not exist
                    header("Location: manage_announcements.php?error_message=" . urlencode("Announcement not found or already deleted."));
                    exit;
                }
            } else {
                // Execution failed (should ideally be caught by PDOException)
                header("Location: manage_announcements.php?error_message=" . urlencode("Failed to delete announcement."));
                exit;
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Error deleting announcement: " . $e->getMessage());
            // Redirect back with a generic database error message
            header("Location: manage_announcements.php?error_message=" . urlencode("Database error: Could not delete announcement."));
            exit;
        }
    } else {
        // Invalid ID (e.g., 0 or negative)
        header("Location: manage_announcements.php?error_message=" . urlencode("Invalid announcement ID."));
        exit;
    }
} else {
    // If 'delete' parameter is not set or not numeric, redirect to the manage page
    header("Location: manage_announcements.php?error_message=" . urlencode("No announcement ID provided for deletion."));
    exit;
}
?>