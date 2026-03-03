<?php
// Return session info (uses centralized session initializer)
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

if (isset($_SESSION['faculty_id'])) {
    echo json_encode([
        'isAuthenticated' => true,
        'userId' => $_SESSION['faculty_id'],
        'email' => $_SESSION['faculty_email'] ?? null,
        'displayName' => $_SESSION['faculty_display_name'] ?? null,
        'role' => $_SESSION['faculty_role'] ?? 'faculty'
    ]);
} else {
    echo json_encode(['isAuthenticated' => false, 'message' => 'No active session or faculty_id not set.']);
}
?>