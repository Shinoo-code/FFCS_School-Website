<?php
// api/enrollments/delete.php
session_start(); // Or your standard session/auth include

require_once '../db_connect.php'; // Ensure correct path to your DB connection

header('Content-Type: application/json');

// --- Authentication Check (Ensure only authorized faculty/admin can delete) ---
if (!isset($_SESSION['faculty_id'])) { // Adjust based on your session variable
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// --- Optional: Role Check (e.g., only 'admin' can delete) ---
// if ($_SESSION['faculty_role'] !== 'admin') {
//     http_response_code(403); // Forbidden
//     echo json_encode(['success' => false, 'message' => 'You do not have permission to delete enrollments.']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $enrollmentId = $input['id'] ?? null;

    if (empty($enrollmentId)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Enrollment ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = :id");
        $stmt->bindParam(':id', $enrollmentId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Enrollment application deleted successfully.']);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['success' => false, 'message' => 'Enrollment application not found or already deleted.']);
            }
        } else {
            http_response_code(500); // Internal Server Error
            $errorInfo = $stmt->errorInfo();
            error_log("DB Execute Failed (Delete Enrollment): " . print_r($errorInfo, true));
            echo json_encode(['success' => false, 'message' => 'Database execution failed while deleting.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Delete Enrollment PDOException: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed for this action.']);
}
?>