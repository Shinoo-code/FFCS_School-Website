<?php
// api/sections/handler.php - UPDATED to handle TH and THF
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../manage_schedules.php?error=Unauthorized");
    exit;
}

// --- Helper Functions for Schedule Conflict ---

/**
 * Parses a PREDEFINED schedule key into an array of standardized day abbreviations.
 * Input key examples: "MWF", "TTh", "Mon", "TH", "THF", etc. // MODIFIED
 * Returns an array (e.g., ['Mon', 'Wed', 'Fri']) or empty array.
 */
function parseSelectedScheduleToDays($scheduleKey) {
    $days = [];
    $key = strtoupper(trim($scheduleKey)); // Use the key directly

    // Logic based on predefined keys
    if (strpos($key, 'MWF') !== false) $days = ['Mon', 'Wed', 'Fri'];
    elseif (strpos($key, 'TTH') !== false) $days = ['Tue', 'Thu'];
    elseif ($key === 'MON') $days = ['Mon'];
    elseif ($key === 'TUE') $days = ['Tue'];
    elseif ($key === 'WED') $days = ['Wed'];
    elseif ($key === 'THU') $days = ['Thu'];
    elseif ($key === 'FRI') $days = ['Fri'];
    // --- NEW: Add conditions for TH and THF ---
    elseif ($key === 'TH') $days = ['Thu']; // Assuming TH means Thursday only
    elseif ($key === 'THF') $days = ['Thu', 'Fri']; // Assuming THF means Thursday and Friday
    // --- END NEW ---
    // Add Sat/Sun or other specific combos if they are keys in your $schedule_options
    else {
        // Fallback or error handling if needed, though dropdown should prevent invalid keys
        error_log("Unrecognized schedule key in parseSelectedScheduleToDays: " . $scheduleKey); // Log unrecognized keys
    }
    return array_unique($days);
}


/**
 * Parses a PREDEFINED time slot value (minutes-minutes) into start/end minutes.
 * Input value example: "540-600"
 * Returns an associative array ['start' => minutes, 'end' => minutes] or null on failure.
 */
function parseSelectedTimeToMinutes($timeValue) {
    $parts = explode('-', $timeValue);
    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
        $startMinutes = (int)$parts[0];
        $endMinutes = (int)$parts[1];
        if ($endMinutes > $startMinutes && $startMinutes >= 0 && $endMinutes <= 1440) { // Added range validation
            return ['start' => $startMinutes, 'end' => $endMinutes];
        }
    }
     error_log("Invalid time slot value format in parseSelectedTimeToMinutes: " . $timeValue); // Log invalid formats
    return null; // Invalid format or values
}

/**
 * Converts a predefined time slot value (e.g., "540-600") back to display text.
 * Needs access to the $time_slot_options array (or pass it in).
 */
function getTimeSlotDisplayText($timeValue, $options) {
    return $options[$timeValue] ?? htmlspecialchars($timeValue); // Return display text or the value itself if not found
}
// --- End Helper Functions ---


// --- Main Handler ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../manage_sections.php?error=invalid_request");
    exit;
}

// --- Recreate Time Slot Options Array Here (needed for error messages) ---
// This should match the array/DB fetch in manage_sections.php
$time_slot_options_handler = [];
try {
    $stmt_time_handler = $pdo->query("SELECT time_value, display_text FROM time_slots ORDER BY start_minutes ASC");
    $time_slot_options_handler = $stmt_time_handler->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Error fetching time slots in handler: " . $e->getMessage());
    // Provide minimal fallback if DB fails
    $time_slot_options_handler = [ "540-600" => "9:00 AM - 10:00 AM" ];
}
// --- End Time Slot Options ---


