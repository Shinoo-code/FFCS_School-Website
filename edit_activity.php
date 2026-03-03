<?php
// edit_activity.php - UPDATED for DATE inputs
include 'api/db_connect.php';

if (!isset($_GET['id'])) {
  header("Location: manage_activities.php?error_msg=" . urlencode("Missing activity ID."));
  exit;
}

$id = $_GET['id'];
$activity = null;
$categories = [];
$error_message = $_GET['error_msg'] ?? '';
$success_message = $_GET['success_msg'] ?? ''; // Message from update_activity.php

try {
    // Fetch the specific activity including start_date and end_date
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
      header("Location: manage_activities.php?error_msg=" . urlencode("Activity not found."));
      exit;
    }

    // Fetch categories for dropdown
    $categories_stmt = $pdo->query("SELECT slug, name FROM categories ORDER BY name ASC");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching data for edit_activity.php: " . $e->getMessage());
    $error_message = "Database error loading activity data.";
    $activity = null; // Ensure activity is null on error
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Activity - <?= htmlspecialchars($activity['title'] ?? '...') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_activity.css">
</head>
<body>

<div class="page-container">
  <h2 class="page-title">Edit Activity</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($error_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($activity): // Only show form if activity data was loaded ?>
  <form action="update_activity.php" method="post" enctype="multipart/form-data" class="edit-form">
    <input type="hidden" name="id" value="<?= htmlspecialchars($activity['id']) ?>">

    <div class="mb-3">
      <label for="title" class="form-label">Title *</label>
      <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($activity['title'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Description *</label>
      <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($activity['description'] ?? '') ?></textarea>
    </div>

    <div class="row g-3">
        <div class="col-md-6 mb-3">
            <label for="start_date" class="form-label">Start Date *</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($activity['start_date'] ?? '') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="end_date" class="form-label">End Date (Optional)</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($activity['end_date'] ?? '') ?>">
             <small class="form-text text-muted">Leave blank for single-day events.</small>
       </div>
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">Category *</label>
        <select class="form-select" id="category" name="category" required>
            <option value="">Select Category...</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= (($activity['category'] ?? '') === $cat['slug']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>


    <div class="mb-3 image-preview-container">
        <label class="form-label d-block">Current Image:</label>
        <?php
        $imageExists = false;
        $imagePath = '';
        if (!empty($activity['image_path'])) {
            $imagePath = "uploads/activities/" . $activity['image_path'];
            if (file_exists($imagePath)) {
                $imageExists = true;
            }
        }
        ?>
        <?php if ($imageExists): ?>
            <img src="<?= htmlspecialchars($imagePath) ?>?t=<?= time() // Add timestamp to prevent caching ?>" alt="Current Activity Image" class="image-preview img-thumbnail">
        <?php else: ?>
            <p class="text-muted">No image currently uploaded or file is missing.</p>
            <?php if (!empty($activity['image_path'])) { echo "<p class='text-danger small'>Missing: " . htmlspecialchars($imagePath) . "</p>"; } ?>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="image" class="form-label">Change Image (optional):</label>
        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/gif">
        <small class="form-text text-muted">Only select a file if you want to replace the current image.</small>
    </div>

    <div class="text-center mt-4">
      <button type="submit" class="btn btn-submit-update"><i class="fas fa-save"></i> Update Activity</button>
    </div>
  </form>
  <?php elseif (empty($error_message)): // Show only if no other error was set ?>
      <p class="text-center text-danger">Could not load activity details for editing.</p>
  <?php endif; ?>

  <div class="back-container">
    <a href="manage_activities.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Script to auto-hide alerts
  const alertElements = document.querySelectorAll('.alert-dismissible');
  alertElements.forEach(alertEl => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
      if (bsAlert) bsAlert.close();
    }, 5000); // Hide after 5 seconds
  });

  // Basic date validation: end_date cannot be before start_date
  const startDateInput = document.getElementById('start_date');
  const endDateInput = document.getElementById('end_date');

  function validateDates() {
    if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
      if (endDateInput.value < startDateInput.value) {
        alert('End Date cannot be before Start Date.');
        endDateInput.value = startDateInput.value; // Or clear it: endDateInput.value = '';
      }
    }
  }

  if (startDateInput) startDateInput.addEventListener('change', validateDates);
  if (endDateInput) endDateInput.addEventListener('change', validateDates);

</script>
</body>
</html>