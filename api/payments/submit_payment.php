<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $amount_paid = $_POST['amount_paid'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $reference_number = $_POST['reference_number'] ?? null;
    $proof_of_payment_url = null;

    if (empty($enrollment_id) || empty($amount_paid) || empty($payment_method)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required payment information.']);
        exit;
    }

    // Handle file upload for proof
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        $filename = basename($_FILES['proof_of_payment']['name']);
        $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
        $extension = pathinfo($safe_filename, PATHINFO_EXTENSION);
        $unique_filename = 'proof_' . $enrollment_id . '_' . uniqid() . '.' . $extension;
        $target_path = $upload_dir . $unique_filename;

        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $target_path)) {
            $proof_of_payment_url = 'uploads/payments/' . $unique_filename;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload proof of payment.']);
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO payments (enrollment_id, amount_paid, payment_method, reference_number, proof_of_payment_url, status) 
             VALUES (:enrollment_id, :amount_paid, :payment_method, :reference_number, :proof_url, 'Pending')"
        );
        $stmt->execute([
            ':enrollment_id' => $enrollment_id,
            ':amount_paid' => $amount_paid,
            ':payment_method' => $payment_method,
            ':reference_number' => $reference_number,
            ':proof_url' => $proof_of_payment_url
        ]);

        echo json_encode(['success' => true, 'message' => 'Payment submitted successfully. Please wait for confirmation.']);

    } catch (PDOException $e) {
        error_log("Payment submission error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during payment submission.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>