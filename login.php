<?php
// Central session initialization and CSRF helper for the login page
require_once __DIR__ . '/api/session.php';
require_once __DIR__ . '/api/csrf.php';

// The actual PHP logic should be minimal here as the main authentication is done via JS fetch to the API.
// We handle any error messages passed from the API or the Google OAuth handler.
$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  <title>Login - FFCS</title>
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Arial&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet"> 
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="login-page-wrapper"> <div class="login-container">
            <div class="login-header">
                <h2>Login</h2>
                </div>
            <div class="login-body">
                <div class="logo-container">
                    <img src="FFCS Pics/logo_monte_cristo.jpg" alt="FFCS Logo" class="logo">
                    <div class="school-name">Faith Family Christian School</div>
                </div>
                
                <div class="error-message" id="error-message" style="display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                
                <form class="login-form" id="login-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                    <div class="form-group" id="tfa-group" style="display: none;">
                        <label for="tfa-code">2FA Code (from Authenticator App)</label>
                        <input type="text" id="tfa-code" name="tfa_code" placeholder="Enter 6-digit code">
                    </div>
                    
                    <div class="forgot-password">
                        <a href="#">Forgot Password?</a>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
                
                <hr style="margin: 20px 0; border-top: 1px solid #eee;">
                <p class="text-center text-muted small mb-3">OR sign in with Google:</p>
                
                <div class="text-center">
                    <div id="g_id_onload"
                        data-client_id="44924255509-9487j1pitu6vh963v88ldfikadbmu0vv.apps.googleusercontent.com"
                        data-context="signin"
                        data-ux_mode="redirect"
                        data-login_uri="http://localhost:3000/api/auth/google_oauth.php"
                        data-auto_prompt="false">
                    </div>

                    <div class="g_id_signin"
                        data-type="standard"
                        data-shape="rectangular"
                        data-theme="outline"
                        data-text="signin_with"
                        data-size="large"
                        data-logo_alignment="left">
                    </div>
                </div>
                <div class="login-footer">
                    <p>Back to <a href="index.php">School Website</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <div id="forgotPasswordModal" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h5 class="custom-modal-title">Forgot Password?</h5>
                <button type="button" class="custom-modal-close-btn" id="closeForgotPasswordModalBtn">&times;</button>
            </div>
            <div class="custom-modal-body">
                <p style="text-align: center; font-size: 1.05rem; color: var(--text-dark);">
                    If you have forgotten your password, please contact your school administrator for assistance.
                </p>
                <p style="text-align: center; font-size: 0.9rem; color: var(--text-light);">
                    They will be able to help you reset your account.
                </p>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn-custom-modal-ok" id="okForgotPasswordModalBtn">OK</button>
            </div>
        </div>
    </div>

    <script defer src="js/login.js"></script>
    <script>
        // expose CSRF token for AJAX login requests
        window.APP_CSRF_TOKEN = '<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8"); ?>';
    </script>
    
</body>
</html>