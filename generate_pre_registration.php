<?php
require_once 'api/db_connect.php';

$enrollment_id = $_GET['id'] ?? null;

if (!$enrollment_id) {
    die('Error: Enrollment ID missing.');
}

$stmt = $pdo->prepare('SELECT * FROM enrollments WHERE id = ?');
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enrollment) {
    die('Error: Enrollment record not found.');
}

$student_full_name = htmlspecialchars(
    ($enrollment['student_first_name'] ?? '') . ' ' .
    (!empty($enrollment['student_middle_name']) ? mb_substr($enrollment['student_middle_name'], 0, 1) . '. ' : '') .
    ($enrollment['student_last_name'] ?? '') .
    (!empty($enrollment['student_extension_name']) ? ' ' . $enrollment['student_extension_name'] : '')
);

$lrn = htmlspecialchars($enrollment['lrn'] ?? 'N/A');
$program = htmlspecialchars($enrollment['program'] ?? ($enrollment['preferred_program'] ?? 'N/A'));
$grade_level = htmlspecialchars($enrollment['grade_level'] ?? 'N/A');
// Section (new requirement) - fallbacks in order of likelihood
$section = htmlspecialchars(
    $enrollment['section'] ??
    $enrollment['student_section'] ??
    $enrollment['preferred_section'] ??
    'N/A'
);
// Email of learner (fallbacks)
$learner_email = htmlspecialchars(
    $enrollment['student_email'] ??
    $enrollment['email'] ??
    $enrollment['guardian_email'] ??
    'N/A'
);
$created_at = htmlspecialchars($enrollment['created_at'] ?? date('Y-m-d H:i:s'));

// Financials (optional)
$total_tuition = isset($enrollment['total_tuition']) ? (float)$enrollment['total_tuition'] : null;
$outstanding_balance = isset($enrollment['outstanding_balance']) ? (float)$enrollment['outstanding_balance'] : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pre-Registration Form - <?= $student_full_name ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400,600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; font-size: 10pt; }
        .pr-container { width: 210mm; margin: 15px auto; background-color: #fff; padding: 20mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; }
        .pr-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .school-logo { max-width: 65px; height: auto; }
        .header-text h1 { margin: 0; font-size: 14pt; font-weight: 700; color: #333; }
        .header-text p { margin: 2px 0 0 0; color: #555; font-size: 9pt; }
        .pr-title { text-align: center; font-size: 13pt; font-weight: 700; margin: 12px 0 8px 0; }
        .pr-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10pt; }
        .pr-table tr { border-bottom: 1px solid #ddd; }
        .pr-table tr:last-child { border-bottom: 2px solid #333; }
        .pr-table td { padding: 8px 6px; }
        .label { width: 30%; font-weight: 700; color: #333; }
        .bal { font-weight: 700; color: #c00; }
        .note { margin-top: 16px; padding: 10px; background: #fff3cd; border: 1px solid #ffeeba; color: #856404; font-size: 9pt; line-height: 1.4; }
        .print-btn { display: block; width: 170px; margin: 20px auto; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; text-align: center; cursor: pointer; font-size: 10pt; }
        .print-btn:hover { background-color: #0056b3; }
        .pr-footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #666; border-top: 1px solid #ccc; padding-top: 8px; }
        @media print { 
            body { background-color: #fff; font-size: 10pt; margin: 0; padding: 0; }
            .pr-container { margin: 0; padding: 15mm; box-shadow: none; border: none; width: 100%; page-break-after: avoid; }
            .print-btn { display: none; }
            .pr-footer { margin-top: 12px; }
            @page { size: A4; margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="pr-container">
        <div class="pr-header">
            <img src="FFCS Pics/logo_monte_cristo.jpg" alt="School Logo" class="school-logo">
            <div class="header-text">
                <h1>Faith Family Christian School</h1>
                <p>Dasmariñas, Cavite</p>
            </div>
        </div>

        <div class="pr-title">Pre-Registration Form (No Payment)</div>

        <table class="pr-table">
            <tr>
                <td class="label">Student Name:</td>
                <td><?= $student_full_name ?></td>
            </tr>
            <tr>
                <td class="label">LRN:</td>
                <td><?= $lrn ?></td>
            </tr>
            <tr>
                <td class="label">Section:</td>
                <td><?= $section ?> </td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td><?= $learner_email ?></td>
            </tr>
            <tr>
                <td class="label">Date Submitted:</td>
                <td><?= $created_at ?></td>
            </tr>
            <?php if (!is_null($total_tuition) || !is_null($outstanding_balance)): ?>
                <tr class="financial">
                    <td class="label">Total Tuition:</td>
                    <td>₱<?= is_null($total_tuition) ? 'N/A' : number_format($total_tuition, 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Outstanding Balance:</td>
                    <td class="bal"><?= is_null($outstanding_balance) ? '₱0.00' : '₱' . number_format($outstanding_balance, 2) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="note">
            Please present this pre-registration form to the school cashier or admission officer when visiting the school. This form confirms that you have started the online pre-registration process. It is not a certificate of registration nor an official receipt.
        </div>

        <button class="print-btn" onclick="window.print()"><i class="bi bi-printer"></i> Print Pre-Registration Form</button>

        <div class="pr-footer">This is a system-generated document. Not valid as official receipt. Date Generated: <?= date('F j, Y, g:i A') ?></div>

    </div>
</body>
</html>
