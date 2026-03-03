<?php
include 'api/db_connect.php'; // Establishes $pdo

// Handle updates (moved logic to update_contact_info.php or similar)

// Fetch existing data
$stmt = $pdo->query("SELECT * FROM contact_info ORDER BY id ASC");
$contactItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Contact information updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Contact Information - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_contact_info.css"/>
</head>
<body>

<div class="container page-container"> 
  <h2 class="text-center">Edit Contact Information</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($contactItems)): ?>
    <p class="text-center text-muted">No contact items found to edit. Please ensure contact information exists in the database.</p>
  <?php else: ?>
    <form method="POST" action="update_contact_info.php" class="contact-info-form">
      <?php foreach ($contactItems as $item): ?>
        <div class="form-section mb-4">
          <input type="hidden" name="id[]" value="<?= (int) $item['id'] ?>">
          <div class="mb-3">
            <label for="label_<?= (int) $item['id'] ?>" class="form-label">Label (e.g., Phone, Email, Address)</label>
            <input type="text" id="label_<?= (int) $item['id'] ?>" name="label[]" class="form-control" value="<?= htmlspecialchars($item['label'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label for="value_<?= (int) $item['id'] ?>" class="form-label">Value</label>
            <textarea id="value_<?= (int) $item['id'] ?>" name="value[]" class="form-control" rows="2" required><?= htmlspecialchars($item['value'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label for="icon_class_<?= (int) $item['id'] ?>" class="form-label">Icon Class (e.g., bi bi-phone)</label>
            <input type="text" id="icon_class_<?= (int) $item['id'] ?>" name="icon_class[]" class="form-control" value="<?= htmlspecialchars($item['icon_class'] ?? '') ?>" placeholder="Optional: Bootstrap Icon class">
            <small class="form-text text-muted">Find icons at <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>. Example: `bi bi-geo-alt-fill`</small>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="text-center">
        <button type="submit" class="btn btn-primary btn-submit"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
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