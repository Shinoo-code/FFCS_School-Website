<?php
require_once 'api/db_connect.php';

$lrn = $_GET['lrn'] ?? null;
$payment_data = null;

if ($lrn) {
    try {
        // Fetch the most recent payment for this LRN
        $stmt = $pdo->prepare("
            SELECT 
                p.reference_number,
                p.amount_paid,
                p.payment_date,
                e.student_first_name,
                e.student_last_name,
                e.lrn
            FROM payments p
            JOIN enrollments e ON p.enrollment_id = e.id
            WHERE e.lrn = ?
            ORDER BY p.payment_date DESC
            LIMIT 1
        ");
        $stmt->execute([$lrn]);
        $payment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle DB error
        die("Error fetching payment data.");
    }
}

if (!$payment_data) {
    die("Could not find payment information for the provided LRN.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/receipt.css">
</head>
<body>

    <div class="receipt-container">
        <div class="receipt-header">
            <img src="FFCS Pics/logo_monte_cristo.jpg" alt="School Logo" class="logo">
            <div class="header-text">
                <h3>Faith Family Christian School</h3>
                <p>Official Receipt</p>
            </div>
        </div>
        <div class="success-message">
            <i class="bi bi-check-circle-fill"></i>
            <h4>Payment Successful!</h4>
            <p>Thank you for your payment. Your transaction has been completed.</p>
        </div>
        <div class="receipt-body">
            <div class="receipt-details">
                <div class="detail-item">
                    <span>Transaction Date:</span>
                    <strong><?= htmlspecialchars(date('F j, Y, g:i A', strtotime($payment_data['payment_date']))) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Reference No:</span>
                    <strong><?= htmlspecialchars($payment_data['reference_number']) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Billed To:</span>
                    <strong><?= htmlspecialchars($payment_data['student_first_name'] . ' ' . $payment_data['student_last_name']) ?></strong>
                </div>
                <div class="detail-item">
                    <span>Student LRN:</span>
                    <strong><?= htmlspecialchars($payment_data['lrn']) ?></strong>
                </div>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tuition Fee Payment</td>
                        <td>₱ <?= htmlspecialchars(number_format($payment_data['amount_paid'], 2)) ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Paid</td>
                        <td>₱ <?= htmlspecialchars(number_format($payment_data['amount_paid'], 2)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="receipt-footer">
            <p>This is a computer-generated receipt and does not require a signature.</p>
        </div>
    </div>

    <div class="action-buttons">
        <button onclick="window.print()"><i class="bi bi-printer"></i> Print Receipt</button>
        <a href="results.php?lrn=<?= htmlspecialchars($lrn) ?>"><i class="bi bi-arrow-left-circle"></i> Back to Status</a>
    </div>

</body>
</html>