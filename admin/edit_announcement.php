<?php
include '../api/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_announcements.php?error=missing_id");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
    header("Location: manage_announcements.php?error=not_found");
    exit;
}

// The update logic is in update_announcement.php (or should be handled by this file if combined)
// For now, this page just displays the form.
// If form is submitted to itself:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $current_id = $_POST['id']; // Hidden input for ID

    if (empty($title) || empty($content) || empty($current_id)) {
        $error_message = "Title and content cannot be empty.";
    } else {
        try {
            $update_stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
            $update_stmt->execute([$title, $content, $current_id]);
            header("Location: manage_announcements.php?updated=1"); // Redirect to main manage page
            exit;
        } catch (PDOException $e) {
            $error_message = "Database error: Could not update announcement.";
            // Log error $e->getMessage()
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Announcement - <?= htmlspecialchars($announcement['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/edit_announcement.css"> 
</head>
<body>

<div class="page-container">
  <h2 class="page-title">Edit Announcement</h2>

  <?php if (isset($error_message)): ?>
    <div class="alert alert-danger text-center" role="alert">
      <?= htmlspecialchars($error_message) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="edit_announcement.php?id=<?= htmlspecialchars($announcement['id']) ?>" class="edit-form"> 
    <input type="hidden" name="id" value="<?= htmlspecialchars($announcement['id']) ?>">
    <div class="mb-3">
      <label for="title" class="form-label">Title *</label>
      <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="content" class="form-label">Content *</label>
      <textarea class="form-control" id="content" name="content" rows="6" required><?= htmlspecialchars($announcement['content']) ?></textarea>
    </div>
    <div class="text-center mt-4">
      <button type="submit" name="update_announcement" class="btn btn-submit-update"><i class="fas fa-save"></i> Update Announcement</button>
    </div>
  </form>

  <div class="back-container">
    <a href="manage_announcements.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>