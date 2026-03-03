<?php
// api/enrollments/status.php
require_once '../db_connect.php'; // Ensure this path is correct

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lrnToFind = $_GET['lrn'] ?? null;

    if (empty($lrnToFind)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'LRN is required.']);
        exit;
    }

    try {
        // --- MODIFIED: Added e.admin_remarks ---
        $stmt = $pdo->prepare("SELECT
                                    e.id,
                                    e.lrn,
                                    e.student_first_name,
                                    e.student_middle_name,
                                    e.student_last_name,
                                    e.student_extension_name,
                                    e.status,
                                    e.section,
                                    e.school_year,
                                    e.grade_level,
                                    e.tuition_mode,
                                    e.installment_months,
                                    e.total_tuition,
                                    e.outstanding_balance,
                                    e.admin_remarks 
                               FROM enrollments e
                               WHERE e.lrn = :lrn 
                               ORDER BY e.submission_timestamp DESC 
                               LIMIT 1");
        // --- END MODIFIED ---
                               
        $stmt->bindParam(':lrn', $lrnToFind, PDO::PARAM_STR);
        $stmt->execute();
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enrollment) {
            $subjects = [];
            // Fetch subjects ONLY if the student is officially enrolled
            if (strtolower($enrollment['status']) === 'enrolled') {
                $stmt_subjects = $pdo->prepare("SELECT * FROM subjects WHERE enrollment_id = :enrollment_id ORDER BY subject_name ASC");
                $stmt_subjects->bindParam(':enrollment_id', $enrollment['id'], PDO::PARAM_INT);
                $stmt_subjects->execute();
                $subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);
            }

            // --- MODIFIED: Added admin_remarks to the response ---
            $studentData = [
                'id' => $enrollment['id'],
                'status' => $enrollment['status'] ?? 'N/A',
                'section' => $enrollment['section'] ?? 'N/A',
                'school_year' => $enrollment['school_year'] ?? 'N/A',
                'grade_level' => $enrollment['grade_level'] ?? 'N/A',
                'tuition_mode' => $enrollment['tuition_mode'],
                'installment_months' => $enrollment['installment_months'],
                'total_tuition' => $enrollment['total_tuition'],
                'outstanding_balance' => $enrollment['outstanding_balance'],
                'admin_remarks' => $enrollment['admin_remarks'], // <-- NEW
                'student' => [
                    'lrn' => $enrollment['lrn'],
                    'firstName' => $enrollment['student_first_name'] ?? '',
                    'middleName' => $enrollment['student_middle_name'] ?? '',
                    'lastName' => $enrollment['student_last_name'] ?? '',
                    'extensionName' => $enrollment['student_extension_name'] ?? ''
                ],
                'subjects' => $subjects // Add subjects to the response
            ];
            // --- END MODIFIED ---
            
            http_response_code(200); // OK
            echo json_encode($studentData);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => "No enrollment record found for LRN: " . htmlspecialchars($lrnToFind)]);
        }
    } catch (PDOException $e) {
        error_log("Database Error in status.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed for this endpoint.']);
}
?>