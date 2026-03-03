<?php
// api/enrollments/download.php
// Returns a student's enrollment record as a downloadable JSON file (excludes uploaded files)
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/session.php';
require_once __DIR__ . '/../db_connect.php';

// Only allow logged-in users (admin or faculty)
$role = $_SESSION['faculty_role'] ?? null;
if (empty($_SESSION['faculty_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$id = $_GET['id'] ?? null;
if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM enrollments WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
        exit;
    }

    // Remove keys that look like uploaded files or paths
    foreach (array_keys($row) as $k) {
        if (preg_match('/(file|upload|path|image|photo|doc|attachment)/i', $k)) {
            unset($row[$k]);
        }
    }

    // Optionally remove large binary columns
    foreach ($row as $k => $v) {
        if (is_resource($v)) unset($row[$k]);
    }

    // Output as downloadable JSON
    $fn = 'enrollment_' . preg_replace('/[^0-9A-Za-z_-]/', '_', $id) . '.json';
    header('Content-Description: File Transfer');
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo json_encode(['success' => true, 'data' => $row], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
    exit;
}

?>
