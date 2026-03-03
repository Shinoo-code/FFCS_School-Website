<?php
include 'api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO learning_paths (year, icon, title, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['year'], $_POST['icon'], $_POST['title'], $_POST['description']]);
    header("Location: manage_learning_paths.php");
    exit;
}
?>
