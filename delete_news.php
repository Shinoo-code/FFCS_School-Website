<?php
include 'api/db_connect.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: manage_news.php?deleted=1");
    exit;
}
?>
