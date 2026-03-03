<?php
// api/schedules/handler.php
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../manage_schedules.php?error=Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../manage_schedules.php?error=Invalid request method");
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_schedule':
            $schedule_key = trim($_POST['schedule_key'] ?? '');
            $display_text = trim($_POST['display_text'] ?? '');

            if (empty($schedule_key) || empty($display_text)) {
                throw new Exception("Schedule Key and Display Text are required.");
            }
            // Optional: Validate key format (e.g., letters only)
            if (!preg_match('/^[A-Za-z]+$/', $schedule_key)) {
                throw new Exception("Schedule Key should contain only letters (e.g., MWF, TTh).");
            }

            // Check if key already exists
            $stmt_check = $pdo->prepare("SELECT id FROM schedule_patterns WHERE schedule_key = ?");
            $stmt_check->execute([$schedule_key]);
            if ($stmt_check->fetch()) {
                 throw new Exception("Schedule Key '$schedule_key' already exists.");
            }

            $stmt_insert = $pdo->prepare("INSERT INTO schedule_patterns (schedule_key, display_text) VALUES (?, ?)");
            $stmt_insert->execute([$schedule_key, $display_text]);
            header("Location: ../../manage_schedules.php?success=Schedule pattern added successfully!");
            break;

        case 'delete_schedule':
            $schedule_id = $_POST['schedule_id'] ?? null;
            if (empty($schedule_id)) {
                throw new Exception("Schedule ID is missing.");
            }

            // Optional: Check if the schedule is currently used in section_subjects before deleting
            // $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) FROM section_subjects WHERE schedule = (SELECT schedule_key FROM schedule_patterns WHERE id = ?)");
            // $stmt_check_usage->execute([$schedule_id]);
            // if ($stmt_check_usage->fetchColumn() > 0) {
            //     throw new Exception("Cannot delete schedule pattern because it is currently assigned to one or more subjects.");
            // }

            $stmt_delete = $pdo->prepare("DELETE FROM schedule_patterns WHERE id = ?");
            $stmt_delete->execute([$schedule_id]);
            if ($stmt_delete->rowCount() > 0) {
                 header("Location: ../../manage_schedules.php?success=Schedule pattern deleted successfully!");
            } else {
                 throw new Exception("Schedule pattern not found or already deleted.");
            }
            break;

        default:
            throw new Exception("Invalid action specified.");
    }

} catch (PDOException $e) {
    error_log("Schedule Handler PDO Error: " . $e->getMessage());
    header("Location: ../../manage_schedules.php?error=Database error occurred. Check logs.");
} catch (Exception $e) {
    header("Location: ../../manage_schedules.php?error=" . urlencode($e->getMessage()));
}
exit;
?>