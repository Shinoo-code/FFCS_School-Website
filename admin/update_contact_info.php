<?php
// update_contact_info.php
// Centralized session initialization
require_once __DIR__ . '/../api/session.php'; // Start session with hardened settings

// Include the database connection script
// Make sure the path to db_connect.php is correct relative to this file.
// If update_contact_info.php is in the root directory and db_connect.php is in api/, then 'api/db_connect.php' is correct.
require_once '../api/db_connect.php'; // Ensures $pdo is available

// Optional: Add an admin role check if only admins should perform this action
// This is a good security practice.
/*
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    // Redirect to login or an error page if not an authorized admin
    header("Location: login.php?error=unauthorized_action");
    exit;
}
*/

// Check if the form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the arrays of data submitted by the form
    // The '?? []' ensures these are arrays even if nothing is submitted, preventing errors.
    $ids = $_POST['id'] ?? [];
    $labels = $_POST['label'] ?? [];
    $values = $_POST['value'] ?? [];
    $icon_classes = $_POST['icon_class'] ?? [];

    // Check if the number of items in each array matches
    // This is a basic validation to ensure data integrity.
    if (count($ids) === count($labels) && count($ids) === count($values) && count($ids) === count($icon_classes)) {
        try {
            // Prepare the SQL statement for updating a contact item
            // Using a prepared statement helps prevent SQL injection.
            $update_stmt = $pdo->prepare("UPDATE contact_info SET label = :label, value = :value, icon_class = :icon_class WHERE id = :id");

            // Loop through each submitted contact item
            for ($i = 0; $i < count($ids); $i++) {
                // Sanitize each piece of data before using it in the query
                // htmlspecialchars is used here for output context, but for DB, direct binding is fine.
                // trim() removes whitespace from the beginning and end of a string.
                $current_id = (int)$ids[$i]; // Ensure ID is an integer
                $current_label = trim($labels[$i]);
                $current_value = trim($values[$i]);
                $current_icon_class = trim($icon_classes[$i]); // Icon class can be empty

                // Bind parameters to the prepared statement
                $update_stmt->bindParam(':id', $current_id, PDO::PARAM_INT);
                $update_stmt->bindParam(':label', $current_label, PDO::PARAM_STR);
                $update_stmt->bindParam(':value', $current_value, PDO::PARAM_STR);
                $update_stmt->bindParam(':icon_class', $current_icon_class, PDO::PARAM_STR);

                // Execute the update for the current item
                $update_stmt->execute();
            }

            // If all updates are successful, redirect back to the edit page with a success message
            header("Location: edit_contact_info.php?success=1");
            exit;

        } catch (PDOException $e) {
            // If a database error occurs, log the error and redirect with an error message
            error_log("Database error updating contact info: " . $e->getMessage());
            header("Location: edit_contact_info.php?error=" . urlencode("A database error occurred. Please try again."));
            exit;
        } catch (Exception $e) {
            // Catch any other general errors
            error_log("General error updating contact info: " . $e->getMessage());
            header("Location: edit_contact_info.php?error=" . urlencode("An unexpected error occurred."));
            exit;
        }
    } else {
        // If the counts of submitted array items do not match, it indicates a form submission problem.
        header("Location: edit_contact_info.php?error=" . urlencode("Form data mismatch. Please try submitting again."));
        exit;
    }
} else {
    // If the page is accessed directly via GET or any method other than POST,
    // redirect to the edit page, as this script is only for processing form submissions.
    header("Location: edit_contact_info.php");
    exit;
}
?>
