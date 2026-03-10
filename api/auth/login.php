<?php
// Final-School-Web/api/auth/login.php - FINAL STABILIZATION FIX

// --- 1. CRITICAL: Start output buffering immediately ---
ob_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// ... (CORS headers unchanged) ...

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../utilities/send_email_otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    header('Content-Type: application/json');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;

    // Accept CSRF token either via X-CSRF-Token header or in the JSON body
    $csrfFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $csrfFromBody = $input['csrf_token'] ?? null;
    $csrfToken = $csrfFromHeader ?: $csrfFromBody;

    if (!validate_csrf_token($csrfToken)) {
        http_response_code(403);
        $response = ['success' => false, 'message' => 'Invalid CSRF token.'];
        goto send_response;
    }

    if (empty($email) || empty($password)) {
        http_response_code(400); 
        $response = ['success' => false, 'message' => 'Email and password are required.'];
        goto send_response; // Use goto to jump to the cleanup block
    }

    try {
        $stmt = $pdo->prepare("SELECT id, email, password_hash, display_name, role, 2fa_enabled FROM faculty WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$faculty || !password_verify($password, $faculty['password_hash'])) {
            http_response_code(401); 
            $response = ['success' => false, 'message' => 'Invalid email or password.'];
            goto send_response;
        }

        if ($faculty['role'] !== 'admin') {
            http_response_code(403); 
            $response = ['success' => false, 'message' => 'Access Denied. You do not have permission to log in.'];
            goto send_response;
        }
        
        // --- FORCED EMAIL OTP LOGIC ---
        if ($faculty['2fa_enabled'] == 1) {
            $otp_code = rand(100000, 999999); 
            $expiry_time = time() + 300; 
            
            $_SESSION['otp_user_id'] = $faculty['id'];
            $_SESSION['otp_code'] = $otp_code;
            $_SESSION['otp_expiry'] = $expiry_time;
            
            // The call to sendOtpEmail() is where the crash is likely occurring
            $email_sent = sendOtpEmail($faculty['email'], $otp_code);

            // Log error if email failed to send
            if (!$email_sent) {
                 error_log("OTP EMAIL FAILED for {$faculty['email']}. Code: {$otp_code}. Check email utility/SMTP setup.");
            }

            http_response_code(200); 
            $response = [
                'success' => true,
                'twoFactorRequired' => true,
                'email' => $faculty['email'],
                'send_error' => !$email_sent 
            ];
            goto send_response;
        }
        // --- END FORCED EMAIL OTP LOGIC ---



        // Final step: Proceed with successful session creation (if 2FA is NOT enabled)
        // Use central session helper to rotate session and set base metadata
        if (function_exists('session_secure_login')) {
            session_secure_login($faculty['id'], $faculty['role']);
        } else {
            // Fallback for older installs: regenerate id and set last activity
            session_regenerate_id(true);
            $_SESSION['__last_activity'] = time();
        }

        // Keep backwards-compatible session keys used elsewhere in the app
        $_SESSION['faculty_id'] = $faculty['id'];
        $_SESSION['faculty_email'] = $faculty['email'];
        $_SESSION['faculty_display_name'] = $faculty['display_name'] ?? $faculty['email'];
        $_SESSION['faculty_role'] = $faculty['role'];

        http_response_code(200);
        $response = ['success' => true, 'message' => 'Login successful.'];
        goto send_response;
        
    } catch (PDOException $e) {
        http_response_code(500); 
        error_log("Login PDOException: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error during login.'];
        goto send_response;
    }

} elseif ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') { 
    http_response_code(405); 
    $response = ['success' => false, 'message' => 'Only POST or OPTIONS requests are allowed.'];
    goto send_response;
}


// --- 2. Cleanup and Send Final JSON Response ---
send_response:
ob_clean(); // Discard ALL buffered output (this is the CRITICAL fix)
echo json_encode($response);
exit; 
// -----------------------------------------------------    