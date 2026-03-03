<?php
include 'api/db_connect.php'; // Establishes $pdo

// Fetch all learning paths for display
$stmt = $pdo->query("SELECT * FROM learning_paths ORDER BY year ASC");
$learning_paths = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Learning path saved successfully!";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_message = "Learning path deleted successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Learning Paths - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_learning_paths.css">
</head>
<body>

<div class="page-container">
  <h2 class="text-center">Manage Learning Paths</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="add_learning_path.php" method="POST" class="mb-4 add-form">
    <h3 class="form-section-title">Add New Learning Path</h3>
    <div class="row g-3 align-items-end">
      <div class="col-md-2">
        <label for="year" class="form-label">Year *</label>
        <input type="text" class="form-control" id="year" name="year" placeholder="e.g., 2024" required>
      </div>
      <div class="col-md-2">
        <label for="icon" class="form-label">Icon *</label>
        <input type="text" class="form-control" id="icon" name="icon" placeholder="e.g., 🎓" required>
      </div>
      <div class="col-md-3">
        <label for="title" class="form-label">Title *</label>
        <input type="text" class="form-control" id="title" name="title" placeholder="Path Title" required>
      </div>
      <div class="col-md-4">
        <label for="description" class="form-label">Description *</label>
        <input type="text" class="form-control" id="description" name="description" placeholder="Path Description" required>
      </div>
      <div class="col-md-1 d-grid">
        <button type="submit" class="btn add-learning-path-btn"><i class="fas fa-plus-circle"></i> Add</button>
      </div>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing Learning Paths</h3>
  <?php if (empty($learning_paths)): ?>
    <p class="text-center text-muted">No learning paths found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Year</th>
            <th>Icon</th>
            <th>Title</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($learning_paths as $lp): ?>
          <tr>
            <form action="edit_learning_path.php" method="POST">
              <td><input type="text" name="year" value="<?= htmlspecialchars($lp['year']) ?>" class="form-control form-control-sm" required></td>
              <td><input type="text" name="icon" value="<?= htmlspecialchars($lp['icon']) ?>" class="form-control form-control-sm" required></td>
              <td><input type="text" name="title" value="<?= htmlspecialchars($lp['title']) ?>" class="form-control form-control-sm" required></td>
              <td><input type="text" name="description" value="<?= htmlspecialchars($lp['description']) ?>" class="form-control form-control-sm" required></td>
              <td>
                <div class="action-buttons-group">
                  <input type="hidden" name="id" value="<?= $lp['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save"></i> Save</button>
                  <a href="delete_learning_path.php?id=<?= $lp['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this learning path?');"><i class="fas fa-trash-alt"></i> Delete</a>
                </div>
              </td>
            </form>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="back-container mt-4">
    <a href="dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const successAlert = document.querySelector('.alert-success');
  if (successAlert) {
    setTimeout(() => {
      bootstrap.Alert.getOrCreateInstance(successAlert).close();
    }, 3000);
  }
</script>
</body>
</html>