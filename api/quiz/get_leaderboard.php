<?php
require_once '../db_connect.php'; // Adjust path as necessary

header('Content-Type: application/json');

$grade_level_filter = $_GET['gradeLevel'] ?? 'all'; // Default to all grades or filter by specific grade

try {
    $sql = "SELECT player_name, grade_level, score, total_questions, percentage, date_played 
            FROM quiz_leaderboard";

    $params = [];
    if ($grade_level_filter !== 'all' && !empty($grade_level_filter)) {
        $sql .= " WHERE grade_level = :grade_level";
        $params[':grade_level'] = $grade_level_filter;
    }

    // Order by percentage (highest first), then by score, then by date (most recent for ties)
    $sql .= " ORDER BY percentage DESC, score DESC, date_played DESC LIMIT 10"; // Show top 10

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leaderboard_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'leaderboard' => $leaderboard_data]);

} catch (PDOException $e) {
    error_log("Get leaderboard PDOException: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching leaderboard.']);
}
?>