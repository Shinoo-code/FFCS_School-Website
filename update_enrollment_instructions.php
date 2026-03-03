<?php
require_once __DIR__ . '/api/session.php';
require 'api/db_connect.php';

if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
   header("Location: dashboard.php?error=unauthorized");
   exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instructions_content = $_POST['instructions_content'] ?? '';
    $school_years_content = $_POST['school_years_content'] ?? '';

    // Convert the line breaks in the textarea back to a comma-separated string for the database
    $years = array_filter(array_map('trim', explode("\n", $school_years_content)));
    $school_years_db_value = implode(',', $years);

    try {
        $pdo->beginTransaction();

        // Update instructions
        $stmt_instructions = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'enrollment_instructions'");
        $stmt_instructions->execute([$instructions_content]);

        // Update school years
        $stmt_years = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'enrollment_school_years'");
        $stmt_years->execute([$school_years_db_value]);

        $pdo->commit();
        header("Location: manage_enrollment_form.php?success=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: manage_enrollment_form.php");
    exit;
}
?>