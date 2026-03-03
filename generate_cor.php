<?php
require_once 'api/db_connect.php'; // Ensure this path is correct

// --- Fetch Schedule Patterns from DB ---
$schedule_options = [];
try {
    $stmt_sched = $pdo->query("SELECT schedule_key, display_text FROM schedule_patterns ORDER BY display_text");
    $schedule_options = $stmt_sched->fetchAll(PDO::FETCH_KEY_PAIR); // key => display text
} catch (PDOException $e) {
    error_log("Error fetching schedule patterns for COR: " . $e->getMessage());
    // Fallback? Or just display the key if lookup fails?
}

// --- Fetch Time Slots from DB ---
$time_slot_options = [];
try {
    $stmt_time = $pdo->query("SELECT time_value, display_text FROM time_slots ORDER BY start_minutes ASC");
    $time_slot_options = $stmt_time->fetchAll(PDO::FETCH_KEY_PAIR); // value => display text
} catch (PDOException $e) {
    error_log("Error fetching time slots for COR: " . $e->getMessage());
    // Fallback? Or just display the value if lookup fails?
}

/**
 * Helper function to get the display text for a stored schedule key.
 */
function getScheduleDisplayTextCOR($scheduleKey, $options) {
    // Check if the key exists in the options array fetched from DB
    return isset($options[$scheduleKey]) ? htmlspecialchars($options[$scheduleKey]) : htmlspecialchars($scheduleKey); // Return display text or the key itself if not found
}

/**
 * Helper function to get the display text for a stored time slot value.
 */
function getTimeSlotDisplayTextCOR($timeValue, $options) {
    // Check if the value exists in the options array fetched from DB
    return isset($options[$timeValue]) ? htmlspecialchars($options[$timeValue]) : htmlspecialchars($timeValue); // Return display text or the value itself if not found
}
// --- End Definitions ---


$enrollment_id = $_GET['id'] ?? null;

if (!$enrollment_id) {
    die("Error: Enrollment ID is missing.");
}

// Fetch enrollment data
$stmt_enrollment = $pdo->prepare("SELECT * FROM enrollments WHERE id = ?");
$stmt_enrollment->execute([$enrollment_id]);
$enrollment = $stmt_enrollment->fetch(PDO::FETCH_ASSOC);

// **Improved Check:** Ensure enrollment exists AND status is 'Enrolled' (case-insensitive)
if (!$enrollment || strtolower($enrollment['status']) !== 'enrolled') {
    die("Error: No valid enrollment record found or the student is not yet enrolled.");
}

// Fetch assigned subjects for this enrollment (from the `subjects` table)
// These subjects were automatically assigned when the section was set in the dashboard
$stmt_subjects = $pdo->prepare("SELECT subject_name, teacher_name, schedule, time_slot, room FROM subjects WHERE enrollment_id = ? ORDER BY subject_name ASC");
$stmt_subjects->execute([$enrollment_id]);
$subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

// Format student name
$student_full_name = htmlspecialchars(
    ($enrollment['student_first_name'] ?? '') . ' ' .
    (!empty($enrollment['student_middle_name']) ? mb_substr($enrollment['student_middle_name'], 0, 1) . '. ' : '') .
    ($enrollment['student_last_name'] ?? '') .
    (!empty($enrollment['student_extension_name']) ? ' ' . $enrollment['student_extension_name'] : '')
);

// --- Financial Calculations ---
$total_tuition = (float)($enrollment['total_tuition'] ?? 0);
$outstanding_balance = (float)($enrollment['outstanding_balance'] ?? 0);
$amount_paid = $total_tuition - $outstanding_balance;

