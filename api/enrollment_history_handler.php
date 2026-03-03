<?php
// api/enrollment_history_handler.php
require_once __DIR__ . '/db_connect.php'; // same directory

// --- IMPORTANT: Suppress direct error output for API ---
error_reporting(0); // Turn off direct error display
ini_set('display_errors', 0); // Ensure it's off

// --- IMPORTANT: Set Content-Type BEFORE any output ---
header('Content-Type: application/json');

// Centralized session initialization and CSRF helper (same folder)
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';

// --- Admin Authentication Check ---
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit; // --- Added exit ---
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'get_history') {
        // --- Fetch History Records ---
        $enrollment_id = $_GET['enrollment_id'] ?? null;
        if (!$enrollment_id) {
            throw new Exception("Enrollment ID is required.");
        }

        $stmt = $pdo->prepare("SELECT * FROM enrollment_history WHERE enrollment_id = :enrollment_id ORDER BY school_year DESC, id DESC");
        $stmt->execute([':enrollment_id' => $enrollment_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'history' => $history]);
        exit; // --- Added exit ---

    } elseif ($method === 'POST') {
        // --- Handle POST Actions (Add, Update, Delete) ---
        switch ($action) {
            case 'add_history':
                $enrollment_id = $_POST['enrollment_id'] ?? null;
                $school_year = trim($_POST['school_year'] ?? '');
                $grade_level = trim($_POST['grade_level'] ?? '');
                $subject = trim($_POST['subject'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $grade = $_POST['grade'] ?? null; // Keep as null if empty string
                $teacher_name = trim($_POST['teacher_name'] ?? '');
                $remarks = $_POST['remarks'] ?? null;

                // Basic Validation
                if (!$enrollment_id || empty($school_year) || empty($grade_level) || empty($subject) || empty($remarks)) {
                    throw new Exception("School Year, Grade Level, Subject, and Remarks are required.");
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO enrollment_history (enrollment_id, school_year, grade_level, subject, description, grades, teacher_name, remarks)
                     VALUES (:enroll_id, :sy, :gl, :subj, :desc, :grd, :teacher, :rem)"
                );
                $stmt->execute([
                    ':enroll_id' => $enrollment_id,
                    ':sy' => $school_year,
                    ':gl' => $grade_level,
                    ':subj' => $subject,
                    ':desc' => empty($description) ? null : $description,
                    // Handle empty string grade input correctly
                    ':grd' => ($grade === '' || $grade === null) ? null : $grade,
                    ':teacher' => empty($teacher_name) ? null : $teacher_name,
                    ':rem' => $remarks
                ]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'History record added successfully.']);
                } else {
                    // Log the actual DB error if possible, but throw a generic message
                    error_log("Failed to insert history record. PDO Error: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to insert history record. Database error.");
                }
                exit; // --- Added exit ---

            case 'update_history':
                 $history_id = $_POST['history_id'] ?? null;
                 $enrollment_id = $_POST['enrollment_id'] ?? null; // Keep for context/reload
                 $school_year = trim($_POST['school_year'] ?? '');
                 $grade_level = trim($_POST['grade_level'] ?? '');
                 $subject = trim($_POST['subject'] ?? '');
                 $description = trim($_POST['description'] ?? '');
                 $grade = $_POST['grade'] ?? null; // Keep null if empty
                 $teacher_name = trim($_POST['teacher_name'] ?? '');
                 $remarks = $_POST['remarks'] ?? null;

                 if (!$history_id || !$enrollment_id || empty($school_year) || empty($grade_level) || empty($subject) || empty($remarks)) {
                     throw new Exception("Missing required fields for update.");
                 }

                 $stmt = $pdo->prepare(
                     "UPDATE enrollment_history SET
                          school_year = :sy, grade_level = :gl, subject = :subj, description = :desc,
                          grades = :grd, teacher_name = :teacher, remarks = :rem, last_updated = NOW()
                      WHERE id = :hist_id AND enrollment_id = :enroll_id" // Ensure it matches enrollment ID too
                 );
                 $stmt->execute([
                     ':sy' => $school_year, ':gl' => $grade_level, ':subj' => $subject,
                     ':desc' => empty($description) ? null : $description,
                     ':grd' => ($grade === '' || $grade === null) ? null : $grade, // Handle empty string grade
                     ':teacher' => empty($teacher_name) ? null : $teacher_name,
                     ':rem' => $remarks,
                     ':hist_id' => $history_id,
                     ':enroll_id' => $enrollment_id
                 ]);

                // rowCount() might be 0 if no data actually changed, even if query was successful.
                // It's better to just assume success if no exception was thrown.
                echo json_encode(['success' => true, 'message' => 'History record update processed.']);
                exit; // --- Added exit ---

            case 'delete_history':
                $history_id = $_POST['history_id'] ?? null;
                if (!$history_id) {
                    throw new Exception("History ID is required for deletion.");
                }

                $stmt = $pdo->prepare("DELETE FROM enrollment_history WHERE id = :hist_id");
                $stmt->execute([':hist_id' => $history_id]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'History record deleted.']);
                } else {
                    // It's not necessarily an error if rowCount is 0 (already deleted)
                    echo json_encode(['success' => true, 'message' => 'Record not found or already deleted.']);
                    // throw new Exception("Record not found or already deleted."); // Or keep throwing exception if needed
                }
                exit; // --- Added exit ---

            default:
                throw new Exception("Invalid action specified.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Enrollment History Handler PDO Error: " . $e->getMessage()); // Log detailed error
    echo json_encode(['success' => false, 'message' => 'Database operation error. Please check server logs.']); // Generic message to client
    exit; // --- Added exit ---
} catch (Exception $e) {
    http_response_code(400); // Bad Request for general validation errors
    error_log("Enrollment History Handler Error: " . $e->getMessage()); // Log detailed error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]); // Send specific error message
    exit; // --- Added exit ---
}
?>