$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_section':
            // ... (add_section logic remains the same) ...
             $grade_level = $_POST['grade_level'];
             $section_name = trim($_POST['section_name']);
             if (empty($grade_level) || empty($section_name)) {
                 throw new Exception("Grade Level and Section Name are required.");
             }
             $stmt_check_sec = $pdo->prepare("SELECT id FROM sections WHERE grade_level = ? AND section_name = ?");
             $stmt_check_sec->execute([$grade_level, $section_name]);
             if ($stmt_check_sec->fetch()) {
                  throw new Exception("Section '{$section_name}' already exists for {$grade_level}.");
             }
             $stmt = $pdo->prepare("INSERT INTO sections (grade_level, section_name) VALUES (?, ?)");
             $stmt->execute([$grade_level, $section_name]);
             header("Location: ../../manage_sections.php?success=Section added successfully!");
             break;


        case 'delete_section':
            // ... (delete_section logic remains the same) ...
            $section_id = $_POST['section_id'];
            if (empty($section_id)) {
                throw new Exception("Section ID is missing.");
            }
            $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
            $stmt->execute([$section_id]);
            header("Location: ../../manage_sections.php?success=Section deleted successfully!");
            break;


        case 'add_subject':
            // Collect Input
            $section_id = $_POST['section_id'];
            $subject_name = trim($_POST['subject_name']);
            $teacher_name = trim($_POST['teacher_name'] ?? '');
            $schedule_key = trim($_POST['schedule'] ?? '');
            $time_slot_value = trim($_POST['time_slot'] ?? '');
            $room = trim($_POST['room'] ?? '');

            // **Basic Validation**
            if (empty($section_id) || empty($subject_name) || empty($teacher_name) || empty($schedule_key) || empty($time_slot_value)) {
                throw new Exception("Section ID, Subject Name, Teacher, Schedule, and Time Slot selection are required.");
            }

            // **Parse New Schedule/Time from Selected Values**
            $newDays = parseSelectedScheduleToDays($schedule_key);
            $newTime = parseSelectedTimeToMinutes($time_slot_value);

            // Get the display text for the selected time slot for potential error messages
            $newTimeText = getTimeSlotDisplayText($time_slot_value, $time_slot_options_handler);

            // --- MODIFIED ERROR CHECK ---
            if (empty($newDays)) {
                throw new Exception("Invalid Schedule selected. Could not parse key: '" . htmlspecialchars($schedule_key) . "'. Check Manage Schedules.");
            }
            if ($newTime === null) {
                throw new Exception("Invalid Time Slot selected. Could not parse value: '" . htmlspecialchars($time_slot_value) . "'. Check Manage Time Slots.");
            }
            // --- END MODIFIED ERROR CHECK ---

            $newStart = $newTime['start'];
            $newEnd = $newTime['end'];

            // **Query Existing Assignments for the SAME Teacher**
            $stmt_check = $pdo->prepare("SELECT ss.schedule, ss.time_slot, ss.subject_name, s.section_name
                                            FROM section_subjects ss
                                            JOIN sections s ON ss.section_id = s.id
                                            WHERE ss.teacher_name = ?");
            $stmt_check->execute([$teacher_name]);
            $existingAssignments = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

            // **Conflict Detection Loop**
            foreach ($existingAssignments as $existing) {
                $existingDays = parseSelectedScheduleToDays($existing['schedule']);
                $existingTime = parseSelectedTimeToMinutes($existing['time_slot']);

                if (empty($existingDays) || $existingTime === null) {
                    error_log("Skipping conflict check against invalid existing data for teacher {$teacher_name}: Schedule '{$existing['schedule']}', Time '{$existing['time_slot']}'");
                    continue;
                }
                $existingStart = $existingTime['start'];
                $existingEnd = $existingTime['end'];

                $dayOverlap = !empty(array_intersect($newDays, $existingDays));

                if ($dayOverlap) {
                    $timeOverlap = ($newStart < $existingEnd) && ($newEnd > $existingStart);

                    if ($timeOverlap) {
                        $conflictingDays = implode(', ', array_intersect($newDays, $existingDays));
                        $existingTimeText = getTimeSlotDisplayText($existing['time_slot'], $time_slot_options_handler);
                        throw new Exception("Conflict: Teacher '" . htmlspecialchars($teacher_name) . "' is already scheduled for '" . htmlspecialchars($existing['subject_name']) . "' in Section '" . htmlspecialchars($existing['section_name']) . "' on " . htmlspecialchars($conflictingDays) . " during " . htmlspecialchars($existingTimeText) . ".");
                    }
                }
            } // End conflict check loop

            // **No Conflict: Insert the subject**
            $stmt_insert = $pdo->prepare(
                "INSERT INTO section_subjects (section_id, subject_name, teacher_name, schedule, time_slot, room)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt_insert->execute([$section_id, $subject_name, $teacher_name, $schedule_key, $time_slot_value, $room]);
            header("Location: ../../manage_sections.php?success=Subject added to section!");
            break;

        case 'delete_subject':
            // ... (delete_subject logic remains the same) ...
             $subject_id = $_POST['subject_id'];
             if (empty($subject_id)) {
                 throw new Exception("Subject ID is missing.");
             }
             $stmt = $pdo->prepare("DELETE FROM section_subjects WHERE id = ?");
             $stmt->execute([$subject_id]);
             header("Location: ../../manage_sections.php?success=Subject removed from section!");
             break;


        default:
            throw new Exception("Invalid action specified.");
    }
} catch (Exception $e) {
    // Redirect back with the error message
    $errorMessage = $e->getMessage();
    error_log("Section Handler Error: " . $errorMessage);
    // Include context in error message if possible (e.g., during conflict check)
    header("Location: ../../manage_sections.php?error=" . urlencode($errorMessage));
}
exit; // Important to exit after handling the request
?>