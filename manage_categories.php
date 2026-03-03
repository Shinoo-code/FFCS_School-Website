<?php
include 'api/db_connect.php';
require_once 'api/csrf.php';

$msg = $_GET['msg'] ?? ''; // Get message from URL query

// Add new category (moved logic to add_category.php, this page just displays)
// Delete category (moved logic to delete_category.php)

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Activity Categories - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_categories.css"> 
</head>
<body>

<div class="page-container">
  <h2 class="text-center">Manage Activity Categories</h2>

  <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="add_category.php" method="post" class="add-category-form mb-4" autocomplete="off">
    <h3 class="form-section-title w-100 text-center">Add New Category</h3>
    <?php echo csrf_input_field(); ?>
    <div class="row g-3 w-100">
        <div class="col-md-5">
            <label for="new_name" class="form-label visually-hidden">Category Name</label>
            <input type="text" class="form-control" id="new_name" name="new_name" placeholder="Category Name" required />
        </div>
        <div class="col-md-5">
            <label for="new_slug" class="form-label visually-hidden">Slug</label>
            <input type="text" class="form-control" id="new_slug" name="new_slug" placeholder="Slug (lowercase, no spaces)" required pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only." />
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-custom"><i class="fas fa-plus-circle"></i> Add</button>
        </div>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing Categories</h3>
  <?php if (empty($categories)): ?>
    <p class="text-center text-muted">No categories found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= htmlspecialchars($cat['name']) ?></td>
              <td><?= htmlspecialchars($cat['slug']) ?></td>
              <td>
                  <?php if ($cat['slug'] !== 'all'): // Prevent deleting a default 'all' category ?>
                  <form action="delete_category.php" method="post" onsubmit="return confirm('Are you sure you want to delete this category? Activities using this category might need updating.');" style="display: inline;">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-delete"><i class="fas fa-trash-alt"></i> Delete</button>
                  </form>
                <?php else: ?>
                  <em>Default</em>
                <?php endif; ?>
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
  const alertMsg = document.querySelector('.alert-success');
  if (alertMsg) {
    setTimeout(() => {
      bootstrap.Alert.getOrCreateInstance(alertMsg).close();
    }, 3000);
  }
</script>
</body>
</html>