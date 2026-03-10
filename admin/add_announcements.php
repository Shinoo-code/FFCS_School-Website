<?php
session_start(); // Good practice to start session for admin checks if needed later
include '../api/db_connect.php'; // Establishes $pdo

// Optional: Add an admin role check if only admins can add announcements
// if (!isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
//     header("Location: manage_announcements.php?error_message=" . urlencode("Unauthorized action."));
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Basic Validation
    if (empty($title) || empty($content)) {
        // Redirect back with an error message
        header("Location: manage_announcements.php?error_message=" . urlencode("Title and Content are required."));
        exit;
    }

    try {
        // Prepare and execute the insert statement
        // The 'date_posted' column in your 'announcements' table should ideally have a DEFAULT CURRENT_TIMESTAMP attribute.
        // If not, you might need to add NOW() or a PHP date in the SQL.
        // Assuming 'date_posted' has a default or you want to set it now:
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, date_posted) VALUES (?, ?, NOW())");
        $stmt->execute([$title, $content]);

        // Redirect back to the manage page with a success message
        header("Location: manage_announcements.php?success=1"); // success=1 is caught by manage_announcements.php
        exit;

    } catch (PDOException $e) {
        // Log the error for debugging
        error_log("Error adding announcement: " . $e->getMessage());
        // Redirect back with a generic database error message
        header("Location: manage_announcements.php?error_message=" . urlencode("Database error: Could not add announcement."));
        exit;
    }
} else {
    // If not a POST request, redirect to the manage page (or an error page)
    header("Location: manage_announcements.php");
    exit;
}
?>