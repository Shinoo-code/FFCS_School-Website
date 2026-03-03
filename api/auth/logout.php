<?php
// api/auth/logout.php
// Use centralized session initializer and require CSRF-protected POST
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

// Validate CSRF token (header or POST body)
$posted = $_POST;
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $posted['csrf_token'] ?? null;
if (!validate_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie (session params are handled by api/session.php)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Finally, destroy the session data on the server.
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logout successful.']);
exit;
?>