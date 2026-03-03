<?php
include 'api/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_programs.php?error=missing_id");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$id]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    header("Location: manage_programs.php?error=not_found");
    exit;
}

// The update logic is in update_program.php
$success_message = $_GET['updated_program_success'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Program - <?= htmlspecialchars($program['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_program.css"> 
</head>
<body>

<div class="page-container">
  <h2 class="page-title">Edit Program</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="update_program.php" method="POST" enctype="multipart/form-data" class="edit-form">
    <input type="hidden" name="id" value="<?= htmlspecialchars($program['id']) ?>">

    <div class="mb-3">
      <label for="title" class="form-label">Program Title *</label>
      <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($program['title']) ?>" required>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Description *</label>
      <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($program['description']) ?></textarea>
    </div>

    <div class="mb-3">
      <label for="features" class="form-label">Key Features *</label>
      <textarea class="form-control" id="features" name="features" rows="3" required placeholder="Feature 1 | Feature 2"><?= htmlspecialchars($program['features']) ?></textarea>
      <small class="form-text text-muted">Use a pipe '|' to separate features.</small>
    </div>

    <div class="mb-3 image-preview-container">
        <label class="form-label d-block">Current Image:</label>
        <?php if (!empty($program['image_path']) && file_exists($program['image_path'])): ?>
            <img src="<?= htmlspecialchars($program['image_path']) ?>" alt="Current Program Image" class="image-preview img-thumbnail">
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
      <button type="submit" class="btn btn-submit-update"><i class="fas fa-save"></i> Update Program</button>
    </div>
  </form>

  <div class="back-container">
    <a href="manage_programs.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const successAlert = document.querySelector('.alert-success');
  if (successAlert) {
    setTimeout(() => {
      if (bootstrap && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
        bootstrap.Alert.getOrCreateInstance(successAlert).close();
      } else {
        successAlert.style.display = 'none';
      }
    }, 3000);
  }
</script>
</body>
</html>