<?php
// enrollment_history.php
require_once __DIR__ . '/../api/db_connect.php'; // Use __DIR__ for reliable path
require_once __DIR__ . '/../api/session.php';

// Session Check - Redirect if not logged in or not admin
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    // Redirect to login or dashboard with an error
    header("Location: login.php?error=unauthorized");
    exit;
}

// --- Fetch Enrolled Students ---
$students_by_grade = [];
$error_message_fetch = '';
try {
    // Fetch students ordered by grade level (Kindergarten first, then numerically), then by name
    $stmt = $pdo->prepare("
        SELECT id, lrn, student_first_name, student_last_name, grade_level
        FROM enrollments
        WHERE status = 'Enrolled'
        ORDER BY
            CASE
                WHEN grade_level = 'Kindergarten' THEN 0
                WHEN grade_level REGEXP '^[0-9]+$' THEN CAST(grade_level AS UNSIGNED)
                ELSE 99 -- Place non-numeric grades (excluding Kindergarten) last
            END,
            student_last_name, student_first_name
    ");
    $stmt->execute();
    $enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group students by grade level
    foreach ($enrolled_students as $student) {
        $grade = $student['grade_level'];
        if (!isset($students_by_grade[$grade])) {
            $students_by_grade[$grade] = [];
        }
        $students_by_grade[$grade][] = $student;
    }

    // Note: The sorting is now handled by the SQL query's ORDER BY clause.

} catch (PDOException $e) {
    error_log("Error fetching enrolled students: " . $e->getMessage());
    $error_message_fetch = "Database error: Could not load student list.";
}

// Feedback messages from GET parameters (e.g., after add/update/delete)
$success_message_get = $_GET['success'] ?? '';
$error_message_get = $_GET['error'] ?? '';
$error_message = $error_message_fetch ?: $error_message_get; // Prioritize fetch error

// Helper function to create safe IDs for HTML attributes (like accordion controls)
function create_safe_id($input) {
    // Remove non-alphanumeric characters and replace spaces/underscores with hyphens
    $safe = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $input);
    $safe = preg_replace('/[\s_]+/', '-', strtolower($safe));
    return 'grade-' . $safe; // Prefix to ensure valid ID
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment History Management - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/enrollment_history.css"/>
    <link rel="stylesheet" href="../css/dashboard.css"/>
    <style>
        /* Add minor style adjustments if needed, or put them in enrollment_history.css */
        body { background-color: #f8f9fa; }
        .page-container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        /* Style for the search input */
        .search-container { max-width: 400px; margin-bottom: 1.5rem; }
        .no-results-message { display: none; /* Hidden by default */ }
    </style>
</head>
<body>

    <div class="page-container">
        <h2 class="page-title">Enrollment History Management</h2>

        <div class="message-area mb-3">
            <?php if ($success_message_get): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars(urldecode($success_message_get)) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars(urldecode($error_message)) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <div class="search-container">
            <label for="studentSearchInput" class="form-label">Search Student by LRN:</label>
            <input type="text" id="studentSearchInput" class="form-control" placeholder="Enter LRN...">
        </div>

        <h3 class="section-title">Enrolled Students by Grade Level</h3>

        <p id="noResultsMessage" class="text-center text-muted mt-4 no-results-message">No students found matching your search.</p>

        <div class="accordion" id="studentGradeAccordion">
            <?php if (empty($students_by_grade) && !$error_message_fetch): ?>
                <p class="text-center text-muted mt-4">No enrolled students found.</p>
            <?php elseif (!empty($students_by_grade)): ?>
                <?php foreach ($students_by_grade as $grade => $students):
                    $grade_id = create_safe_id($grade); // Use helper function for safe ID
                ?>
                    <div class="accordion-item mb-2" data-grade="<?= htmlspecialchars($grade) ?>">
                        <h2 class="accordion-header" id="heading-<?= $grade_id ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $grade_id ?>" aria-expanded="false" aria-controls="collapse-<?= $grade_id ?>">
                                <strong>Grade Level: <?= htmlspecialchars($grade) ?></strong>&nbsp;(<span class="student-count"><?= count($students) ?></span>&nbsp;Student<?= count($students) > 1 ? 's' : '' ?>)
                            </button>
                        </h2>
                        <div id="collapse-<?= $grade_id ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $grade_id ?>" data-bs-parent="#studentGradeAccordion">
                            <div class="accordion-body">
                                <div class="table-container table-responsive">
                                    <table class="data-table table table-striped table-hover table-sm mb-0 student-table"> 
                                        <thead>
                                            <tr>
                                                <th>LRN</th>
                                                <th>Last Name</th>
                                                <th>First Name</th>
                                                <th style="width: 250px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr class="student-row" data-lrn="<?= htmlspecialchars($student['lrn'] ?? '') ?>">
                                                    <td><?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($student['student_last_name']) ?></td>
                                                    <td><?= htmlspecialchars($student['student_first_name']) ?></td>
                                                    <td class="action-buttons">
                                                        <button class="btn btn-sm btn-info view-history-btn"
                                                                data-enrollment-id="<?= $student['id'] ?>"
                                                                data-student-name="<?= htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']) ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                            <button class="btn btn-sm btn-success add-history-btn"
                                                                    data-enrollment-id="<?= $student['id'] ?>"
                                                                    data-student-name="<?= htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']) ?>">
                                                                <i class="fas fa-plus"></i> Add
                                                            </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                             <tr class="no-grade-results-row" style="display: none;">
                                                <td colspan="4" class="text-center text-muted">No students found in this grade for the current search.</td>
                                             </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="back-container mt-4">
            <a href="../dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="modal fade" id="addHistoryModal" tabindex="-1" aria-labelledby="addHistoryModalLabel" aria-hidden="true">

         <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addHistoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addHistoryModalLabel">Add Enrollment History for <span id="addModalStudentName"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body modal-body-scrollable">
                        <input type="hidden" name="action" value="add_history">
                        <input type="hidden" id="addEnrollmentId" name="enrollment_id">
                        <div class="alert alert-danger" id="addHistoryError" style="display: none;"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="addSchoolYear" class="form-label form-label-sm">School Year*</label>
                                <input type="text" class="form-control form-control-sm" id="addSchoolYear" name="school_year" placeholder="YYYY-YYYY" required pattern="\d{4}-\d{4}" title="Format: YYYY-YYYY">
                            </div>
                            <div class="col-md-6">
                                <label for="addGradeLevel" class="form-label form-label-sm">Grade Level*</label>
                                <select class="form-select form-select-sm" id="addGradeLevel" name="grade_level" required>
                                    <option value="" selected disabled>Select Grade Level...</option>
                                    <option value="Kindergarten">Kindergarten</option>
                                    <option value="1">Grade 1</option>
                                    <option value="2">Grade 2</option>
                                    <option value="3">Grade 3</option>
                                    <option value="4">Grade 4</option>
                                    <option value="5">Grade 5</option>
                                    <option value="6">Grade 6</option>
                                    <option value="7">Grade 7</option>
                                    <option value="8">Grade 8</option>
                                    <option value="9">Grade 9</option>
                                    <option value="10">Grade 10</option>
                                    <option value="11">Grade 11</option>
                                    <option value="12">Grade 12</option>
                                </select>
                            </div>
                             <div class="col-md-12">
                                <label for="addSubject" class="form-label form-label-sm">Subject*</label>
                                <input type="text" class="form-control form-control-sm" id="addSubject" name="subject" required>
                            </div>
                            <div class="col-md-12">
                                <label for="addDescription" class="form-label form-label-sm">Description (Optional)</label>
                                <textarea class="form-control form-control-sm" id="addDescription" name="description" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="addGrade" class="form-label form-label-sm">Grade (Numerical)</label>
                                <input type="number" class="form-control form-control-sm" id="addGrade" name="grade" min="0" max="100" step="1">
                            </div>
                            <div class="col-md-4">
                                <label for="addTeacher" class="form-label form-label-sm">Teacher</label>
                                <input type="text" class="form-control form-control-sm" id="addTeacher" name="teacher_name">
                            </div>
                            <div class="col-md-4">
                                <label for="addRemarks" class="form-label form-label-sm">Remarks*</label>
                                <select class="form-select form-select-sm" id="addRemarks" name="remarks" required>
                                    <option value="" selected disabled>Select...</option>
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                    <option value="Incomplete">Incomplete</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewHistoryModal" tabindex="-1" aria-labelledby="viewHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewHistoryModalLabel">Enrollment History for <span id="viewModalStudentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-scrollable">
                    <div class="alert alert-danger" id="viewHistoryError" style="display: none;"></div>
                    <div id="historyRecordsContainer">
                        <p class="text-center">Loading history...</p>
                    </div>
                    <form id="editHistoryFormTemplate" style="display: none;" class="mt-3 p-3 border rounded bg-light edit-form">
                        <h6><i class="fas fa-pencil-alt"></i> Edit Record</h6>
                        <input type="hidden" name="action" value="update_history">
                        <input type="hidden" class="edit-history-id" name="history_id">
                        <input type="hidden" class="edit-enrollment-id" name="enrollment_id"> 
                        <div class="row g-2">
                            <div class="col-md-2"><label class="form-label form-label-sm">SY:</label><input type="text" name="school_year" class="form-control form-control-sm edit-school-year" required pattern="\d{4}-\d{4}" title="Format: YYYY-YYYY"></div>
                            <div class="col-md-2"><label class="form-label form-label-sm">Grade Lvl:</label><input type="text" name="grade_level" class="form-control form-control-sm edit-grade-level" required></div>
                            <div class="col-md-3"><label class="form-label form-label-sm">Subject:</label><input type="text" name="subject" class="form-control form-control-sm edit-subject" required></div>
                            <div class="col-md-5"><label class="form-label form-label-sm">Description:</label><input type="text" name="description" class="form-control form-control-sm edit-description"></div>
                            <div class="col-md-2"><label class="form-label form-label-sm">Grade:</label><input type="number" name="grade" class="form-control form-control-sm edit-grade" min="0" max="100"></div>
                            <div class="col-md-3"><label class="form-label form-label-sm">Teacher:</label><input type="text" name="teacher_name" class="form-control form-control-sm edit-teacher"></div>
                            <div class="col-md-3"><label class="form-label form-label-sm">Remarks:</label><select name="remarks" class="form-select form-select-sm edit-remarks" required><option value="Passed">Passed</option><option value="Failed">Failed</option><option value="Incomplete">Incomplete</option></select></div>
                            <div class="col-md-4 align-self-end text-end action-buttons">
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                <button type="button" class="btn btn-sm btn-secondary cancel-edit-btn">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Ensure script runs only once even if included multiple times
    if (typeof enrollmentHistoryScriptLoaded === 'undefined') {
        const enrollmentHistoryScriptLoaded = true;

        document.addEventListener('DOMContentLoaded', function() {
            // Get modal elements and instances
            const addModalEl = document.getElementById('addHistoryModal');
            const viewModalEl = document.getElementById('viewHistoryModal');
            const addModal = (typeof bootstrap !== 'undefined' && addModalEl) ? new bootstrap.Modal(addModalEl) : null;
            const viewModal = (typeof bootstrap !== 'undefined' && viewModalEl) ? new bootstrap.Modal(viewModalEl) : null;

            // Elements for Add Modal
            const addForm = document.getElementById('addHistoryForm');
            const addEnrollmentIdInput = document.getElementById('addEnrollmentId');
            const addModalStudentNameSpan = document.getElementById('addModalStudentName');
            const addHistoryErrorDiv = document.getElementById('addHistoryError');

            // Elements for View/Edit Modal
            const viewModalStudentNameSpan = document.getElementById('viewModalStudentName');
            const historyRecordsContainer = document.getElementById('historyRecordsContainer');
            const viewHistoryErrorDiv = document.getElementById('viewHistoryError');
            const editFormTemplate = document.getElementById('editHistoryFormTemplate');

            // --- Search Elements ---
            const searchInput = document.getElementById('studentSearchInput');
            const accordion = document.getElementById('studentGradeAccordion');
            const noResultsMessage = document.getElementById('noResultsMessage');

            // --- Event Listener: Open "Add History" Modal ---
            document.querySelectorAll('.add-history-btn').forEach(button => {
               button.addEventListener('click', function() {
                    if(!addModal || !addEnrollmentIdInput || !addModalStudentNameSpan || !addForm) return;
                    addEnrollmentIdInput.value = this.dataset.enrollmentId;
                    addModalStudentNameSpan.textContent = this.dataset.studentName;
                    if(addHistoryErrorDiv) addHistoryErrorDiv.style.display = 'none';
                    addForm.reset();
                    addModal.show();
                });
            });

            // --- Event Listener: Submit "Add History" Form ---
             if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if(addHistoryErrorDiv) addHistoryErrorDiv.style.display = 'none';
                    const formData = new FormData(addForm);
                    const submitButton = addForm.querySelector('button[type="submit"]');
                    if(submitButton) submitButton.disabled = true;

                    fetch('api/enrollment_history_handler.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if(addModal) addModal.hide();
                            // Refresh the page with success message
                            window.location.search = `?success=${encodeURIComponent(data.message || 'History record added successfully!')}`;
                        } else { throw new Error(data.message || 'Failed to add history record.'); }
                    })
                    .catch(error => {
                        console.error('Add History Error:', error);
                        if(addHistoryErrorDiv) { addHistoryErrorDiv.textContent = error.message; addHistoryErrorDiv.style.display = 'block'; }
                    })
                    .finally(() => { if(submitButton) submitButton.disabled = false; });
                });
            }

            // --- Event Listener: Open "View History" Modal ---
            document.querySelectorAll('.view-history-btn').forEach(button => {
                button.addEventListener('click', function() {
                     if(!viewModal || !viewModalStudentNameSpan || !historyRecordsContainer) return;
                    viewModalStudentNameSpan.textContent = this.dataset.studentName;
                    historyRecordsContainer.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading history...</p>';
                    if(viewHistoryErrorDiv) viewHistoryErrorDiv.style.display = 'none';
                    viewModal.show();
                    loadHistoryRecords(this.dataset.enrollmentId);
                });
            });

            // --- Function: Load History Records via Fetch ---
            function loadHistoryRecords(enrollmentId) {
                 if(!historyRecordsContainer) return;
                 fetch(`api/enrollment_history_handler.php?action=get_history&enrollment_id=${enrollmentId}`)
                .then(response => { if (!response.ok) { return response.json().then(err => { throw new Error(err.message || `HTTP error ${response.status}`); }); } return response.json(); })
                .then(data => { if (data.success && Array.isArray(data.history)) { renderHistoryTable(enrollmentId, data.history); } else { throw new Error(data.message || 'Could not load history.'); } })
                .catch(error => { console.error('Load History Error:', error); historyRecordsContainer.innerHTML = `<p class="text-danger text-center">Error loading history: ${error.message}</p>`; });
            }

            // --- *** MODIFIED: Function: Render History GROUPED BY GRADE in View Modal *** ---
             function renderHistoryTable(enrollmentId, historyData) {
                 if (!historyRecordsContainer) return;
                 historyRecordsContainer.querySelectorAll('.edit-form-instance').forEach(form => form.remove()); // Clear any open edit forms

                 if (historyData.length === 0) {
                     historyRecordsContainer.innerHTML = '<p class="text-center text-muted">No history records found for this student.</p>';
                     return;
                 }

                 // --- Group records by Grade Level ---
                 const groupedByGrade = historyData.reduce((acc, record) => {
                     const grade = record.grade_level || 'Unknown Grade';
                     if (!acc[grade]) {
                         acc[grade] = [];
                     }
                     acc[grade].push(record);
                     return acc;
                 }, {});
                 // --- End Grouping ---

                 // --- Sort Grades (Optional: Kindergarten first, then numerically) ---
                 const sortedGrades = Object.keys(groupedByGrade).sort((a, b) => {
                     if (a === 'Kindergarten') return -1;
                     if (b === 'Kindergarten') return 1;
                     const numA = parseInt(a);
                     const numB = parseInt(b);
                     if (!isNaN(numA) && !isNaN(numB)) {
                         return numA - numB;
                     }
                     // Fallback for non-numeric/non-Kindergarten grades
                     return a.localeCompare(b);
                 });
                 // --- End Sorting ---


                 let accordionHtml = '<div class="accordion" id="historyGradeAccordion">';

                 sortedGrades.forEach((grade, index) => {
                     const recordsForGrade = groupedByGrade[grade];
                     const gradeIdSafe = `history-grade-${grade.replace(/[^a-zA-Z0-9]/g, '-')}-${index}`; // Unique ID for accordion
                     const isFirstGrade = index === 0; // Show the first grade expanded by default

                     accordionHtml += `
                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header" id="heading-${gradeIdSafe}">
                                <button class="accordion-button ${isFirstGrade ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${gradeIdSafe}" aria-expanded="${isFirstGrade ? 'true' : 'false'}" aria-controls="collapse-${gradeIdSafe}">
                                    <strong>Grade Level: ${grade}</strong>
                                </button>
                            </h2>
                            <div id="collapse-${gradeIdSafe}" class="accordion-collapse collapse ${isFirstGrade ? 'show' : ''}" aria-labelledby="heading-${gradeIdSafe}" data-bs-parent="#historyGradeAccordion">
                                <div class="accordion-body p-2">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover table-sm">
                                            <thead>
                                                <tr>
                                                    <th>SY</th>
                                                    <th>Subject</th>
                                                    <th>Description</th>
                                                    <th>Grade</th>
                                                    <th>Teacher</th>
                                                    <th>Remarks</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;

                     recordsForGrade.forEach(record => {
                         const escapedRecordJson = JSON.stringify(record).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
                         accordionHtml += `<tr data-history-id="${record.id}">
                                             <td>${record.school_year || ''}</td>
                                             <td>${record.subject || ''}</td>
                                             <td>${record.description || ''}</td>
                                             <td>${record.grades || ''}</td>
                                             <td>${record.teacher_name || ''}</td>
                                             <td>${record.remarks || ''}</td>
                                             <td class="action-buttons">
                                                 <button class="btn btn-sm btn-primary edit-history-btn" data-record='${escapedRecordJson}' title="Edit"><i class="fas fa-edit"></i> Edit</button>
                                                 <button class="btn btn-sm btn-danger delete-history-btn" data-history-id="${record.id}" title="Delete"><i class="fas fa-trash"></i> Delete</button>
                                             </td>
                                           </tr>`;
                     });

                     accordionHtml += `</tbody></table></div></div></div></div>`; // Close table, accordion body, collapse, item
                 }); // End loop through grades

                 accordionHtml += '</div>'; // Close accordion container
                 historyRecordsContainer.innerHTML = accordionHtml;
                 attachViewModalActionListeners(enrollmentId); // Re-attach listeners for edit/delete
            }
            // --- *** END MODIFIED FUNCTION *** ---

            // --- Function: Attach Event Listeners for View Modal Buttons (Edit/Delete) ---
             function attachViewModalActionListeners(enrollmentId) {
                if (!historyRecordsContainer) return;
                // Use event delegation on the container for dynamically added buttons
                historyRecordsContainer.removeEventListener('click', handleHistoryActionClick); // Remove previous listener
                historyRecordsContainer.addEventListener('click', function(event) {
                    handleHistoryActionClick(event, enrollmentId);
                });
             }

            // --- NEW: Consolidated handler for Edit/Delete clicks via delegation ---
            function handleHistoryActionClick(event, enrollmentId) {
                const editButton = event.target.closest('.edit-history-btn');
                const deleteButton = event.target.closest('.delete-history-btn');

                if (editButton) {
                    handleEditClick(editButton, enrollmentId);
                } else if (deleteButton) {
                    handleDeleteClick(deleteButton, enrollmentId);
                }
            }
            // --- END NEW ---

            // --- Function: Handle Edit Button Click ---
            function handleEditClick(button, enrollmentId) {
                // Find any currently open edit form within the history container and remove it
                const activeEditForm = historyRecordsContainer.querySelector('.edit-form-instance');
                if (activeEditForm) {
                    const associatedRow = activeEditForm.previousElementSibling; // The TR above the form's TR
                    if (associatedRow && associatedRow.tagName === 'TR') {
                        associatedRow.style.display = ''; // Show the original row again
                    }
                    activeEditForm.remove(); // Remove the TR containing the form
                }

                // Parse data and show the new form
                try {
                    const recordData = JSON.parse(button.dataset.record.replace(/&apos;/g, "'").replace(/&quot;/g, '"'));
                    const row = button.closest('tr');
                    showEditForm(row, recordData, enrollmentId);
                } catch (e) {
                    console.error("Error parsing record data for edit:", e, button.dataset.record);
                    if(viewHistoryErrorDiv) { viewHistoryErrorDiv.textContent = "Error loading data for editing."; viewHistoryErrorDiv.style.display = 'block'; }
                }
            }

            // --- Function: Handle Delete Button Click ---
            function handleDeleteClick(button, enrollmentId) {
                const historyId = button.dataset.historyId;
                if (confirm('Are you sure you want to delete this history record? This cannot be undone.')) {
                    deleteHistoryRecord(historyId, enrollmentId);
                }
            }

            // --- Function: Show Edit Form Below Table Row ---
             function showEditForm(tableRow, recordData, enrollmentId) {
                 if (!editFormTemplate || !tableRow) return;
                 tableRow.style.display = 'none'; // Hide the data row
                 // Clone the template form
                 const editFormInstance = editFormTemplate.cloneNode(true);
                 editFormInstance.removeAttribute('id'); // Remove template ID
                 editFormInstance.style.display = 'block'; // Make the form visible
                 editFormInstance.classList.add('edit-form-instance'); // Add class for identification

                 // Populate form fields
                 editFormInstance.querySelector('.edit-history-id').value = recordData.id;
                 editFormInstance.querySelector('.edit-enrollment-id').value = enrollmentId;
                 editFormInstance.querySelector('.edit-school-year').value = recordData.school_year || '';
                 editFormInstance.querySelector('.edit-grade-level').value = recordData.grade_level || '';
                 editFormInstance.querySelector('.edit-subject').value = recordData.subject || '';
                 editFormInstance.querySelector('.edit-description').value = recordData.description || '';
                 editFormInstance.querySelector('.edit-grade').value = recordData.grades ?? ''; // Use ?? for null/undefined -> ''
                 editFormInstance.querySelector('.edit-teacher').value = recordData.teacher_name || '';
                 editFormInstance.querySelector('.edit-remarks').value = recordData.remarks || '';

                 // Create a new table row and cell to hold the form
                 const formRow = document.createElement('tr');
                 formRow.classList.add('edit-form-instance'); // Add class for identification
                 const formCell = document.createElement('td');
                 formCell.colSpan = tableRow.cells.length; // Make cell span all columns
                 formCell.style.padding = '0'; // Remove cell padding
                 formCell.style.border = 'none'; // Remove cell border
                 formCell.appendChild(editFormInstance); // Add the form to the cell
                 formRow.appendChild(formCell); // Add the cell to the row

                 // Insert the form row after the hidden data row
                 tableRow.insertAdjacentElement('afterend', formRow);

                 // Add event listeners for the form's buttons
                 editFormInstance.querySelector('.cancel-edit-btn').addEventListener('click', () => {
                     formRow.remove(); // Remove the form row
                     tableRow.style.display = ''; // Show the original data row again
                 });
                 editFormInstance.addEventListener('submit', function(e) {
                     e.preventDefault();
                     submitEditForm(editFormInstance, enrollmentId);
                 });
            }

            // --- Function: Submit Edit Form Data ---
             function submitEditForm(formElement, enrollmentId) {
                const formData = new FormData(formElement);
                const submitButton = formElement.querySelector('button[type="submit"]');
                if(submitButton) submitButton.disabled = true;
                if(viewHistoryErrorDiv) viewHistoryErrorDiv.style.display = 'none';

                fetch('api/enrollment_history_handler.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadHistoryRecords(enrollmentId); // Reload the history view after successful update
                    } else { throw new Error(data.message || 'Failed to update record.'); }
                })
                .catch(error => {
                    console.error('Update History Error:', error);
                    if(viewHistoryErrorDiv) { viewHistoryErrorDiv.textContent = `Update Error: ${error.message}`; viewHistoryErrorDiv.style.display = 'block'; }
                    // Re-enable button only if the form still exists in the DOM
                    const stillExistsForm = document.contains(formElement);
                    if(submitButton && stillExistsForm) submitButton.disabled = false;
                });
            }

            // --- Function: Delete History Record ---
            function deleteHistoryRecord(historyId, enrollmentId) {
                 if(viewHistoryErrorDiv) viewHistoryErrorDiv.style.display = 'none';
                 fetch('api/enrollment_history_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}, // Send as form data
                    body: `action=delete_history&history_id=${historyId}`
                 })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadHistoryRecords(enrollmentId); // Reload history after successful delete
                    } else { throw new Error(data.message || 'Failed to delete record.'); }
                })
                .catch(error => {
                    console.error('Delete History Error:', error);
                    if(viewHistoryErrorDiv) { viewHistoryErrorDiv.textContent = `Delete Error: ${error.message}`; viewHistoryErrorDiv.style.display = 'block'; }
                });
            }

            // --- Auto-hide alerts shown on page load ---
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => { setTimeout(() => { if (typeof bootstrap !== 'undefined' && bootstrap.Alert) { const bsAlert = bootstrap.Alert.getOrCreateInstance(alert); if(bsAlert) bsAlert.close(); } else { alert.style.display = 'none'; } }, 5000); });

            // --- Search Functionality ---
            if (searchInput && accordion && noResultsMessage) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let totalMatches = 0;

                    // Iterate through each accordion item (grade level on main page)
                    accordion.querySelectorAll('.accordion-item').forEach(item => {
                        const studentTable = item.querySelector('.student-table tbody');
                        if (!studentTable) return;

                        const studentRows = studentTable.querySelectorAll('.student-row');
                        const noGradeResultsRow = studentTable.querySelector('.no-grade-results-row');
                        let gradeMatches = 0;

                        // Filter students within this grade
                        studentRows.forEach(row => {
                            const lrn = row.dataset.lrn?.toLowerCase() || '';
                            // *** MODIFIED: Search only by LRN ***
                            if (searchTerm === '' || (lrn && lrn.includes(searchTerm))) {
                                row.style.display = ''; // Show row
                                gradeMatches++;
                                totalMatches++;
                            } else {
                                row.style.display = 'none'; // Hide row
                            }
                        });

                        // Show/hide the "no results in this grade" message
                        if (noGradeResultsRow) {
                            noGradeResultsRow.style.display = (gradeMatches === 0 && searchTerm !== '') ? '' : 'none';
                        }

                        // Show/hide the entire accordion item based on matches
                        item.style.display = (gradeMatches > 0 || searchTerm === '') ? '' : 'none';
                    });

                    // Show/hide the overall "No results" message
                    noResultsMessage.style.display = (totalMatches === 0 && searchTerm !== '') ? 'block' : 'none';
                });
            } else {
                console.warn("Search input, accordion, or noResultsMessage element not found. Search functionality disabled.");
            }
            // --- END Search ---

        }); // End DOMContentLoaded
    } // End script load check
</script>

</body>
</html>