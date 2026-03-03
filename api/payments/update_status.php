<?php
// Centralized session initialization and CSRF
require_once __DIR__ . '/../session.php';
require_once '../db_connect.php';
// Admin check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../manage_payments.php?error_message=Unauthorized");
    exit;
}

// CSRF protection
require_once __DIR__ . '/../csrf.php';

// Only accept POST for state-changing actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../manage_payments.php?error_message=Invalid request method.");
    exit;
}

$payment_id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

// Validate CSRF token
$posted_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($posted_token)) {
    header("Location: ../../manage_payments.php?error_message=Invalid or missing CSRF token.");
    exit;
}

if (!$payment_id || !$action || !in_array($action, ['approve', 'decline'])) {
    header("Location: ../../manage_payments.php?error_message=Invalid action or ID.");
    exit;
}

try {
    $pdo->beginTransaction();

    // Get payment details
    $stmt_payment = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt_payment->execute([$payment_id]);
    $payment = $stmt_payment->fetch();

    if (!$payment || $payment['status'] !== 'Pending') {
        $pdo->rollBack();
        header("Location: ../../manage_payments.php?error_message=Payment not found or already processed.");
        exit;
    }

    if ($action === 'approve') {
        // 1. Update payment status
        $stmt_update_payment = $pdo->prepare("UPDATE payments SET status = 'Approved' WHERE id = ?");
        $stmt_update_payment->execute([$payment_id]);

        // 2. Get current enrollment balance
        $stmt_enrollment = $pdo->prepare("SELECT outstanding_balance FROM enrollments WHERE id = ?");
        $stmt_enrollment->execute([$payment['enrollment_id']]);
        $enrollment = $stmt_enrollment->fetch();

        // 3. Update enrollment balance and status
        $new_balance = $enrollment['outstanding_balance'] - $payment['amount_paid'];
        
        $sql_update_enrollment = "UPDATE enrollments SET outstanding_balance = :new_balance";
        // If balance is paid off, officially enroll the student
        if ($new_balance <= 0) {
            $sql_update_enrollment .= ", status = 'Enrolled'";
        }
        $sql_update_enrollment .= " WHERE id = :enrollment_id";
        
        $stmt_update_enrollment = $pdo->prepare($sql_update_enrollment);
        $stmt_update_enrollment->execute([
            ':new_balance' => $new_balance,
            ':enrollment_id' => $payment['enrollment_id']
        ]);

        $success_message = "Payment approved and balance updated.";

    } else { // Action is 'decline'
        // Use 'For Verification' as the canonical status label for verification-required cases
        $stmt_update_payment = $pdo->prepare("UPDATE payments SET status = 'For Verification' WHERE id = ?");
        $stmt_update_payment->execute([$payment_id]);
        $success_message = "Payment marked For Verification.";
    }

    $pdo->commit();
    header("Location: ../../manage_payments.php?success_message=" . urlencode($success_message));

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Payment status update error: " . $e->getMessage());
    header("Location: ../../manage_payments.php?error_message=Database error occurred.");
}
?>
