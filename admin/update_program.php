<?php
include 'api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_programs.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$features = $_POST['features'] ?? '';

if (!$id) {
    header('Location: manage_programs.php?error=missing_id');
    exit;
}

// Handle optional image upload
$targetDir = 'uploads/programs/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$imagePath = null;
if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
    $imagePath = $targetDir . basename($_FILES['image']['name']);
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        // If upload failed, ignore image update but log error
        error_log('Failed to move uploaded file for program id ' . $id);
        $imagePath = null;
    }
}

try {
    if ($imagePath) {
        $stmt = $pdo->prepare("UPDATE programs SET title = ?, description = ?, features = ?, image_path = ? WHERE id = ?");
        $stmt->execute([$title, $description, $features, $imagePath, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE programs SET title = ?, description = ?, features = ? WHERE id = ?");
        $stmt->execute([$title, $description, $features, $id]);
    }

    header('Location: manage_programs.php?success=1');
    exit;
} catch (PDOException $e) {
    error_log('Update program PDOException: ' . $e->getMessage());
    header('Location: manage_programs.php?error=update_failed');
    exit;
}