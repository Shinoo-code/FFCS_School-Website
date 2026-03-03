<?php
require_once '../db_connect.php'; // Adjust path as necessary

header('Content-Type: application/json');

$response_data = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $player_name = isset($input['playerName']) && !empty(trim($input['playerName'])) ? trim($input['playerName']) : 'Anonymous';
    $grade_level = $input['gradeLevel'] ?? null;
    $score = isset($input['score']) ? (int)$input['score'] : null;
    $total_questions = isset($input['totalQuestions']) ? (int)$input['totalQuestions'] : null;

    if ($grade_level === null || $score === null || $total_questions === null || $total_questions == 0) {
        $response_data['message'] = 'Missing required score data.';
        echo json_encode($response_data);
        exit;
    }

    // Sanitize player_name further if needed
    $player_name_sanitized = htmlspecialchars(substr($player_name, 0, 100)); // Limit length

    $percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100, 2) : 0.00;

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO quiz_leaderboard (player_name, grade_level, score, total_questions, percentage, date_played)
             VALUES (:player_name, :grade_level, :score, :total_questions, :percentage, NOW())"
        );

        $stmt->bindParam(':player_name', $player_name_sanitized);
        $stmt->bindParam(':grade_level', $grade_level);
        $stmt->bindParam(':score', $score, PDO::PARAM_INT);
        $stmt->bindParam(':total_questions', $total_questions, PDO::PARAM_INT);
        $stmt->bindParam(':percentage', $percentage);

        if ($stmt->execute()) {
            $response_data['success'] = true;
            $response_data['message'] = 'Score submitted successfully!';
        } else {
            $response_data['message'] = 'Failed to submit score to the database.';
        }
    } catch (PDOException $e) {
        error_log("Quiz score submission PDOException: " . $e->getMessage());
        $response_data['message'] = 'Database error occurred during score submission.';
    }
}

echo json_encode($response_data);
?>