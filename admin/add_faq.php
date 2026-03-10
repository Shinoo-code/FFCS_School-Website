<?php
include '../api/db_connect.php';

$question = $_POST['question'];
$answer = $_POST['answer'];

$stmt = $pdo->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
$stmt->execute([$question, $answer]);

header("Location: manage_faqs.php?success=FAQ added successfully!");
exit;
?>
