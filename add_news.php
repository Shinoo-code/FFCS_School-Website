<?php
include 'api/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    // Check and handle uploaded image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $imageUploadPath = 'uploads/news/' . $imageName;

        // Make sure uploads/news/ folder exists
        if (!is_dir('uploads/news')) {
            mkdir('uploads/news', 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($imageTmpPath, $imageUploadPath)) {
            $stmt = $pdo->prepare("INSERT INTO news (title, date, description, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $date, $description, $imageUploadPath]);
        }
    }

    header("Location: manage_news.php?success=1");
    exit;
}
?>
