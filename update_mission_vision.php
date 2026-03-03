<?php
include 'api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mission = $_POST['mission'] ?? '';
    $vision = $_POST['vision'] ?? '';

    // Update or Insert mission
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mission_vision WHERE type = 'mission'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $update = $pdo->prepare("UPDATE mission_vision SET content = ? WHERE type = 'mission'");
        $update->execute([$mission]);
    } else {
        $insert = $pdo->prepare("INSERT INTO mission_vision (type, content) VALUES ('mission', ?)");
        $insert->execute([$mission]);
    }

    // Update or Insert vision
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mission_vision WHERE type = 'vision'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $update = $pdo->prepare("UPDATE mission_vision SET content = ? WHERE type = 'vision'");
        $update->execute([$vision]);
    } else {
        $insert = $pdo->prepare("INSERT INTO mission_vision (type, content) VALUES ('vision', ?)");
        $insert->execute([$vision]);
    }

    // Redirect back with success flag
    header("Location: edit_mission_vision.php?success=1");
    exit;
}
?>
