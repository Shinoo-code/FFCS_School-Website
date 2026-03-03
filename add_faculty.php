// Final-School-Web/add_faculty.php - MODIFIED for OTP on creation

<?php
require_once __DIR__ . '/api/session.php';
require 'api/db_connect.php';

$message = '';
$message_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    $role = trim($_POST['role'] ?? ''); 

    // Basic Validation (Assuming this section passes)
    if (empty($email) || empty($password) || empty($display_name) || empty($role) || $password !== $confirm_password || strlen($password) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = $password !== $confirm_password ? "Passwords do not match." : "Validation failed.";
        header("Location: manage_users.php?error=" . urlencode($error_msg));
        exit;
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM faculty WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                header("Location: manage_users.php?error=" . urlencode("Email address already registered."));
                exit;
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // MODIFIED: Set 2fa_enabled = 1 and 2fa_secret = NULL to force OTP login
                $sql = "INSERT INTO faculty (email, password_hash, display_name, role, 2fa_enabled, 2fa_secret) 
                        VALUES (:email, :password_hash, :display_name, :role, 1, NULL)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password_hash', $password_hash);
                $stmt->bindParam(':display_name', $display_name);
                $stmt->bindParam(':role', $role); 

                if ($stmt->execute()) {
                    header("Location: manage_users.php?success=" . urlencode("User account created successfully! The new user must verify their email with an OTP on first login."));
                    exit;
                } else {
                    header("Location: manage_users.php?error=" . urlencode("Failed to create account. Please try again."));
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log("Error creating faculty account: " . $e->getMessage());
            header("Location: manage_users.php?error=" . urlencode("Database error. Could not create account."));
            exit;
        }
    }
} else {
    header("Location: manage_users.php");
    exit;
}
?>