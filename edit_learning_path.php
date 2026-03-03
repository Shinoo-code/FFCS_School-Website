<?php
include 'api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE learning_paths SET year = ?, icon = ?, title = ?, description = ? WHERE id = ?");
    $stmt->execute([$_POST['year'], $_POST['icon'], $_POST['title'], $_POST['description'], $_POST['id']]);
    // Redirect with success=1 query parameter
    header("Location: manage_learning_paths.php?success=1");
    exit;
}
?>
