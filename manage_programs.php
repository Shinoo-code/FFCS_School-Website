<?php
include 'api/db_connect.php'; // Establishes $pdo

// Fetch all programs for display
$stmt = $pdo->query("SELECT * FROM programs ORDER BY display_order ASC, id DESC");
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Program saved successfully!";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_message = "Program deleted successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Programs - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_programs.css">
</head>
<body>

<div class="container page-container"> 
  <h2 class="text-center">Manage Programs</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="add_program.php" method="POST" enctype="multipart/form-data" class="mb-4 add-form">
    <h3 class="form-section-title">Add New Program</h3>
    <div class="row g-3">
      <div class="col-md-4 mb-3">
        <label for="prog_title" class="form-label">Title *</label>
        <input type="text" id="prog_title" name="title" class="form-control" placeholder="Program Title" required />
      </div>
      <div class="col-md-8 mb-3">
        <label for="prog_description" class="form-label">Description *</label>
        <textarea id="prog_description" name="description" class="form-control" rows="2" placeholder="Program Description" required></textarea>
      </div>
      <div class="col-md-8 mb-3">
        <label for="prog_features" class="form-label">Key Features *</label>
        <textarea id="prog_features" name="features" class="form-control" rows="2" placeholder="Feature 1 | Feature 2 | Feature 3" required></textarea>
        <small class="form-text text-muted">Use a pipe '|' to separate each feature.</small>
      </div>
      <div class="col-md-4 mb-3">
        <label for="prog_image" class="form-label">Image *</label>
        <input type="file" id="prog_image" name="image" class="form-control" required />
      </div>
    </div>
    <div class="text-center-btn mt-2"> 
      <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Program</button>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing Programs</h3>
  <?php if (empty($programs)): ?>
    <p class="text-center text-muted">No programs found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Description</th>
            <th>Features</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($programs as $program): ?>
            <tr>
              <td style="width: 150px;"> 
                <form action="update_program.php" method="POST" enctype="multipart/form-data" class="edit-form-in-table">
                  <input type="hidden" name="id" value="<?= $program['id'] ?>" />
                  <img src="<?= htmlspecialchars($program['image_path']) ?>" alt="Program Image" class="program-image img-thumbnail mb-2" />
                  <input type="file" name="image" class="form-control form-control-sm" />
              </td>
              <td><input type="text" name="title" value="<?= htmlspecialchars($program['title']) ?>" class="form-control form-control-sm" required /></td>
              <td><textarea name="description" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars($program['description']) ?></textarea></td>
              <td>
                <textarea name="features" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars($program['features']) ?></textarea>
                <small class="form-text text-muted d-block mt-1">Use '|' to separate features.</small>
              </td>
              <td>
                <div class="action-buttons-group">
                  <button type="submit" class="btn btn-sm btn-success mb-1 w-100"><i class="fas fa-save"></i> Save</button>
                  </form>
                  <a href="delete_program.php?id=<?= $program['id'] ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to delete this program?');"><i class="fas fa-trash-alt"></i> Delete</a>
                </div>
              </td>
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