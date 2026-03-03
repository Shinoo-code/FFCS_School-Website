<?php
// Use centralized session initializer and CSRF helper
require_once __DIR__ . '/api/session.php';
require_once __DIR__ . '/api/csrf.php';

// Get email from URL (for display) and check for errors
$email = htmlspecialchars($_GET['email'] ?? 'your account');
$error_message = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <style>
        .verification-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: bold;
        }
        .login-btn {
            background-color: #007bff; /* Use a primary blue color */
            margin-top: 20px;
        }
    </style>
</head>
<body class="quiz-page-body">

<div class="verification-container">
    <h3 class="text-center" style="color: var(--primary-blue);">Two-Factor Verification</h3>
    <p class="text-center text-muted">A verification code has been sent to<?= $email ?>.</p>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger text-center"><?= $error_message ?></div>
    <?php elseif (!isset($_SESSION['otp_code']) || (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry'])): ?>
         <div class="alert alert-danger text-center">Verification session expired or invalid. Please go back and try signing in again.</div>
    <?php else: ?>
        <div class="alert alert-warning text-center small">
            Your verification code expires in 5 minutes.
        </div>
    <?php endif; ?>

    <form action="api/auth/verify_otp_endpoint.php" method="POST">
        <?php echo csrf_input_field(); ?>
        <div class="form-group mb-4">
            <label for="otp-code" class="text-center w-100">Enter 6-Digit Code:</label>
            <input type="text" id="otp-code" name="otp_code" class="form-control" maxlength="6" required placeholder="123456">
        </div>
        <button type="submit" class="login-btn w-100">Verify and Log In</button>
    </form>
    
    <p class="text-center mt-3"><a href="login.php">Cancel Login</a></p>
</div>

</body>
</html>