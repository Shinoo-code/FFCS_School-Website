<?php
// Centralized session initialization and CSRF
require_once __DIR__ . '/../session.php';
require_once '../db_connect.php';
require_once __DIR__ . '/../csrf.php';

// Load mail config and PHPMailer if available
$mailConfig = [];
if (file_exists(__DIR__ . '/../mail_config.php')) {
    $mailConfig = require __DIR__ . '/../mail_config.php';
}
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

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
    $status = $_POST['status'] ?? 'Pending';
    // This is now the ID of the section from the <select> dropdown
    $section_id = ($status === 'Enrolled') ? ($_POST['section_id'] ?? null) : null;

    // --- NEW: Get the admin remarks ---
    // Use trim, but allow empty string. Convert empty string to NULL.
    $admin_remarks = trim($_POST['admin_remarks'] ?? '');
    $admin_remarks_to_save = !empty($admin_remarks) ? $admin_remarks : null;
    // --- END NEW ---


    if (empty($enrollment_id)) {
        header("Location: ../../dashboard.php?error_message=" . urlencode("Enrollment ID is required."));
        exit;
    }

    try {
        // Fetch enrollment basic info (name, emails, lrn) for notification composition
        $infoStmt = $pdo->prepare("SELECT student_first_name, student_last_name, student_email, father_email, mother_email, guardian_email, lrn, section, school_year FROM enrollments WHERE id = :id");
        $infoStmt->execute([':id' => $enrollment_id]);
        $enrollInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pdo->beginTransaction();

        $section_name_to_save = null;
        
        // If enrolling, get the section name from its ID
        if ($status === 'Enrolled' && !empty($section_id)) {
            $stmt_sec_name = $pdo->prepare("SELECT section_name FROM sections WHERE id = ?");
            $stmt_sec_name->execute([$section_id]);
            $section_name_to_save = $stmt_sec_name->fetchColumn();
        }

        // --- NEW: If status is set to Enrolled, clear the remarks ---
        if ($status === 'Enrolled') {
            $admin_remarks_to_save = null;
        }
        // --- END NEW ---

        // Step 1: Update the enrollment status, section, and admin_remarks
        // --- MODIFIED SQL ---
        $sql = "UPDATE enrollments SET 
                    status = :status, 
                    section = :section, 
                    admin_remarks = :admin_remarks 
                WHERE id = :id";
        $stmt_update_enrollment = $pdo->prepare($sql);
        $stmt_update_enrollment->execute([
            ':status' => $status,
            ':section' => $section_name_to_save,
            ':admin_remarks' => $admin_remarks_to_save, // <-- NEW
            ':id' => $enrollment_id
        ]);
        // --- END MODIFIED SQL ---


        // Step 2: AUTOMATION LOGIC - Clear old subjects and assign new ones
        
    // Always clear subjects when status changes to avoid having subjects when "Pending" or "For Verification" (legacy: 'Declined')
        $delete_stmt = $pdo->prepare("DELETE FROM subjects WHERE enrollment_id = ?");
        $delete_stmt->execute([$enrollment_id]);

        // If the new status is "Enrolled" and a valid section was chosen, assign the new subjects
        if ($status === 'Enrolled' && !empty($section_id)) {
            // Get all predefined subjects for the chosen section
            $subjects_stmt = $pdo->prepare("SELECT * FROM section_subjects WHERE section_id = ?");
            $subjects_stmt->execute([$section_id]);
            $predefined_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Insert each predefined subject into the student's personal subject list
            if ($predefined_subjects) {
                $insert_subject_stmt = $pdo->prepare(
                    "INSERT INTO subjects (enrollment_id, subject_name, teacher_name, schedule, time_slot, room, modality) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                foreach ($predefined_subjects as $subject) {
                    $insert_subject_stmt->execute([
                        $enrollment_id,
                        $subject['subject_name'],
                        $subject['teacher_name'],
                        $subject['schedule'],
                        $subject['time_slot'],
                        $subject['room'],
                        'Face-to-Face' // Default modality
                    ]);
                }
            }
        }

        $pdo->commit();

        // After successful DB update, attempt to notify the student/parents by email
        try {
            // Build recipient list from enrollment info and validate
            $emails = [];
            foreach (['guardian_email','mother_email','father_email','student_email'] as $col) {
                $e = trim($enrollInfo[$col] ?? '');
                if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $e;
                }
            }
            $emails = array_values(array_unique($emails));

            if (!empty($emails)) {
                $studentName = trim(($enrollInfo['student_first_name'] ?? '') . ' ' . ($enrollInfo['student_last_name'] ?? '')) ?: 'Student';
                $lrn = $enrollInfo['lrn'] ?? 'N/A';
                $sectionUsed = $section_name_to_save ?? ($enrollInfo['section'] ?? null);

                // Compose subject and body based on status
                if ($status === 'Enrolled') {
                    $subject = "Congratulations — {$studentName} is Enrolled at MCREI";
                    $html = "<p>Dear Parent/Guardian,</p>\n" .
                            "<p>Congratulations! We are pleased to inform you that your student <strong>" . htmlspecialchars($studentName) . "</strong> (LRN: " . htmlspecialchars($lrn) . ") has been <strong>ENROLLED</strong> for the upcoming school year.</p>\n" .
                            ($sectionUsed ? "<p>Assigned Section: <strong>" . htmlspecialchars($sectionUsed) . "</strong></p>\n" : '') .
                            "<p>Please follow the admissions instructions sent earlier regarding payments and document submission. If you have questions, contact the school office.</p>\n" .
                            "<p>Welcome to the MCREI family!<br>MCREI Admissions Office</p>";
                    $plain = "Dear Parent/Guardian,\n\n" .
                             "Congratulations! Your student {$studentName} (LRN: {$lrn}) has been ENROLLED." .
                             ($sectionUsed ? "\nAssigned Section: {$sectionUsed}" : "") .
                             "\n\nPlease follow the admissions instructions regarding payments and document submission.\n\nMCREI Admissions Office";
                } else {
                    // Pending or Declined
                    $statusWord = strtoupper($status);
                    $subject = "Enrollment Update: {$statusWord} — {$studentName}";
                    $remarkText = trim($admin_remarks_to_save ?? '');
                    if (empty($remarkText)) {
                        $remarkText = ($status === 'Pending') ? 'Some submitted documents need clarification or clearer copies. Please review and re-upload legible documents.' : 'Your application could not be accepted. Please contact the admissions office for details.';
                    }
                    $html = "<p>Dear Parent/Guardian,</p>\n" .
                            "<p>We are writing about the enrollment application for <strong>" . htmlspecialchars($studentName) . "</strong> (LRN: " . htmlspecialchars($lrn) . "). Current status: <strong>" . htmlspecialchars($status) . "</strong>.</p>\n" .
                            "<p><strong>Note from the admissions team:</strong> " . nl2br(htmlspecialchars($remarkText)) . "</p>\n" .
                            "<p>Please address the issues above and resubmit the required items where applicable. If you need assistance, contact the admissions office.</p>\n" .
                            "<p>MCREI Admissions Office</p>";
                    $plain = "Dear Parent/Guardian,\n\n" .
                             "Status for {$studentName} (LRN: {$lrn}): {$status}\n\n" .
                             "Note from the admissions team: {$remarkText}\n\n" .
                             "Please address the issues above and contact admissions if you need help.\n\nMCREI Admissions Office";
                }

                // Send using PHPMailer and mail_config
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                // transport
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
                    $encryption = $mailConfig['encryption'] ?? 'tls';
                    if (!empty($encryption)) {
                        $mail->SMTPSecure = $encryption;
                    }
                } else {
                    $mail->isMail();
                }

                // From
                $configuredFrom = trim($mailConfig['from_address'] ?? '');
                $configuredName = trim($mailConfig['from_name'] ?? 'MCREI');
                $from = ($configuredFrom && filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)) ? $configuredFrom : 'no-reply@example.com';
                $mail->setFrom($from, $configuredName ?: 'MCREI');
                $mail->Sender = $from;

                foreach ($emails as $to) {
                    $mail->addAddress($to);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = $plain;

                try {
                    $mail->send();
                    $notifyResult = ['success' => true, 'message' => 'Notification sent', 'recipients' => $emails];
                } catch (\PHPMailer\PHPMailer\Exception $mex) {
                    $err = $mail->ErrorInfo ?: $mex->getMessage();
                    error_log('Enrollment notification error: ' . $err);
                    $notifyResult = ['success' => false, 'message' => $err, 'recipients' => $emails];
                }

                // Log the notification attempt to enrollment_notifications
                try {
                    $pdo->beginTransaction();
                    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollment_notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        enrollment_id INT NOT NULL,
                        recipients TEXT NOT NULL,
                        status VARCHAR(32) NOT NULL,
                        result VARCHAR(32) NOT NULL,
                        error_message TEXT NULL,
                        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        sent_by INT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $ins = $pdo->prepare("INSERT INTO enrollment_notifications (enrollment_id, recipients, status, result, error_message, sent_by) VALUES (:enrollment_id, :recipients, :status, :result, :error_message, :sent_by)");
                    $ins->execute([
                        ':enrollment_id' => $enrollment_id,
                        ':recipients' => json_encode($emails),
                        ':status' => $status,
                        ':result' => ($notifyResult['success'] ? 'success' : 'failure'),
                        ':error_message' => $notifyResult['success'] ? null : $notifyResult['message'],
                        ':sent_by' => $_SESSION['faculty_id'] ?? null,
                    ]);
                    $pdo->commit();
                } catch (PDOException $loge) {
                    error_log('Failed to log enrollment notification: ' . $loge->getMessage());
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                }
            }
        } catch (Exception $outerNotifyEx) {
            error_log('Notification outer exception: ' . $outerNotifyEx->getMessage());
        }

        header("Location: ../../dashboard.php?success_message=" . urlencode("Status, remarks, and subjects updated automatically!"));

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Status/Section/Subject update error: " . $e->getMessage());
        header("Location: ../../dashboard.php?error_message=" . urlencode("A database error occurred during the update process."));
    }
} else {
    header("Location: ../../dashboard.php?error_message=" . urlencode("Invalid request method."));
}
exit;
?>