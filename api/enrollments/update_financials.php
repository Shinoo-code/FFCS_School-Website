<?php
// Centralized session initialization and CSRF
require_once __DIR__ . '/../session.php';
require_once '../db_connect.php';
require_once __DIR__ . '/../csrf.php';

// Admin Check
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: ../../dashboard.php?error_message=" . urlencode("Unauthorized action."));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($posted_token)) {
        header("Location: ../../dashboard.php?error_message=" . urlencode("Invalid or missing CSRF token."));
        exit;
    }
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $tuition_mode = $_POST['tuition_mode'] ?? 'Full Payment';
    $total_tuition = $_POST['total_tuition'] ?? 0.00;
    $outstanding_balance = $_POST['outstanding_balance'] ?? 0.00;
    
    // Get installment months, set to NULL if not installment mode or not provided
    $installment_months = null;
    if ($tuition_mode === 'Installment' && isset($_POST['installment_months'])) {
        $installment_months = !empty($_POST['installment_months']) ? (int)$_POST['installment_months'] : null;
    }

    if (empty($enrollment_id)) {
        header("Location: ../../dashboard.php?error_message=" . urlencode("Enrollment ID is required."));
        exit;
    }

    try {
        $sql = "UPDATE enrollments SET 
                    tuition_mode = :tuition_mode,
                    total_tuition = :total_tuition,
                    outstanding_balance = :outstanding_balance,
                    installment_months = :installment_months
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tuition_mode' => $tuition_mode,
            ':total_tuition' => $total_tuition,
            ':outstanding_balance' => $outstanding_balance,
            ':installment_months' => $installment_months,
            ':id' => $enrollment_id
        ]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../../dashboard.php?success_message=" . urlencode("Financial details updated successfully!"));
        } else {
            header("Location: ../../dashboard.php?success_message=" . urlencode("No changes were made, but the form was submitted."));
        }

    } catch (PDOException $e) {
        error_log("Financial update error: " . $e->getMessage());
        header("Location: ../../dashboard.php?error_message=" . urlencode("A database error occurred during the update."));
    }
} else {
    header("Location: ../../dashboard.php?error_message=" . urlencode("Invalid request method."));
}
exit;
?>