<?php
include 'api/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_news.php?error=missing_id");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news_item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news_item) {
    header("Location: manage_news.php?error=not_found");
    exit;
}

// Update logic is in update_news.php (or this file if combined)
// If form submits to self:
$error_message = '';
$success_message_from_update = $_GET['updated_news_success'] ?? ''; // Check for message from update script

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_news_item'])) {
    $current_id = $_POST['id'];
    $title = trim($_POST['title']);
    $date = $_POST['date'];
    $description = trim($_POST['description']);
    $image_path_to_save = $news_item['image_path']; // Keep current image by default

    if (empty($title) || empty($date) || empty($description)) {
        $error_message = "Title, Date, and Description are required.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['image']['name'])) {
            $targetDir = "uploads/news/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $imageExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($_FILES["image"]["name"]));
            $uniqueImageName = uniqid('news_', true) . '_' . $safe_filename;
            $new_image_path = $targetDir . $uniqueImageName;

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageExtension, $allowed_types)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $new_image_path)) {
                    // Optionally delete old image if it exists and is different
                    if ($news_item['image_path'] && file_exists($news_item['image_path']) && $news_item['image_path'] !== $new_image_path) {
                        @unlink($news_item['image_path']);
                    }
                    $image_path_to_save = $new_image_path;
                } else {
                    $error_message = "Failed to move uploaded image.";
                }
            } else {
                $error_message = "Invalid file type. Allowed types: " . implode(', ', $allowed_types);
            }
        }

        if (empty($error_message)) {
            try {
                $update_stmt = $pdo->prepare("UPDATE news SET title = ?, date = ?, description = ?, image_path = ? WHERE id = ?");
                $update_stmt->execute([$title, $date, $description, $image_path_to_save, $current_id]);
                header("Location: manage_news.php?updated=1"); // Redirect to main manage page
                exit;
            } catch (PDOException $e) {
                $error_message = "Database error: Could not update news item.";
                // Log $e->getMessage()
            }
        }
    }
     // Re-fetch if update happened on this page (or rely on redirect)
    if(empty($error_message) && !isset($_GET['updated_news_success'])){ // Avoid re-fetch if already redirected
        $stmt->execute([$id]); // Re-fetch data after potential update
        $news_item = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit News Item - <?= htmlspecialchars($news_item['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_news.css">
</head>
<body>

<div class="page-container">
  <h2 class="page-title">Edit News Item</h2>

  <?php if ($error_message): ?>
    <div class="alert alert-danger text-center" role="alert">
      <?= htmlspecialchars($error_message) ?>
    </div>
  <?php endif; ?>
  <?php if ($success_message_from_update): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message_from_update) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>


  <form action="edit_news.php?id=<?= htmlspecialchars($news_item['id']) ?>" method="POST" enctype="multipart/form-data" class="edit-form">
    <input type="hidden" name="id" value="<?= htmlspecialchars($news_item['id']) ?>">

    <div class="mb-3">
      <label for="title" class="form-label">Title *</label>
      <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($news_item['title']) ?>" required>
    </div>

    <div class="mb-3">
      <label for="date" class="form-label">Date *</label>
      <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($news_item['date']) ?>" required>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Description *</label>
      <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($news_item['description']) ?></textarea>
    </div>

    <div class="mb-3 image-preview-container">
        <label class="form-label d-block">Current Image:</label>
        <?php if (!empty($news_item['image_path']) && file_exists($news_item['image_path'])): ?>
            <img src="<?= htmlspecialchars($news_item['image_path']) ?>" alt="Current News Image" class="image-preview img-thumbnail">
        <?php else: ?>
            <p class="text-muted">No image currently uploaded or file is missing.</p>
        <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="image" class="form-label">Change Image (optional):</label>
      <input type="file" class="form-control" id="image" name="image">
      <small class="form-text text-muted">Only select a file if you want to replace the current image.</small>
    </div>

    <div class="text-center mt-4">
      <button type="submit" name="update_news_item" class="btn btn-submit-update"><i class="fas fa-save"></i> Update News Item</button>
    </div>
  </form>

  <div class="back-container">
    <a href="manage_news.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const successAlertUpdate = document.querySelector('.alert-success');
  if (successAlertUpdate) {
    setTimeout(() => {
      if (bootstrap && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
        bootstrap.Alert.getOrCreateInstance(successAlertUpdate).close();
      } else {
        successAlertUpdate.style.display = 'none';
      }
    }, 3000);
  }
</script>
</body>
</html>