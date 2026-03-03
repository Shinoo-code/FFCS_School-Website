<?php
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php';
// --- NEW: Include 2FA utility class (Placeholder) ---
require 'TwoFactorAuth.php';
// --- END NEW ---

// Admin Check
// Validate CSRF token for POST requests first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validate_csrf_token($posted_csrf)) {
        header("Location: ../../manage_users.php?error=" . urlencode("Invalid CSRF token."));
        exit;
    }
}

if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    if (isset($_POST['user_id'])) {
        header("Location: ../../edit_user.php?id=" . $_POST['user_id'] . "&error=" . urlencode("Unauthorized action."));
    } else {
        header("Location: ../../manage_users.php?error=" . urlencode("Unauthorized action."));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id_to_update = $_POST['user_id'] ?? null;
    $display_name = trim($_POST['display_name'] ?? '');
    $role = 'admin'; 
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    
    // --- NEW 2FA FIELDS ---
    $tfa_current_secret = $_POST['tfa_current_secret'] ?? null; 
    $tfa_code_verify = $_POST['tfa_code_verify'] ?? null;     
    $tfa_code_verify_disable = $_POST['tfa_code_verify_disable'] ?? null; 
    $action_2fa = $_POST['action_2fa'] ?? null; 
    // --- END NEW 2FA FIELDS ---

    // Validation (Standard)
    if (empty($user_id_to_update) || !is_numeric($user_id_to_update)) {
        header("Location: ../../manage_users.php?error=" . urlencode("Invalid user ID."));
        exit;
    }
    if (empty($display_name)) {
        header("Location: ../../edit_user.php?id=" . $user_id_to_update . "&error=" . urlencode("Display Name is required."));
        exit;
    }

    $update_password = false;
    if (!empty($new_password)) {
        if ($new_password !== $confirm_new_password) {
            header("Location: ../../edit_user.php?id=" . $user_id_to_update . "&error=" . urlencode("New passwords do not match."));
            exit;
        }
        if (strlen($new_password) < 8) {
            header("Location: ../../edit_user.php?id=" . $user_id_to_update . "&error=" . urlencode("New password must be at least 8 characters long."));
            exit;
        }
        $update_password = true;
    }

    // --- NEW: 2FA Validation and Logic ---
    $update_tfa = false;
    $tfa_status_message = "";
    $tfa_secret_to_save = null;
    $tfa_enabled_to_save = null;
    $redirect_to_self = "../../edit_user.php?id=" . $user_id_to_update; // Helper for redirects

    // 1. Handle explicit DISABLE action
    if ($action_2fa === 'disable') {
        if (empty($tfa_code_verify_disable)) {
             header("Location: " . $redirect_to_self . "&error=" . urlencode("You must enter the current 2FA code to disable it."));
             exit;
        }
        
        // Fetch current secret for verification
        $stmt_secret = $pdo->prepare("SELECT 2fa_secret FROM faculty WHERE id = ?");
        $stmt_secret->execute([$user_id_to_update]);
        $existing_secret = $stmt_secret->fetchColumn();

        if (TwoFactorAuth::verifyCode($existing_secret, $tfa_code_verify_disable)) {
             $update_tfa = true;
             $tfa_secret_to_save = null; // Clear the secret
             $tfa_enabled_to_save = 0;
             $tfa_status_message = " 2FA successfully disabled!";
        } else {
             header("Location: " . $redirect_to_self . "&error=" . urlencode("Invalid 2FA code provided. 2FA remains active."));
             exit;
        }
        
    } 
    
    // 2. Handle implicit ENABLE action (when saving and verification code is present)
    // Only proceed if a verification code is present AND user is currently disabled
    if (!empty($tfa_code_verify) && empty($action_2fa)) {
        // Fetch current DB status to confirm if we need to enable
        $stmt_is_enabled = $pdo->prepare("SELECT 2fa_enabled FROM faculty WHERE id = ?");
        $stmt_is_enabled->execute([$user_id_to_update]);
        $is_currently_enabled = $stmt_is_enabled->fetchColumn();
        
        if ($is_currently_enabled == 0) {
            if (empty($tfa_current_secret)) {
                 header("Location: " . $redirect_to_self . "&error=" . urlencode("2FA Secret key is missing. Please reload the page."));
                 exit;
            }

            if (TwoFactorAuth::verifyCode($tfa_current_secret, $tfa_code_verify)) {
                 $update_tfa = true;
                 $tfa_secret_to_save = $tfa_current_secret; // Save the newly generated secret
                 $tfa_enabled_to_save = 1;
                 $tfa_status_message = " 2FA successfully enabled!";
            } else {
                 header("Location: " . $redirect_to_self . "&error=" . urlencode("Invalid 2FA verification code. Please correct it to enable 2FA."));
                 exit;
            }
        }
    }
    // --- END 2FA Validation and Logic ---


    try {
        // Build SQL dynamically to include password, 2FA fields, and display name
        $sql_parts = ["display_name = :display_name"];
        $params = [
            ':display_name' => $display_name,
            ':id' => $user_id_to_update
        ];

        if ($update_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_parts[] = "password_hash = :password_hash";
            $params[':password_hash'] = $new_password_hash;
        }

        if ($update_tfa) {
             $sql_parts[] = "2fa_secret = :tfa_secret";
             $sql_parts[] = "2fa_enabled = :tfa_enabled";
             $params[':tfa_secret'] = $tfa_secret_to_save; 
             $params[':tfa_enabled'] = $tfa_enabled_to_save;
        }


        $sql = "UPDATE faculty SET " . implode(", ", $sql_parts) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute($params)) {
            $final_message = "User account updated successfully." . $tfa_status_message;
            // Handle case where only display name or password was changed but 2FA wasn't touched
            if(!$update_tfa && !$update_password && $stmt->rowCount() == 0) {
                 $final_message = "No changes were applied (data submitted was identical to current data).";
            }
            header("Location: " . $redirect_to_self . "&success=" . urlencode($final_message));
        } else {
            header("Location: " . $redirect_to_self . "&error=" . urlencode("Failed to update account. Please try again."));
        }
        exit;

    } catch (PDOException $e) {
        error_log("Error updating faculty account: " . $e->getMessage());
        header("Location: " . $redirect_to_self . "&error=" . urlencode("Database error. Could not update account."));
        exit;
    }
} else {
    header("Location: ../../manage_users.php");
    exit;
}