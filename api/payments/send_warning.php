<?php
// Use centralized session initialization and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php';
// Load mail config
$mailConfig = [];
if (file_exists(__DIR__ . '/../mail_config.php')) {
    $mailConfig = require __DIR__ . '/../mail_config.php';
}

header('Content-Type: application/json');

// Admin check: Ensure only authorized admins can send warnings
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    // CSRF validation (accept header or JSON body)
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? null;
    if (!validate_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }
    $enrollment_id = $input['enrollment_id'] ?? null;

    if (!$enrollment_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enrollment ID is required.']);
        exit;
    }

    try {
        // Fetch student and parent contact information from the database
        $stmt = $pdo->prepare("
            SELECT student_first_name, student_last_name, mother_contact, father_contact 
            FROM enrollments 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Enrollment record not found.']);
            exit;
        }

        $studentName = $enrollment['student_first_name'] . ' ' . $enrollment['student_last_name'];
        // Use mother's contact, or father's if mother's is not available
        $parentContact = !empty($enrollment['mother_contact']) ? $enrollment['mother_contact'] : $enrollment['father_contact'];

        // Try to send an email using PHPMailer. If PHPMailer is not available
        // this will fall back to PHP's mail() via PHPMailer::isMail().
        require_once __DIR__ . '/../../vendor/autoload.php';

        // Fetch email columns (if available) and outstanding balance
        $stmt2 = $pdo->prepare("SELECT student_email, father_email, mother_email, guardian_email, outstanding_balance, lrn FROM enrollments WHERE id = :id");
        $stmt2->execute([':id' => $enrollment_id]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        $emails = [];
        if ($row) {
            foreach (['guardian_email','mother_email','father_email','student_email'] as $col) {
                $e = trim($row[$col] ?? '');
                if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $e;
                }
            }
        }

        if (empty($emails)) {
            // No valid email addresses found - return error but keep user-friendly message
            echo json_encode(['success' => false, 'message' => 'No valid email addresses found for this enrollment.']);
            exit;
        }

        $balanceText = isset($row['outstanding_balance']) ? number_format((float)$row['outstanding_balance'], 2) : 'N/A';
        $lrnText = $row['lrn'] ?? 'N/A';

        $subject = "Payment Reminder: Outstanding Balance for {$studentName}";
        $htmlBody = "<p>Dear Parent/Guardian,</p>\n" .
                    "<p>This is a friendly reminder from MCREI regarding the outstanding balance for your student <strong>" . htmlspecialchars($studentName) . "</strong> (LRN: " . htmlspecialchars($lrnText) . ").</p>\n" .
                    "<p>Outstanding Balance: <strong>₱{$balanceText}</strong></p>\n" .
                    "<p>Check the status or get in touch with the school office to settle the payment as soon as possible.</p>\n" .
                    "<p>Thank you,<br>MCREI Admissions Office</p>";

        $plainBody = "Dear Parent/Guardian,\n\n" .
                     "This is a friendly reminder from MCREI regarding the outstanding balance for your student {$studentName} (LRN: {$lrnText}).\n" .
                     "Outstanding Balance: ₱{$balanceText}\n\n" .
                     "Please log in to the portal or contact the school office to settle the payment.\n\n" .
                     "Thank you,\nMCREI Admissions Office";

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $sentResult = [
            'success' => false,
            'message' => 'Unknown error',
            'recipients' => array_values(array_unique($emails)),
        ];

        try {
            // Configure transport according to config
            $driver = strtolower($mailConfig['driver'] ?? 'mail');
            if ($driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host = $mailConfig['host'] ?? '127.0.0.1';
                $mail->Port = (int)($mailConfig['port'] ?? 1025);
                $username = $mailConfig['username'] ?? '';
                $password = $mailConfig['password'] ?? '';
                if (!empty($username)) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $username;
                    $mail->Password = $password;
                } else {
                    $mail->SMTPAuth = false;
                }
                $encryption = $mailConfig['encryption'] ?? '';
                if (!empty($encryption)) {
                    $mail->SMTPSecure = $encryption; // 'tls' or 'ssl'
                }
                // reasonable timeouts for SMTP
                $mail->Timeout = 10;
                $mail->SMTPKeepAlive = false;
            } else {
                // default: use PHP mail()
                $mail->isMail();
            }

            // Determine From address: prefer configured value, otherwise fall back to a safe computed value
            $configuredFrom = trim($mailConfig['from_address'] ?? '');
            $configuredName = trim($mailConfig['from_name'] ?? 'MCREI');
            $from = '';
            if ($configuredFrom && filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)) {
                $from = $configuredFrom;
            } else {
                $serverName = $_SERVER['SERVER_NAME'] ?? '';
                $serverHost = preg_replace('/:\d+$/', '', trim($serverName));
                $invalidHosts = ['localhost', '127.0.0.1', '::1', ''];
                if (in_array(strtolower($serverHost), $invalidHosts, true) || strpos($serverHost, '.') === false) {
                    $domain = 'example.com';
                } else {
                    $domain = $serverHost;
                }
                $from = 'no-reply@' . $domain;
                if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
                    $from = 'no-reply@example.com';
                }
            }

            $mail->setFrom($from, $configuredName ?: 'MCREI');
            // set Sender explicitly (some transports use this)
            $mail->Sender = $from;

            foreach (array_unique($emails) as $to) {
                $mail->addAddress($to);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();

            $sentResult['success'] = true;
            $sentResult['message'] = 'Payment warning sent.';

            echo json_encode($sentResult);
        } catch (\PHPMailer\PHPMailer\Exception $ex) {
            $errMsg = $mail->ErrorInfo ?: $ex->getMessage();
            error_log('Send Warning PHPMailer error: ' . $errMsg . ' / ' . $ex->getMessage());
            http_response_code(500);
            $sentResult['success'] = false;
            $sentResult['message'] = 'Failed to send email: ' . $errMsg;
            echo json_encode($sentResult);
        }

        // Persist a send-log (audit). Create table if missing and insert a record.
        try {
            $pdo->beginTransaction();
            $pdo->exec("CREATE TABLE IF NOT EXISTS payment_warnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enrollment_id INT NOT NULL,
                recipients TEXT NOT NULL,
                result VARCHAR(32) NOT NULL,
                error_message TEXT NULL,
                sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sent_by INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $logStmt = $pdo->prepare("INSERT INTO payment_warnings (enrollment_id, recipients, result, error_message, sent_by) VALUES (:enrollment_id, :recipients, :result, :error_message, :sent_by)");
            $logStmt->execute([
                ':enrollment_id' => $enrollment_id,
                ':recipients' => json_encode(array_values(array_unique($emails))),
                ':result' => ($sentResult['success'] ? 'success' : 'failure'),
                ':error_message' => $sentResult['success'] ? null : ($sentResult['message'] ?? null),
                ':sent_by' => $_SESSION['faculty_id'] ?? null,
            ]);
            $pdo->commit();
        } catch (PDOException $le) {
            // don't break main flow if logging fails; just record the error
            error_log('Failed to log payment_warning: ' . $le->getMessage());
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Send Warning Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>