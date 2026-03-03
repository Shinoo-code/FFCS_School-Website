<?php
include 'api/db_connect.php';

$id = $_POST['id'];

$delete = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
$delete->execute([$id]);

header("Location: manage_faqs.php?success=FAQ deleted successfully!");
exit;
?>
