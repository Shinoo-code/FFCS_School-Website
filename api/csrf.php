<?php
// Simple CSRF helper
// Usage: require_once __DIR__ . '/csrf.php'; then use csrf_input_field() in forms and validate_csrf_token()

// Use centralized session hardening
require_once __DIR__ . '/session.php';

if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Fallback to less-strong token if random_bytes is unavailable
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['csrf_token'] ?? '';
    }

    function csrf_input_field() {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    function validate_csrf_token($token) {
        if (!is_string($token) || $token === '') return false;
        $session = $_SESSION['csrf_token'] ?? '';
        // Use hash_equals to mitigate timing attacks
        return is_string($session) && hash_equals($session, $token);
    }
}

?>