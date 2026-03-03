<?php
include 'api/db_connect.php';

$id = $_POST['id'];
$stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
$stmt->execute([$id]);

header("Location: manage_activities.php?deleted=1");
