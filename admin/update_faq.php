<?php
include 'api/db_connect.php';

$id = $_POST['id'];
$question = $_POST['question'];
$answer = $_POST['answer'];

$update = $pdo->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
$update->execute([$question, $answer, $id]);

header("Location: manage_faqs.php?success=FAQ updated successfully!");
exit;
?>
