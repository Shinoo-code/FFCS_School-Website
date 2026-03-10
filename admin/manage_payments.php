<?php
// Centralized session initialization and security
require_once __DIR__ . '/../api/session.php';
require_once '../api/db_connect.php';
require_once '../api/csrf.php';

// Ensure only admins can access this page
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit;
}

$success_message = $_GET['success_message'] ?? '';
$error_message = $_GET['error_message'] ?? '';

    // --- Handle Add Manual Payment POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual_payment'])) {
    // Validate CSRF token
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($posted_token)) {
        header("Location: manage_payments.php?error_message=" . urlencode('Invalid or missing CSRF token.'));
        exit;
    }
    // Only admin allowed (already checked above)
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $lrn_post = trim($_POST['lrn'] ?? '');
    $amount = $_POST['amount'] ?? null;
    $payment_date_raw = trim($_POST['payment_date'] ?? '');
    if ($payment_date_raw !== '') {
        // datetime-local -> convert from 'YYYY-MM-DDTHH:MM' to 'YYYY-MM-DD HH:MM:00'
        $payment_date = str_replace('T', ' ', $payment_date_raw);
        if (strlen($payment_date) === 16) $payment_date .= ':00';
    } else {
        $payment_date = date('Y-m-d H:i:s');
    }
    $payment_method = $_POST['payment_method'] ?? 'On-site';
    $reference = trim($_POST['reference_number'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    // If enrollment_id not provided but LRN is, try to resolve enrollment by LRN
    if ((empty($enrollment_id) || !is_numeric($enrollment_id)) && $lrn_post !== '') {
        try {
            $stmt_resolve = $pdo->prepare("SELECT id FROM enrollments WHERE lrn = ? LIMIT 1");
            $stmt_resolve->execute([$lrn_post]);
            $found = $stmt_resolve->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                $enrollment_id = $found['id'];
            }
        } catch (PDOException $e) {
            error_log('LRN resolve error: ' . $e->getMessage());
        }
    }

    // Basic validation
    if (empty($enrollment_id) || !is_numeric($enrollment_id) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
        header("Location: manage_payments.php?error_message=" . urlencode('Invalid payment data.'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert payment as Approved (admin recorded on-site payment)
        $stmt_insert = $pdo->prepare("INSERT INTO payments (enrollment_id, amount_paid, payment_method, reference_number, payment_date, status) VALUES (:enrollment_id, :amount, :method, :reference, :payment_date, 'Approved')");
        $stmt_insert->execute([
            ':enrollment_id' => $enrollment_id,
            ':amount' => $amount,
            ':method' => $payment_method,
            ':reference' => $reference,
            ':payment_date' => $payment_date,
        ]);

        // Update enrollment balance
        $stmt_en = $pdo->prepare("SELECT outstanding_balance FROM enrollments WHERE id = ? FOR UPDATE");
        $stmt_en->execute([$enrollment_id]);
        $en = $stmt_en->fetch(PDO::FETCH_ASSOC);
        $current_balance = isset($en['outstanding_balance']) ? (float)$en['outstanding_balance'] : 0.0;
        $new_balance = $current_balance - (float)$amount;
        $sql_update = "UPDATE enrollments SET outstanding_balance = :new_balance";
        if ($new_balance <= 0) {
            $sql_update .= ", status = 'Enrolled'";
        }
        $sql_update .= " WHERE id = :id";
        $stmt_update_en = $pdo->prepare($sql_update);
        $stmt_update_en->execute([':new_balance' => $new_balance, ':id' => $enrollment_id]);

        $pdo->commit();
        header("Location: manage_payments.php?success_message=" . urlencode('Manual payment recorded successfully.'));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Error inserting manual payment: ' . $e->getMessage());
        header("Location: manage_payments.php?error_message=" . urlencode('Database error recording payment.'));
        exit;
    }
}

// --- Fetch student financial summaries ---
$student_finances = [];
try {
    // Added enrollment ID (e.id) to the select statement
    $stmt_finances = $pdo->query("
        SELECT
            e.id,
            e.student_first_name,
            e.student_last_name,
            e.lrn,
            e.status,
            e.total_tuition,
            e.outstanding_balance
        FROM enrollments e
        WHERE e.status = 'Enrolled' OR e.total_tuition > 0 OR e.outstanding_balance > 0
        ORDER BY e.student_last_name ASC
    ");
    $student_finances = $stmt_finances->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching student finances: " . $e->getMessage());
    $error_message = "Could not retrieve student financial data.";
}


// --- Fetch all individual payment entries for transaction history ---
$payments = [];
try {
    $stmt_payments = $pdo->query("
        SELECT
            p.*,
            e.student_first_name,
            e.student_last_name,
            e.lrn
        FROM payments p
        JOIN enrollments e ON p.enrollment_id = e.id
        ORDER BY p.payment_date DESC
    ");
    $payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    // $payments remains []; // Already initialized
    $error_message .= " Could not retrieve payment transaction data."; // Append error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - FFCS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/manage_feedback.css">
    <link rel="stylesheet" href="../css/manage_payments.css">
</head>
<body>

<?php echo "\n<script>window.APP_CSRF_TOKEN = '" . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . "';</script>\n"; ?>

<div class="page-container" style="max-width: 1200px;">
    <h2 class="page-title">Manage Student Payments</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="search-container">
        <label for="searchInput" class="form-label">Search by Name or LRN:</label>
        <input type="text" id="searchInput" class="search-input" placeholder="Start typing to filter...">
    </div>


    <div class="card mb-5">
        <div class="card-header">
            <h5 class="mb-0">Student Financial Status</h5>
            <div class="float-end">
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addManualPaymentModal"><i class="fas fa-plus-circle"></i> Add Manual Payment</button>
            </div>
        </div>
        <div class="card-body">
            <div class="filter-buttons mb-3">
                <button class="btn btn-sm btn-outline-primary active" data-status="all">All Students</button>
                <button class="btn btn-sm btn-outline-danger" data-status="balance">Has Balance</button>
                <button class="btn btn-sm btn-outline-success" data-status="paid">Fully Paid</button>
                <button class="btn btn-sm btn-outline-warning" data-status="none">No Payments Made</button>
            </div>
            <div class="table-responsive">
                <table class="feedback-table table" id="student-finances-table">
                    <thead>
                        <tr>
                            <th>Student Name</th> <th>LRN</th> <th>Total Tuition</th> <th>Outstanding Balance</th> <th>Payment Status</th> <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($student_finances)): ?>
                            <tr><td colspan="6" class="text-center">No student financial data to display.</td></tr>
                        <?php else: ?>
                            <?php foreach ($student_finances as $student):
                                $total_tuition = (float)($student['total_tuition'] ?? 0);
                                $balance = (float)($student['outstanding_balance'] ?? 0);
                                $total_paid = $total_tuition - $balance;
                                $status_text = ''; $status_class = ''; $data_status = '';
                                if ($balance <= 0 && $total_tuition > 0 && $total_paid > 0) { $status_text = 'Fully Paid'; $status_class = 'status-paid'; $data_status = 'paid'; }
                                elseif ($total_paid == 0 && $total_tuition > 0) { $status_text = 'No Payments Made'; $status_class = 'status-no-payment'; $data_status = 'none'; }
                                elseif ($balance > 0) { $status_text = 'Has Balance'; $status_class = 'status-balance'; $data_status = 'balance'; }
                                else { $status_text = 'N/A'; $data_status = 'all'; }
                            ?>
                            <tr data-status="<?= $data_status ?>" data-searchable-name="<?= htmlspecialchars(strtolower($student['student_first_name'] . ' ' . $student['student_last_name'])) ?>" data-searchable-lrn="<?= htmlspecialchars(strtolower($student['lrn'] ?? '')) ?>">
                                <td><?= htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']) ?></td>
                                <td><?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></td>
                                <td>₱<?= htmlspecialchars(number_format($total_tuition, 2)) ?></td>
                                <td>₱<?= htmlspecialchars(number_format($balance, 2)) ?></td>
                                <td class="<?= $status_class ?>"><?= $status_text ?></td>
                                <td> <?php if ($balance > 0): ?> <button class="btn btn-sm btn-warning send-warning-btn" data-enrollment-id="<?= $student['id'] ?>">Send Warning</button> <?php endif; ?> </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="no-results-row" style="display: none;"><td colspan="6">No students match the current filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="payment-history-section" class="printable-area">
        <h3 class="mt-4 print-title">Payment Transaction History Report</h3> <div class="d-flex justify-content-between align-items-center mb-3">
             <h3 class="mt-4 mb-0 d-inline-block">Payment Transaction History</h3>
             <div>
                <button class="btn btn-sm btn-secondary" id="printSpecificBtn"><i class="fas fa-print"></i> Print Filtered Report</button>
                <button class="btn btn-sm btn-primary" id="printAllBtn"><i class="fas fa-print"></i> Print Full Report</button>
             </div>
        </div>
        <div class="table-responsive">
            <table class="feedback-table table" id="payment-history-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>LRN</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Reference No.</th>
                        <th>Date</th>
                        <th class="proof-col">Proof</th> <th>Status</th>
                        <th class="actions-col">Actions</th> </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr> <td colspan="9" class="text-center">No payment submissions yet.</td> </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr data-searchable-name="<?= htmlspecialchars(strtolower($payment['student_first_name'] . ' ' . $payment['student_last_name'])) ?>" data-searchable-lrn="<?= htmlspecialchars(strtolower($payment['lrn'] ?? '')) ?>">
                                <td><?= htmlspecialchars($payment['student_first_name'] . ' ' . $payment['student_last_name']) ?></td>
                                <td><?= htmlspecialchars($payment['lrn'] ?? 'N/A') ?></td>
                                <td>₱<?= htmlspecialchars(number_format($payment['amount_paid'], 2)) ?></td>
                                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                <td><?= htmlspecialchars($payment['reference_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($payment['payment_date']))) ?></td>
                                <td class="proof-col">
                                    <?php if (!empty($payment['proof_of_payment_url'])): ?>
                                        <a href="<?= htmlspecialchars($payment['proof_of_payment_url']) ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                    <?php else: ?> N/A <?php endif; ?>
                                </td>
                                <td>
                                    <?php $paymentStatusClass = ''; switch (strtolower($payment['status'])) { case 'approved': $paymentStatusClass = 'status-approved'; break; case 'declined': case 'for verification': $paymentStatusClass = 'status-declined'; break; default: $paymentStatusClass = 'status-pending'; break; } ?>
                                    <?php $displayPaymentStatus = (strtolower($payment['status']) === 'declined') ? 'For Verification' : $payment['status']; ?>
                                    <span class="status-label <?= $paymentStatusClass ?>"> <?= htmlspecialchars($displayPaymentStatus) ?> </span>
                                </td>
                                <td class="actions-col">
                                    <?php if (strtolower($payment['status']) === 'pending'): ?>
                                    <div class="action-buttons-group">
                                        <form action="../api/payments/update_status.php" method="post" style="display:inline;">
                                            <?php echo csrf_input_field(); ?>
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($payment['id']) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve Payment"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form action="../api/payments/update_status.php" method="post" style="display:inline;margin-left:6px;">
                                            <?php echo csrf_input_field(); ?>
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($payment['id']) ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Mark For Verification"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                         <tr class="no-results-row" style="display: none;"><td colspan="9">No payments match the current filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> <div class="back-container mt-4">
        <a href="../dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Add Manual Payment Modal -->
<div class="modal fade" id="addManualPaymentModal" tabindex="-1" aria-labelledby="addManualPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="manage_payments.php">
                <?php echo csrf_input_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addManualPaymentModalLabel">Add Manual Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_manual_payment" value="1">
                                <div class="mb-3">
                                    <label for="lrn_input" class="form-label">Student LRN</label>
                                    <div class="input-group">
                                        <input type="text" name="lrn" id="lrn_input" class="form-control" placeholder="Enter student LRN" aria-label="Student LRN">
                                        <button type="button" id="lrn_lookup_btn" class="btn btn-outline-secondary">Lookup</button>
                                    </div>
                                    <input type="hidden" name="enrollment_id" id="enrollment_id_hidden" value="">
                                    <div id="lrn_lookup_result" class="form-text mt-2 text-muted">Enter LRN and click Lookup to fetch student.</div>
                                </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (PHP)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date & Time</label>
                        <input type="datetime-local" name="payment_date" id="payment_date" class="form-control">
                        <small class="text-muted">Leave blank to use current date/time.</small>
                    </div>
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference / Receipt No. (optional)</label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="remark" class="form-label">Remark (optional)</label>
                        <textarea name="remark" id="remark" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const financesTableBody = document.getElementById('student-finances-table').querySelector('tbody');
    const historyTableBody = document.getElementById('payment-history-table').querySelector('tbody');
    const filterButtons = document.querySelectorAll('.filter-buttons .btn');
    const printAllBtn = document.getElementById('printAllBtn');
    const printSpecificBtn = document.getElementById('printSpecificBtn');
    const printTitle = document.querySelector('.print-title'); // Get the title element
    let currentStatusFilter = 'all';

    // --- Combined Filter Function ---
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        filterTable(financesTableBody, searchTerm, currentStatusFilter);
        filterTable(historyTableBody, searchTerm, 'all');
        updatePrintSpecificButtonState(); // Update print button state based on search
    }

    // --- Table Filtering Logic ---
    function filterTable(tbody, searchTerm, statusFilter) {
        const rows = tbody.querySelectorAll('tr:not(.no-results-row)');
        const noResultsRow = tbody.querySelector('.no-results-row');
        let hasVisibleRows = false;

        rows.forEach(row => {
            const studentName = row.dataset.searchableName || '';
            const studentLrn = row.dataset.searchableLrn || '';
            const rowStatus = row.dataset.status || 'all';

            const matchesSearch = searchTerm === '' || studentName.includes(searchTerm) || studentLrn.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || rowStatus === statusFilter;

            const shouldShow = matchesSearch && (tbody === historyTableBody || matchesStatus);

            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) {
                hasVisibleRows = true;
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = hasVisibleRows ? 'none' : '';
        }
    }

    // --- Print Button Logic ---
    printAllBtn.addEventListener('click', function() {
        // Temporarily show all rows in history table for printing all
        const historyRows = historyTableBody.querySelectorAll('tr:not(.no-results-row)');
        historyRows.forEach(row => row.style.display = ''); // Show all rows

        // Set the print title
        if (printTitle) {
            printTitle.textContent = "Full Payment Transaction History Report";
        }
        window.print(); // Trigger browser print

        // Re-apply filters after printing
        applyFilters();
    });

    printSpecificBtn.addEventListener('click', function() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm === '') {
            alert('Please search for a specific student first using the search box above.');
            return;
        }

        // Filters are already applied by applyFilters(), just print the current view
        // Set the print title for the specific report
         if (printTitle) {
             printTitle.textContent = `Payment Transaction History for: ${searchTerm}`;
         }
        window.print();
    });

    // --- Update Print Specific Button State ---
    function updatePrintSpecificButtonState() {
        const searchTerm = searchInput.value.trim();
        // Enable the specific print button only if there's a search term
        printSpecificBtn.disabled = searchTerm === '';
        printSpecificBtn.title = searchTerm === '' ? 'Search for a student first' : 'Print report for the searched student(s)';
    }


    // --- Event Listeners ---
    searchInput.addEventListener('input', applyFilters);

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-outline-primary');
            });

            this.classList.add('active', 'btn-primary');
            this.classList.remove('btn-outline-primary');
            currentStatusFilter = this.dataset.status;
            applyFilters();
        });
    });

    // --- Send Warning Button Logic ---
    const warningButtons = document.querySelectorAll('.send-warning-btn');
    warningButtons.forEach(button => {
        button.addEventListener('click', async function () {
            const enrollmentId = this.dataset.enrollmentId;
            if (!enrollmentId) return;
            if (!confirm('Send payment warning email to parent/guardian for this student?')) return;
            const btn = this;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Sending...';
            try {
                const resp = await fetch('api/payments/send_warning.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: JSON.stringify({ enrollment_id: enrollmentId, csrf_token: window.APP_CSRF_TOKEN || '' })
                });
                const data = await resp.json();
                if (data && data.success) {
                    alert(data.message || 'Warning sent successfully.');
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                    btn.textContent = 'Sent';
                } else {
                    alert(data.message || 'Failed to send warning.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while sending the warning. See console for details.');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            try { var bsAlert = new bootstrap.Alert(alert); bsAlert.close(); } catch (e) { alert.style.display = 'none'; }
        }, 6000);
    });

    // Initial filter and print button state
    applyFilters();
    
    // --- LRN lookup for Add Manual Payment modal ---
    try {
        // Build a lookup map from PHP student data
        const studentMap = {};
        <?php foreach ($student_finances as $s):
            $lrn_js = htmlspecialchars($s['lrn'] ?? '');
            $name_js = htmlspecialchars($s['student_first_name'] . ' ' . $s['student_last_name']);
            $id_js = htmlspecialchars($s['id']);
            if (!empty($s['lrn'])):
        ?>
            studentMap["<?= $lrn_js ?>"] = { id: "<?= $id_js ?>", name: "<?= $name_js ?>" };
        <?php endif; endforeach; ?>

        const lrnInput = document.getElementById('lrn_input');
        const lookupBtn = document.getElementById('lrn_lookup_btn');
        const resultDiv = document.getElementById('lrn_lookup_result');
        const hiddenEnrollment = document.getElementById('enrollment_id_hidden');

        function doLookup() {
            const lrn = (lrnInput && lrnInput.value || '').trim();
            if (!lrn) {
                resultDiv.textContent = 'Please enter an LRN to lookup.';
                hiddenEnrollment.value = '';
                return;
            }
            const entry = studentMap[lrn];
            if (entry) {
                resultDiv.textContent = `Found: ${entry.name}`;
                hiddenEnrollment.value = entry.id;
            } else {
                resultDiv.textContent = 'No student found with that LRN. You can still submit if you know the LRN and it exists in the DB.';
                hiddenEnrollment.value = '';
            }
        }

        if (lookupBtn) lookupBtn.addEventListener('click', doLookup);
        if (lrnInput) lrnInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doLookup(); } });
    } catch (err) {
        console.warn('LRN lookup init failed', err);
    }
});
</script>

</body>
</html>