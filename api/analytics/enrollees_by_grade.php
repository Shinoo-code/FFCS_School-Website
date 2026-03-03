<?php
// api/analytics/enrollees_by_grade.php
// Returns enrollment counts by grade level for the last 12 months (monthly buckets)
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/session.php';
require_once __DIR__ . '/../db_connect.php';

// Require login and admin role
if (empty($_SESSION['faculty_id']) || empty($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    // Build last 12 month labels
    $months = [];
    $period = new DatePeriod(new DateTime(date('Y-m-01')), new DateInterval('P1M'), 12);
    // We want the previous 11 months plus current month, so shift start back 11 months
    $start = new DateTime();
    $start->modify('-11 months');
    $start->modify('first day of this month');
    $months = [];
    $iter = clone $start;
    for ($i = 0; $i < 12; $i++) {
        $months[] = $iter->format('Y-m');
        $iter->modify('+1 month');
    }

    $start_date = $start->format('Y-m-01 00:00:00');

    $sql = "SELECT grade_level, DATE_FORMAT(submission_timestamp, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM enrollments
            WHERE submission_timestamp >= :start_date
            GROUP BY grade_level, ym";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_date' => $start_date]);
    $rows = $stmt->fetchAll();

    // Build mapping grade => { ym => cnt }
    $map = [];
    foreach ($rows as $r) {
        $grade = $r['grade_level'] ?? 'Unknown';
        $ym = $r['ym'];
        $cnt = (int)$r['cnt'];
        if (!isset($map[$grade])) $map[$grade] = [];
        $map[$grade][$ym] = $cnt;
    }

    // Build datasets aligned with $months
    $datasets = [];
    foreach ($map as $grade => $vals) {
        $data = [];
        foreach ($months as $m) {
            $data[] = isset($vals[$m]) ? (int)$vals[$m] : 0;
        }
        $datasets[] = [
            'label' => $grade,
            'data' => $data
        ];
    }

    echo json_encode(['success' => true, 'labels' => $months, 'datasets' => $datasets]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $e->getMessage()]);
}

?>
