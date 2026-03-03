<?php
// api/timeslots/handler.php
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../manage_time_slots.php?error=Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../manage_time_slots.php?error=Invalid request method");
    exit;
}

/**
 * Parses a time slot value string (e.g., "540-600") into start/end minutes.
 */
function parseAndValidateTimeValue($timeValue) {
    $parts = explode('-', trim($timeValue));
    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
        $start = (int)$parts[0];
        $end = (int)$parts[1];
        // Validate minutes range (0 to 1439 for a 24-hour day) and end > start
        if ($start >= 0 && $start < 1440 && $end > $start && $end <= 1440) {
            return ['start' => $start, 'end' => $end];
        }
    }
    return null; // Invalid format or values
}


$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_timeslot':
            $time_value = trim($_POST['time_value'] ?? '');
            $display_text = trim($_POST['display_text'] ?? '');

            if (empty($time_value) || empty($display_text)) {
                throw new Exception("Time Value and Display Text are required.");
            }

            // Validate and parse the time_value
            $parsed_time = parseAndValidateTimeValue($time_value);
            if ($parsed_time === null) {
                throw new Exception("Invalid Time Value format. Use 'start_minutes-end_minutes' (e.g., 540-600) and ensure End > Start.");
            }
            $start_minutes = $parsed_time['start'];
            $end_minutes = $parsed_time['end'];

            // Check if time_value already exists
            $stmt_check = $pdo->prepare("SELECT id FROM time_slots WHERE time_value = ?");
            $stmt_check->execute([$time_value]);
            if ($stmt_check->fetch()) {
                 throw new Exception("Time Value '$time_value' already exists.");
            }

            // Optional: Check for overlapping time slots with existing ones (more complex logic)
            // $stmt_overlap = $pdo->prepare("SELECT id FROM time_slots WHERE (:start < end_minutes) AND (:end > start_minutes)");
            // $stmt_overlap->execute([':start' => $start_minutes, ':end' => $end_minutes]);
            // if ($stmt_overlap->fetch()) {
            //      throw new Exception("The new time slot overlaps with an existing time slot.");
            // }


            $stmt_insert = $pdo->prepare("INSERT INTO time_slots (time_value, display_text, start_minutes, end_minutes) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$time_value, $display_text, $start_minutes, $end_minutes]);
            header("Location: ../../manage_time_slots.php?success=Time slot added successfully!");
            break;

        case 'delete_timeslot':
            $timeslot_id = $_POST['timeslot_id'] ?? null;
            if (empty($timeslot_id)) {
                throw new Exception("Time Slot ID is missing.");
            }

            // Optional: Check if the time slot is currently used in section_subjects before deleting
            // $stmt_check_usage = $pdo->prepare("SELECT COUNT(*) FROM section_subjects WHERE time_slot = (SELECT time_value FROM time_slots WHERE id = ?)");
            // $stmt_check_usage->execute([$timeslot_id]);
            // if ($stmt_check_usage->fetchColumn() > 0) {
            //     throw new Exception("Cannot delete time slot because it is currently assigned to one or more subjects.");
            // }


            $stmt_delete = $pdo->prepare("DELETE FROM time_slots WHERE id = ?");
            $stmt_delete->execute([$timeslot_id]);
             if ($stmt_delete->rowCount() > 0) {
                header("Location: ../../manage_time_slots.php?success=Time slot deleted successfully!");
            } else {
                 throw new Exception("Time slot not found or already deleted.");
            }
            break;

        default:
            throw new Exception("Invalid action specified.");
    }

} catch (PDOException $e) {
    error_log("Time Slot Handler PDO Error: " . $e->getMessage());
    header("Location: ../../manage_time_slots.php?error=Database error occurred. Check logs.");
} catch (Exception $e) {
    header("Location: ../../manage_time_slots.php?error=" . urlencode($e->getMessage()));
}
exit;
?>