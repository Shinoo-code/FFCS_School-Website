<?php
require_once __DIR__ . '/api/session.php';
require_once 'api/db_connect.php'; // Ensure path is correct

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// --- MODIFIED: Fetch Teacher Names AND Grade Level from NEW table ---
$teacher_names_list = []; // Changed variable name for clarity
try {
    // Fetch teacher_name, id, and grade_level_assigned from the new teachers_list table
    $stmt_teachers = $pdo->query("SELECT id, teacher_name, grade_level_assigned FROM teachers_list ORDER BY teacher_name ASC");
    $teacher_names_list = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teacher names list: " . $e->getMessage());
    $error_message .= " Could not load teacher names list."; // Append to existing errors
}
// --- END MODIFIED FETCH ---


// Fetch all sections and their subjects (Existing code)
$sections_by_grade = [];
try {
    $stmt = $pdo->query("
        SELECT
            s.id as section_id, s.grade_level, s.section_name,
            ss.id as subject_id, ss.subject_name, ss.teacher_name, ss.schedule, ss.time_slot, ss.room
        FROM sections s
        LEFT JOIN section_subjects ss ON s.id = ss.section_id
        ORDER BY s.grade_level, s.section_name, ss.subject_name /* Consider adding sorting for grade level (e.g., FIELD or CAST) if needed */
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group results by grade and then by section
    foreach ($results as $row) {
        if (!isset($sections_by_grade[$row['grade_level']])) {
            $sections_by_grade[$row['grade_level']] = [];
        }
        if (!isset($sections_by_grade[$row['grade_level']][$row['section_id']])) {
            $sections_by_grade[$row['grade_level']][$row['section_id']] = [
                'section_name' => $row['section_name'],
                'subjects' => []
            ];
        }
        if ($row['subject_id']) {
            // Store the subject details including teacher name directly
             $sections_by_grade[$row['grade_level']][$row['section_id']]['subjects'][] = $row;
        }
    }
    // Optional: Sort grades if needed (e.g., Kindergarten first, then numerically)
    // ksort($sections_by_grade); // Basic sort, might need custom logic for 'Kindergarten'

} catch (PDOException $e) {
    $error_message .= " Could not retrieve section/subject data: " . $e->getMessage(); // Append error
}

$grade_levels = ["Kindergarten", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"];

// --- Define Schedule Options ---
// Define common schedule patterns and standard time slots
// Fetch from DB or keep as array - ensure consistency
$schedule_options = [];
try {
    $stmt_sched = $pdo->query("SELECT schedule_key, display_text FROM schedule_patterns ORDER BY display_text");
    $schedule_options = $stmt_sched->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Error fetching schedule patterns: " . $e->getMessage());
    // Provide fallback options if DB fetch fails
    $schedule_options = [
        "MWF" => "Monday, Wednesday, Friday",
        "TTh" => "Tuesday, Thursday",
        "Mon" => "Monday Only",
    ];
    $error_message .= " Could not load schedule options from database.";
}

$time_slot_options = [];
try {
    $stmt_time = $pdo->query("SELECT time_value, display_text FROM time_slots ORDER BY start_minutes ASC");
    $time_slot_options = $stmt_time->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Error fetching time slots: " . $e->getMessage());
    // Provide fallback options if DB fetch fails
    $time_slot_options = [
        "540-600" => "9:00 AM - 10:00 AM",
        "600-660" => "10:00 AM - 11:00 AM",
    ];
     $error_message .= " Could not load time slot options from database.";
}
// --- END Definitions ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections & Subjects - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="css/manage_feedback.css"> 
    <style>
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff; /* Light blue when open */
            color: #0c63e4;
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, .25);
        }
        .add-subject-form .form-label-sm {
             font-size: 0.8rem;
             margin-bottom: 0.2rem;
        }
    </style>
</head>
<body>

