<?php
// Final-School-Web/api/auth/verify_otp_endpoint.php - UPDATED to use centralized session and CSRF validation

// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit;
}

// CSRF validation: accept token from header or POST body
$csrfFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
$csrfFromPost = $_POST['csrf_token'] ?? null;
$csrfToken = $csrfFromHeader ?: $csrfFromPost;
if (!validate_csrf_token($csrfToken)) {
    header("Location: ../../login.php?error=" . urlencode("Invalid CSRF token."));
    exit;
}

// 1. Retrieve data from POST and Session
$otp_code = trim($_POST['otp_code'] ?? '');
$session_otp = $_SESSION['otp_code'] ?? null;
$session_user_id = $_SESSION['otp_user_id'] ?? null;
$session_expiry = $_SESSION['otp_expiry'] ?? 0;

$error_redirect = "../../verify_otp.php?error=";

if ($otp_code === '') {
    header("Location: " . $error_redirect . urlencode("Verification code is required."));
    exit;
}

if (!$session_user_id || !$session_otp) {
    // Session was destroyed before POST was handled
    header("Location: ../../login.php?error=" . urlencode("Verification session expired. Please log in again."));
    exit;
}

// 2. Check Expiry
if (time() > $session_expiry) {
    // Clear temporary OTP data before redirect
    unset($_SESSION['otp_code'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry']);
    header("Location: ../../login.php?error=" . urlencode("Verification code expired. Please log in again."));
    exit;
}

// 3. Check OTP Match
if ($otp_code != $session_otp) {
    // Do NOT clear the session here so the user can try again
    header("Location: " . $error_redirect . urlencode("Invalid verification code."));
    exit;
}

// 4. Code is valid and not expired! Final step: Create the permanent user session.
try {
    $stmt = $pdo->prepare("SELECT id, email, display_name, role FROM faculty WHERE id = ?");
    $stmt->execute([$session_user_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($faculty) {
        // Clear temporary OTP data
        unset($_SESSION['otp_code'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry']);

        // Use central session helper to rotate session and set metadata
        if (function_exists('session_secure_login')) {
            session_secure_login($faculty['id'], $faculty['role']);
        } else {
            session_regenerate_id(true);
            $_SESSION['__last_activity'] = time();
        }

        // Backwards-compatible session keys
        $_SESSION['faculty_id'] = $faculty['id'];
        $_SESSION['faculty_email'] = $faculty['email'];
        $_SESSION['faculty_display_name'] = $faculty['display_name'] ?? $faculty['email'];
        $_SESSION['faculty_role'] = $faculty['role'];

        // Final Redirect to Dashboard
        header('Location: ../../dashboard.php');
        exit;
    } else {
        header("Location: ../../login.php?error=" . urlencode("User authorization failed after verification."));
        exit;
    }
} catch (PDOException $e) {
    error_log("Final OTP verification DB error: " . $e->getMessage());
    header("Location: ../../login.php?error=" . urlencode("A database error occurred during final login."));
    exit;
}