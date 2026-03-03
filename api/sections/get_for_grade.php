<?php
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['faculty_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$grade_level = $_GET['grade_level'] ?? null;

if (!$grade_level) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Grade level is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, section_name FROM sections WHERE grade_level = ? ORDER BY section_name ASC");
    $stmt->execute([$grade_level]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'sections' => $sections]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>