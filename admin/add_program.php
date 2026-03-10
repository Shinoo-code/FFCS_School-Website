<?php
include '../api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $features = $_POST['features']; // expect pipe-separated features

    // Handle image upload
    $targetDir = "uploads/programs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = $targetDir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }

    $stmt = $pdo->prepare("INSERT INTO programs (title, description, features, image_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $features, $imagePath]);

    header("Location: manage_programs.php?success=1");
    exit;
}
?>
