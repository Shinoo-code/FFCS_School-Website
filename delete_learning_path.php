<?php
include 'api/db_connect.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM learning_paths WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header("Location: manage_learning_paths.php");
exit;
?>