<div class="page-container" style="max-width: 1400px;"> 
    <h2 class="page-title">Manage Sections and Subjects</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
         <div class="card-header"><h5 class="mb-0">Add New Section</h5></div>
         <div class="card-body">
             <form action="api/sections/handler.php" method="POST" class="row g-3 align-items-end">
                 <input type="hidden" name="action" value="add_section">
                 <div class="col-md-5">
                     <label for="grade_level_add" class="form-label">Grade Level</label>
                     <select id="grade_level_add" name="grade_level" class="form-select" required>
                         <option value="" disabled selected>Select a grade...</option>
                         <?php foreach ($grade_levels as $grade): ?>
                             <option value="<?= htmlspecialchars($grade) ?>"><?= is_numeric($grade) ? "Grade " . $grade : $grade ?></option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="col-md-5">
                     <label for="section_name" class="form-label">Section Name</label>
                     <input type="text" id="section_name" name="section_name" class="form-control" placeholder="e.g., Rose, Lily, Mabini" required>
                 </div>
                 <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add Section</button></div>
             </form>
         </div>
    </div>

    <h3 class="mt-5">Existing Sections</h3>
    <div class="accordion" id="sectionsAccordion">
        <?php if (empty($sections_by_grade)): ?>
            <p class="text-center text-muted">No sections created yet. Add a section above to get started.</p>
        <?php else: ?>
            <?php foreach ($sections_by_grade as $grade => $sections): ?>
                <div class="accordion-item mb-3">
                    <h2 class="accordion-header" id="heading-<?= htmlspecialchars(str_replace(' ', '', $grade)) ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars(str_replace(' ', '', $grade)) ?>">
                            <strong><?= is_numeric($grade) ? "Grade " . htmlspecialchars($grade) : htmlspecialchars($grade) ?></strong>
                        </button>
                    </h2>
                    <div id="collapse-<?= htmlspecialchars(str_replace(' ', '', $grade)) ?>" class="accordion-collapse collapse" data-bs-parent="#sectionsAccordion">
                        <div class="accordion-body">
                            <?php if (empty($sections)): ?>
                                <p>No sections defined for this grade level yet.</p>
                            <?php else: ?>
                                <?php foreach ($sections as $section_id => $section_data): ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Section: <?= htmlspecialchars($section_data['section_name']) ?></h6>
                                            <form action="api/sections/handler.php" method="POST" onsubmit="return confirm('WARNING: Deleting this section will also delete all subjects assigned to it. Are you sure?');">
                                                <input type="hidden" name="action" value="delete_section">
                                                <input type="hidden" name="section_id" value="<?= $section_id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i> Delete Section</button>
                                            </form>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title">Subjects in this Section</h6>
                                            <table class="table table-sm table-bordered table-striped">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Teacher</th>
                                                        <th>Schedule</th>
                                                        <th>Time</th>
                                                        <th>Room</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($section_data['subjects'])): ?>
                                                        <tr><td colspan="6" class="text-center">No subjects added yet.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($section_data['subjects'] as $subject): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                                                <td><?= htmlspecialchars($subject['teacher_name'] ?? 'N/A') ?></td> 
                                                                <td><?= htmlspecialchars($subject['schedule'] ?? 'N/A') ?></td>
                                                                <td><?= htmlspecialchars($time_slot_options[$subject['time_slot']] ?? $subject['time_slot'] ?? 'N/A') ?></td> <td><?= htmlspecialchars($subject['room'] ?? 'N/A') ?></td>
                                                                <td>
                                                                     <form action="api/sections/handler.php" method="POST" onsubmit="return confirm('Delete this subject assignment?');">
                                                                         <input type="hidden" name="action" value="delete_subject">
                                                                         <input type="hidden" name="subject_id" value="<?= $subject['subject_id'] ?>">
                                                                         <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                                                     </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                            <hr>
                                            
                                            <form action="api/sections/handler.php" method="POST" class="mt-3 add-subject-form">
                                                <input type="hidden" name="action" value="add_subject">
                                                <input type="hidden" name="section_id" value="<?= $section_id ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-3">
                                                         <label for="subject_name_<?= $section_id ?>" class="form-label form-label-sm">Subject Name*</label>
                                                         <input type="text" id="subject_name_<?= $section_id ?>" name="subject_name" class="form-control form-control-sm" placeholder="Subject Name" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                         <label for="teacher_name_<?= $section_id ?>" class="form-label form-label-sm">Teacher*</label>
                                                         <select id="teacher_name_<?= $section_id ?>" name="teacher_name" class="form-select form-select-sm" required>
                                                             <option value="">Select Teacher...</option>
                                                             <?php
                                                             // --- MODIFICATION: Filter teachers by current section's grade level ---
                                                             $teacherFoundForGrade = false;
                                                             foreach ($teacher_names_list as $teacher):
                                                                 // Check if the teacher's assigned grade matches the section's grade
                                                                 if (isset($teacher['grade_level_assigned']) && $teacher['grade_level_assigned'] == $grade) {
                                                                      $teacherFoundForGrade = true;
                                                             ?>
                                                                     <option value="<?= htmlspecialchars($teacher['teacher_name']) ?>"><?= htmlspecialchars($teacher['teacher_name']) ?></option>
                                                             <?php
                                                                 } // --- End IF condition ---
                                                             endforeach;
                                                             // --- END MODIFICATION ---

                                                             // Add message if no teachers found for this grade
                                                             if (!$teacherFoundForGrade):
                                                             ?>
                                                                <option value="" disabled>No teachers assigned to this grade level</option>
                                                             <?php endif; ?>
                                                         </select>
                                                    </div>
                                                    
                                                    <div class="col-md-2 col-lg-auto">
                                                         <label for="schedule_<?= $section_id ?>" class="form-label form-label-sm">Schedule*</label>
                                                         <select id="schedule_<?= $section_id ?>" name="schedule" class="form-select form-select-sm" required>
                                                             <option value="">Select Days...</option>
                                                             <?php foreach ($schedule_options as $value => $text): ?>
                                                                 <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($text) ?></option>
                                                             <?php endforeach; ?>
                                                         </select>
                                                    </div>
                                                    <div class="col-md-2 col-lg-auto">
                                                         <label for="time_slot_<?= $section_id ?>" class="form-label form-label-sm">Time Slot*</label>
                                                         <select id="time_slot_<?= $section_id ?>" name="time_slot" class="form-select form-select-sm" required>
                                                             <option value="">Select Time...</option>
                                                             <?php foreach ($time_slot_options as $value => $text): ?>
                                                                 <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($text) ?></option>
                                                             <?php endforeach; ?>
                                                         </select>
                                                    </div>
                                                    <div class="col-md-1">
                                                         <label for="room_<?= $section_id ?>" class="form-label form-label-sm">Room</label>
                                                         <input type="text" id="room_<?= $section_id ?>" name="room" class="form-control form-control-sm" placeholder="Room">
                                                    </div>
                                                    <div class="col-md-auto">
                                                         <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-plus"></i> Add Subject</button>
                                                    </div>
                                                </div>
                                            </form>
                                            
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                             <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="back-container mt-4">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000); // 5000 milliseconds = 5 seconds
    });
</script>
</body>
</html>