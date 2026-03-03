<?php
include 'api/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: manage_programs.php");
exit;
?>
