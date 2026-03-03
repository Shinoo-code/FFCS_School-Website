<?php
// Use centralized session initializer so session configuration is consistent
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_connect.php';
// *** IMPORTANT: Requires the Google API Client Library installed via Composer. ***
require_once '../../vendor/autoload.php'; 
// NEW: Include the file that contains the email sending function (for simulation)
require_once '../utilities/send_email_otp.php'; 

// --- Google App Credentials (REPLACE THESE) ---
define('GOOGLE_CLIENT_ID', '44924255509-9487j1pitu6vh963v88ldfikadbmu0vv.apps.googleusercontent.com'); 
define('GOOGLE_CLIENT_SECRET', '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'); 
// The Redirect URI must match the one authorized in Google Cloud Console
define('GOOGLE_REDIRECT_URI', 'http://localhost:3000/api/auth/google_oauth.php'); 
// --- END Credentials ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Initialize the Google Client
    $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]); 
    
    try {
        // Verify the ID Token from the client-side SDK
        $payload = $client->verifyIdToken($id_token);
    } catch (\Exception $e) {
        error_log("Google Token Verification Error: " . $e->getMessage());
        $payload = false;
    }

    if ($payload) {
        $google_email = $payload['email'];
        $google_name = $payload['name'];

        try {
            // Check authorization: must exist in 'faculty' table AND have 'admin' role
            // Also fetch 2fa_enabled so we can decide whether to require OTP
            $stmt = $pdo->prepare("SELECT id, email, display_name, role, 2fa_enabled FROM faculty WHERE email = :email AND role = 'admin'");
            $stmt->bindParam(':email', $google_email);
            $stmt->execute();
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($faculty) {
                // If 2FA is enabled for this account, start the OTP flow
                if (!empty($faculty['2fa_enabled']) && $faculty['2fa_enabled'] == 1) {
                    // --- Email-Based OTP Flow: Step 1 (Initiate OTP) ---
                    $otp_code = rand(100000, 999999); // Generate 6-digit OTP
                    $expiry_time = time() + 300; // 5 minutes validity

                    // Store details in session for verification in the next step
                    $_SESSION['otp_user_id'] = $faculty['id'];
                    $_SESSION['otp_code'] = $otp_code;
                    $_SESSION['otp_expiry'] = $expiry_time;

                    // *** Actual Email Sending Logic ***
                    $email_sent = sendOtpEmail($faculty['email'], $otp_code);

                    // Fallback logging in case email config is still wrong
                    if (!$email_sent) {
                         error_log("OTP EMAIL FAILED. MANUAL CODE: {$otp_code}. Recipient: {$faculty['email']}"); 
                    }

                    // Redirect to the separate OTP verification page
                    header('Location: ../../verify_otp.php?email=' . urlencode($faculty['email']));
                    exit;
                    // --- END OTP Flow ---
                }

                // No 2FA required: finalize the session now using the central helper
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

                // Redirect to dashboard
                header('Location: ../../dashboard.php');
                exit;

            } else {
                // Email not found or role is not admin
                $error_msg = urlencode("Access Denied. Your Google account ({$google_email}) is not authorized for administrator access.");
                header("Location: ../../login.php?error=" . $error_msg);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Google OAuth DB Error: " . $e->getMessage());
            $error_msg = urlencode("Database error during sign-in verification.");
            header("Location: ../../login.php?error=" . $error_msg);
            exit;
        }
    }
} 

$error_msg = urlencode("Google sign-in failed or unauthorized access.");
header("Location: ../../login.php?error=" . $error_msg);
exit;