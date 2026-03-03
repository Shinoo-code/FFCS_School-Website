<?php
// Admin page: View recent notification attempts for payments and enrollments
require_once __DIR__ . '/api/session.php';
require_once __DIR__ . '/api/db_connect.php';

// Admin only
if (!isset($_SESSION['faculty_id']) || !isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$payment_notifications = [];
$enrollment_notifications = [];
try {
    $stmt = $pdo->query("SELECT id, payment_id, enrollment_id, recipients, result, error_message, sent_at FROM payment_notifications ORDER BY sent_at DESC LIMIT 200");
    $payment_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Failed to fetch payment_notifications: ' . $e->getMessage());
}

try {
    $stmt2 = $pdo->query("SELECT id, enrollment_id, recipients, status, result, error_message, sent_at FROM enrollment_notifications ORDER BY sent_at DESC LIMIT 200");
    $enrollment_notifications = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Failed to fetch enrollment_notifications: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Notifications - FFCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/common.css" rel="stylesheet">
</head>
<body>

<!-- Minimal header (page is part of admin area) -->
<nav class="navbar navbar-light bg-white border-bottom">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h4">Notification Logs</span>
    <div>
      <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
  </div>
</nav>

<main class="container my-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Recent notification attempts</h5>

    <ul class="nav nav-tabs" id="notifTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">Payment Notifications</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab">Enrollment Notifications</button>
      </li>
    </ul>
    <div class="tab-content mt-3">
      <div class="tab-pane fade show active" id="payments" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-striped table-sm align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Payment ID</th>
                <th>Enrollment ID</th>
                <th>Recipients</th>
                <th>Result</th>
                <th>Error</th>
                <th>Sent At</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($payment_notifications as $row): 
                  // Decode recipients if stored as JSON array
                  $recipsRaw = $row['recipients'] ?? '';
                  $recipsArr = json_decode($recipsRaw, true);
                  if (is_array($recipsArr)) {
                      $recipsDisplay = implode(', ', array_map(function($e){ return htmlspecialchars((string)$e); }, $recipsArr));
                  } else {
                      $recipsDisplay = htmlspecialchars((string)$recipsRaw);
                  }
                  $errorMsg = $row['error_message'] ?? '';
            ?>
              <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['payment_id']) ?></td>
                <td><?= htmlspecialchars($row['enrollment_id']) ?></td>
                <td style="max-width:320px;word-break:break-word"><?= $recipsDisplay ?></td>
                <td>
                  <?php if (strtolower(($row['result'] ?? '')) === 'success'): ?>
                    <span class="badge bg-success">Success</span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?= htmlspecialchars($row['result'] ?? 'failure') ?></span>
                  <?php endif; ?>
                </td>
                <td style="max-width:320px;word-break:break-word"><?= htmlspecialchars((string)($errorMsg ?? '')) ?></td>
                <td><?= htmlspecialchars($row['sent_at'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="enrollments" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Enrollment ID</th>
                <th>Recipients</th>
                <th>Status</th>
                <th>Result</th>
                <th>Error</th>
                <th>Sent At</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($enrollment_notifications as $row): 
                  $recipsRaw = $row['recipients'] ?? '';
                  $recipsArr = json_decode($recipsRaw, true);
                  if (is_array($recipsArr)) {
                      $recipsDisplay = implode(', ', array_map(function($e){ return htmlspecialchars((string)$e); }, $recipsArr));
                  } else {
                      $recipsDisplay = htmlspecialchars((string)$recipsRaw);
                  }
                  $err = $row['error_message'] ?? '';
            ?>
              <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['enrollment_id']) ?></td>
                <td style="max-width:320px;word-break:break-word"><?= $recipsDisplay ?></td>
                <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                <td><?php if (strtolower(($row['result'] ?? '')) === 'success') echo '<span class="badge bg-success">Success</span>'; else echo '<span class="badge bg-warning">' . htmlspecialchars($row['result'] ?? '') . '</span>'; ?></td>
                <td style="max-width:320px;word-break:break-word"><?= htmlspecialchars((string)$err) ?></td>
                <td><?= htmlspecialchars($row['sent_at'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
