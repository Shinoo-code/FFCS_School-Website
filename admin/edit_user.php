<?php
// Use centralized session initializer and CSRF helper
require_once __DIR__ . '/../api/session.php';
require_once __DIR__ . '/../api/csrf.php';
require '../api/db_connect.php';
// --- NEW: Include 2FA utility class (Placeholder) ---
require '../api/auth/TwoFactorAuth.php'; 
// --- END NEW ---

// Admin Check - Ensure only admins can access this page
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized_user_edit_page");
    exit;
}

$user_id_to_edit = null;
$user_data = null;
$error_message = $_GET['error'] ?? '';
$success_message = $_GET['success'] ?? '';

// --- NEW 2FA VARIABLES ---
$tfa_secret = '';
$tfa_qr_url = '';
$tfa_is_enabled = 0;
// --- END 2FA VARIABLES ---


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id_to_edit = (int)$_GET['id'];

    try {
        // MODIFIED: Select new 2FA columns
        $stmt = $pdo->prepare("SELECT id, email, display_name, role, 2fa_secret, 2fa_enabled FROM faculty WHERE id = :id");
        $stmt->bindParam(':id', $user_id_to_edit, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            header("Location: manage_users.php?error=" . urlencode("User not found."));
            exit;
        }
        
        // --- NEW 2FA LOGIC (Setup) ---
        // Fetch existing 2FA status
        $tfa_is_enabled = $user_data['2fa_enabled'];
        $tfa_secret = $user_data['2fa_secret'];
        
        // If no secret exists (new user or secret cleared), generate a new one
        if (!$tfa_secret) {
             // Generate a temporary secret
             $tfa_secret = TwoFactorAuth::createSecret();
        } 

        // Generate the QR Code URL based on the current secret
        $tfa_qr_url = TwoFactorAuth::getQRCodeUrl($user_data['email'], $tfa_secret);
        // --- END NEW 2FA LOGIC ---
        
    } catch (PDOException $e) {
        error_log("Error fetching user for edit: " . $e->getMessage());
        $error_message = "Database error: Could not retrieve user data.";
        $user_data = null;
    }
} else {
    header("Location: manage_users.php?error=" . urlencode("No user ID provided for editing."));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Account - FFCS Dashboard</title>
    <link rel="stylesheet" href="../css/edit_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tfa-setup-section {
            padding: 20px;
            border: 1px solid #ffc107;
            background-color: #fff3cd50;
            border-radius: 8px;
            margin-top: 25px;
            margin-bottom: 25px;
            text-align: center;
        }
        .tfa-qr-container {
            margin: 15px 0;
            padding: 10px;
            background-color: white;
            border: 1px solid #ddd;
            display: inline-block;
        }
        .tfa-status-enabled { color: var(--accent-green); font-weight: bold; }
        .tfa-status-disabled { color: var(--secondary-red); font-weight: bold; }
        .tfa-secret-code { font-family: monospace; font-size: 1.1em; background-color: #eee; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="edit-user-container">
        <h2>Edit User Account</h2>

        <div class="message-area">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($user_data): ?>
            <form action="../api/auth/update_user.php" method="POST">
                <?php echo csrf_input_field(); ?>
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['id']) ?>">

                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" readonly disabled>
                    <small class="text-muted">Email address cannot be changed.</small>
                </div>

                <div class="form-group">
                    <label for="display_name">Display Name:</label>
                    <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($user_data['display_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <input type="text" id="role" name="role_display" value="Admin" disabled readonly>
                    <small class="text-muted">All user accounts are set to Admin.</small>
                    <input type="hidden" name="role" value="admin">
                </div>

                <hr style="margin: 25px 0;">

                <div class="tfa-setup-section">
                    <h4>Two-Factor Authentication Setup</h4>
                    <p>Current Status: 
                        <span class="<?= $tfa_is_enabled ? 'tfa-status-enabled' : 'tfa-status-disabled' ?>">
                            <?= $tfa_is_enabled ? 'ENABLED' : 'DISABLED' ?>
                        </span>
                    </p>
                    
                    <input type="hidden" name="tfa_current_secret" value="<?= htmlspecialchars($tfa_secret) ?>">
                    
                    <?php if (!$tfa_is_enabled): ?>
                        <p class="text-muted small">To enable 2FA, please follow these steps:</p>
                        <ol style="text-align: left; max-width: 450px; margin: 10px auto;">
                            <li>Install an authenticator app (e.g., Google Authenticator, Authy).</li>
                            <li>Scan the QR code below or manually enter the secret key.</li>
                            <li>Enter the 6-digit code generated by the app below to confirm and enable.</li>
                        </ol>
                        
                        <div class="tfa-qr-container">
                             <img src="<?= htmlspecialchars($tfa_qr_url) ?>" alt="2FA QR Code" width="180" height="180">
                        </div>
                        <p>Manual Secret: <span class="tfa-secret-code"><?= htmlspecialchars($tfa_secret) ?></span></p>

                        <div class="form-group" style="max-width: 300px; margin: 20px auto;">
                            <label for="tfa_code_verify">Verification Code *</label>
                            <input type="text" id="tfa_code_verify" name="tfa_code_verify" required placeholder="Enter code to enable 2FA">
                        </div>
                        <p class="text-muted small">After clicking 'Update Account', your 2FA will be enabled for the next login.</p>
                        
                    <?php else: ?>
                        <div style="max-width: 400px; margin: 0 auto;">
                            <p class="text-muted small">2FA is currently active for this account. To disable, enter your current 2FA code and click 'Disable 2FA'.</p>
                            <div class="form-group">
                                <label for="tfa_code_verify_disable">Current 2FA Code *</label>
                                <input type="text" id="tfa_code_verify_disable" name="tfa_code_verify_disable" placeholder="Enter current code">
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" name="action_2fa" value="disable" class="btn btn-sm btn-danger"><i class="fas fa-times-circle"></i> Disable 2FA</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
                <hr style="margin: 25px 0;">
                <p class="text-muted"><em>Leave password fields blank if you do not want to change the password.</em></p>

                <div class="form-group">
                    <label for="new_password">New Password (min. 8 characters):</label>
                    <input type="password" id="new_password" name="new_password">
                </div>

                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password">
                </div>

                <button type="submit" class="btn-submit-update"><i class="fas fa-save"></i> Update Account</button>
            </form>
        <?php else: ?>
            <?php if (empty($error_message)): ?>
                <p class="text-danger text-center">Could not load user data for editing.</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="back-container">
            <a href="manage_users.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide success/error messages
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>