<?php
// api/payments/webhook.php
require_once '../db_connect.php';

// Load mail config and PHPMailer if available
$mailConfig = [];
if (file_exists(__DIR__ . '/../mail_config.php')) {
    $mailConfig = require __DIR__ . '/../mail_config.php';
}
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

$paymongo_webhook_secret_key = 'whsk_R87sTqxEDmuPcgPzK8PoB3rq';

// Get the request body and signature
$payload = file_get_contents('php://input');
$signature_header = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

$event = json_decode($payload, true);

// Check if it's a successful payment event
if (isset($event['data']['attributes']['type']) && $event['data']['attributes']['type'] === 'checkout_session.payment.paid') {
    $session = $event['data']['attributes']['data'];
    $checkout_session_id = $session['id'];
    $metadata = $session['attributes']['metadata'] ?? null;
    $enrollment_id = $metadata['enrollment_id'] ?? null;

    if (!$enrollment_id || !$checkout_session_id) {
        http_response_code(400);
        error_log("Webhook Error: Missing enrollment_id or session_id in payload.");
        exit('Missing required data.');
    }
    
    try {
        $pdo->beginTransaction();

        // Find the "Pending" payment record using the reference number
        $stmt_payment = $pdo->prepare("SELECT * FROM payments WHERE reference_number = ? AND status = 'Pending'");
        $stmt_payment->execute([$checkout_session_id]);
        $payment = $stmt_payment->fetch();

        if ($payment) {
            $amount_paid = $payment['amount_paid'];

            // 1. Update the payment status to 'Approved'
            $stmt_update_payment = $pdo->prepare("UPDATE payments SET status = 'Approved' WHERE id = ?");
            $stmt_update_payment->execute([$payment['id']]);

            // 2. Update the enrollment's outstanding balance
            $stmt_update_enrollment = $pdo->prepare("UPDATE enrollments SET outstanding_balance = outstanding_balance - ? WHERE id = ?");
            $stmt_update_enrollment->execute([$amount_paid, $enrollment_id]);

            // 3. Notify parents/students by email about successful payment (best-effort)
            try {
                // fetch enrollment contact info
                $infoStmt = $pdo->prepare("SELECT student_first_name, student_last_name, student_email, father_email, mother_email, guardian_email, lrn FROM enrollments WHERE id = ?");
                $infoStmt->execute([$enrollment_id]);
                $enroll = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $emails = [];
                foreach (['guardian_email','mother_email','father_email','student_email'] as $col) {
                    $e = trim($enroll[$col] ?? '');
                    if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $e;
                    }
                }
                $emails = array_values(array_unique($emails));

                if (!empty($emails)) {
                    $studentName = trim(($enroll['student_first_name'] ?? '') . ' ' . ($enroll['student_last_name'] ?? '')) ?: 'Student';
                    $lrnText = $enroll['lrn'] ?? 'N/A';

                    $subject = "Payment Received — {$studentName} (LRN: {$lrnText})";
                    $html = "<p>Dear Parent/Guardian,</p>\n" .
                            "<p>We have received your payment of <strong>₱" . number_format((float)$amount_paid, 2) . "</strong> for <strong>" . htmlspecialchars($studentName) . "</strong> (LRN: " . htmlspecialchars($lrnText) . ").</p>\n" .
                            "<p>Reference: <strong>" . htmlspecialchars($checkout_session_id) . "</strong></p>\n" .
                            "<p>Thank you for settling your account. If you have any questions, please contact the school office.</p>\n" .
                            "<p>MCREI Admissions Office</p>";
                    $plain = "Dear Parent/Guardian,\n\n" .
                             "We have received your payment of ₱" . number_format((float)$amount_paid, 2) . " for {$studentName} (LRN: {$lrnText}).\n\n" .
                             "Reference: {$checkout_session_id}\n\nThank you for settling your account.\n\nMCREI Admissions Office";

                    // send via PHPMailer using mail_config
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $driver = strtolower($mailConfig['driver'] ?? 'mail');
                    if ($driver === 'smtp') {
                        $mail->isSMTP();
                        $mail->Host = $mailConfig['host'] ?? 'smtp.gmail.com';
                        $mail->Port = (int)($mailConfig['port'] ?? 587);
                        $username = $mailConfig['username'] ?? '';
                        $password = $mailConfig['password'] ?? '';
                        if (!empty($username)) {
                            $mail->SMTPAuth = true;
                            $mail->Username = $username;
                            $mail->Password = $password;
                        } else {
                            $mail->SMTPAuth = false;
                        }
                        $enc = $mailConfig['encryption'] ?? 'tls';
                        if (!empty($enc)) { $mail->SMTPSecure = $enc; }
                    } else {
                        $mail->isMail();
                    }

                    $from = ($mailConfig['from_address'] ?? 'no-reply@example.com');
                    $fromName = ($mailConfig['from_name'] ?? 'MCREI');
                    $mail->setFrom($from, $fromName);
                    $mail->Sender = $from;
                    foreach ($emails as $to) { $mail->addAddress($to); }
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $html;
                    $mail->AltBody = $plain;

                    try {
                        $mail->send();
                        $notifyStatus = 'success';
                        $notifyError = null;
                    } catch (\PHPMailer\PHPMailer\Exception $mex) {
                        $notifyStatus = 'failure';
                        $notifyError = $mail->ErrorInfo ?: $mex->getMessage();
                        error_log('Payment notification error: ' . $notifyError);
                    }

                    // Log notification
                    try {
                        $pdo->beginTransaction();
                        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_notifications (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            payment_id INT NOT NULL,
                            enrollment_id INT NOT NULL,
                            recipients TEXT NOT NULL,
                            result VARCHAR(32) NOT NULL,
                            error_message TEXT NULL,
                            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            sent_by INT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        $log = $pdo->prepare("INSERT INTO payment_notifications (payment_id, enrollment_id, recipients, result, error_message, sent_by) VALUES (:payment_id, :enrollment_id, :recipients, :result, :error_message, :sent_by)");
                        $log->execute([
                            ':payment_id' => $payment['id'],
                            ':enrollment_id' => $enrollment_id,
                            ':recipients' => json_encode($emails),
                            ':result' => $notifyStatus,
                            ':error_message' => $notifyError,
                            ':sent_by' => null
                        ]);
                        $pdo->commit();
                    } catch (PDOException $loge) {
                        error_log('Failed to log payment notification: ' . $loge->getMessage());
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    }
                }
            } catch (Exception $ex) {
                error_log('Payment notification outer exception: ' . $ex->getMessage());
            }

        }

        $pdo->commit();
        http_response_code(200); // Send a success response back to PayMongo
        echo json_encode(['status' => 'success', 'message' => 'Database updated.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Webhook Database Error: " . $e->getMessage());
        http_response_code(500); // Tell PayMongo something went wrong
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
} else {
    // If it's not a payment success event, just acknowledge it so PayMongo stops sending it.
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
}
?>