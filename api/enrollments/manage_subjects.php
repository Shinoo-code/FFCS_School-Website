<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// --- Admin Authentication Check ---
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $enrollment_id = $_GET['enrollment_id'] ?? null;
    if (!$enrollment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enrollment ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE enrollment_id = :enrollment_id ORDER BY subject_name ASC");
        $stmt->execute([':enrollment_id' => $enrollment_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Get Subjects Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error fetching subjects.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>