// Sample fee breakdown (adjust percentages as needed)
$misc_fee_percentage = 0.20; // 20%
$other_fees_percentage = 0.10; // 10%
$misc_fee = $total_tuition * $misc_fee_percentage;
$other_fees = $total_tuition * $other_fees_percentage;
$tuition_fee = $total_tuition - $misc_fee - $other_fees;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Registration - <?= $student_full_name ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* --- Styles remain the same as previous version --- */
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; font-size: 10pt; }
        .cor-container { width: 210mm; min-height: 270mm; margin: 15px auto; background-color: #fff; padding: 20mm 20mm 15mm 20mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .cor-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .school-logo { max-width: 65px; height: auto; }
        .header-text h1 { margin: 0; font-size: 1.5em; font-weight: 700; color: #333; }
        .header-text h2 { margin: 3px 0 0 0; font-size: 1.0em; font-weight: normal; color: #555; }
        .cor-title { text-align: center; font-size: 1.3em; font-weight: bold; margin: 15px 0; }
        .student-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; }
        .student-info td { padding: 5px; }
        .student-info .label { font-weight: bold; width: 18%; padding-right: 5px; }
        .student-info .value { width: 32%; }
        .section-title { font-size: 1.1em; font-weight: bold; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .subjects-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .subjects-table th, .subjects-table td { border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 0.8em; word-wrap: break-word; }
        .subjects-table th { background-color: #f2f2f2; font-weight: bold; }
        .subjects-table th:nth-child(1), .subjects-table td:nth-child(1) { width: 25%; } /* Subject */
        .subjects-table th:nth-child(2), .subjects-table td:nth-child(2) { width: 20%; } /* Teacher */
        .subjects-table th:nth-child(3), .subjects-table td:nth-child(3) { width: 15%; } /* Schedule */
        .subjects-table th:nth-child(4), .subjects-table td:nth-child(4) { width: 20%; } /* Time */
        .subjects-table th:nth-child(5), .subjects-table td:nth-child(5) { width: 10%; } /* Room */
        .financial-table { font-size: 0.85em; }
        .financial-table td:last-child { text-align: right; }
        .financial-table th:nth-child(1), .financial-table td:nth-child(1) { width: 70%; }
        .financial-table th:nth-child(2), .financial-table td:nth-child(2) { width: 30%; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; text-align: center; cursor: pointer; font-size: 1em; }
        .cor-footer { position: absolute; bottom: 15mm; left: 20mm; right: 20mm; text-align: center; font-size: 0.75em; color: #666; border-top: 1px solid #ccc; padding-top: 5px; }
        @media print { body { background-color: #fff; font-size: 10pt; } .cor-container { margin: 0; padding: 15mm; box-shadow: none; border: none; min-height: 0; width: 100%; height: auto; } .print-button { display: none; } .cor-header { padding-bottom: 10px; margin-bottom: 10px;} .section-title { margin-top: 15px; margin-bottom: 5px;} .subjects-table th, .subjects-table td { padding: 4px; font-size: 7.5pt; } .financial-table th, .financial-table td { padding: 4px; font-size: 8pt; } .student-info { font-size: 8.5pt; margin-bottom: 15px;} .student-info td { padding: 3px;} .cor-footer { position: static; margin-top: 20px; padding-top: 10px; } @page { size: A4; margin: 15mm; } }
    </style>
</head>
<body>

    <div class="cor-container">
        <div class="cor-header">
            <img src="FFCS Pics/logo_monte_cristo.jpg" alt="School Logo" class="school-logo">
            <div class="header-text">
                <h1>Faith Family Christian School</h1>
                <h2>Dasmariñas, Cavite</h2>
            </div>
        </div>

        <div class="cor-title">
            Certificate of Registration <br>
            S.Y. <?= htmlspecialchars($enrollment['school_year']) ?>
        </div>

        <h3 class="section-title">Student Information</h3>
        <table class="student-info">
            <tr>
                <td class="label">Name:</td>
                <td class="value"><?= $student_full_name ?></td>
                <td class="label">LRN:</td>
                <td class="value"><?= htmlspecialchars($enrollment['lrn'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label">Grade Level:</td>
                <td class="value"><?= htmlspecialchars($enrollment['grade_level'] ?? 'N/A') ?></td>
                <td class="label">Section:</td>
                <td class="value"><?= htmlspecialchars($enrollment['section'] ?? 'N/A') ?></td>
            </tr>
             <tr>
                <td class="label">Status:</td>
                <td class="value" colspan="3"><?= htmlspecialchars($enrollment['status'] ?? 'N/A') ?></td>
            </tr>
        </table>

        <h3 class="section-title">Class Schedule</h3>
        <?php if (!empty($subjects)): ?>
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Schedule</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['subject_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($subject['teacher_name'] ?? 'TBA') ?></td>
                        <td>
                            <?php
                                // --- MODIFIED: Use the helper function for Schedule ---
                                echo getScheduleDisplayTextCOR($subject['schedule'] ?? '', $schedule_options);
                            ?>
                        </td>
                        <td>
                            <?php
                                // --- MODIFIED: Use the helper function for Time Slot ---
                                echo getTimeSlotDisplayTextCOR($subject['time_slot'] ?? '', $time_slot_options);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($subject['room'] ?? 'TBA') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 0.9em; text-align: center; margin-top: 10px;">No subjects have been assigned yet.</p>
        <?php endif; ?>

        <h3 class="section-title">Assessment of Fees</h3>
        <table class="financial-table subjects-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tuition Fee</td>
                    <td>PHP <?= number_format($tuition_fee, 2) ?></td>
                </tr>
                <tr>
                    <td>Miscellaneous Fees</td>
                    <td>PHP <?= number_format($misc_fee, 2) ?></td>
                </tr>
                 <tr>
                    <td>Other Fees</td>
                    <td>PHP <?= number_format($other_fees, 2) ?></td>
                </tr>
                <tr style="font-weight: bold; background-color: #f2f2f2;">
                    <td>Total Assessed Fees</td>
                    <td>PHP <?= number_format($total_tuition, 2) ?></td>
                </tr>
                <tr>
                    <td>Amount Paid</td>
                    <td>PHP <?= number_format($amount_paid, 2) ?></td>
                </tr>
                <tr style="font-weight: bold; color: <?= ($outstanding_balance > 0) ? '#c00' : '#155724' ?>;">
                    <td>Outstanding Balance</td>
                    <td>PHP <?= number_format($outstanding_balance, 2) ?></td>
                </tr>
            </tbody>
        </table>

         <div class="cor-footer">
             This is a system-generated document. Not valid as official receipt. Date Generated: <?= date('F j, Y, g:i A') ?>
         </div>

    </div>

    <button class="print-button" onclick="window.print()"><i class="bi bi-printer"></i> Print Certificate</button>

</body>
</html>