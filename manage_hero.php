<?php
// manage_hero.php

include 'api/db_connect.php'; // Ensures $pdo is available

// Fetch existing hero data (assuming ID 1 for the hero section)
$stmt_hero = $pdo->query("SELECT * FROM hero_section WHERE id = 1 LIMIT 1");
$hero = $stmt_hero->fetch(PDO::FETCH_ASSOC);

// Provide default values if no record exists yet, or if fetching failed
if (!$hero) {
    $hero = [
        'heading' => 'Default Heading <strong>with Bold</strong>',
        'subheading' => 'Default subheading for the hero section.',
        'years_excellence' => '0',
        'students_enrolled' => '0',
        'image_path' => 'FFCS Pics/default_hero.jpg' // Path to a default placeholder
    ];
}

// --- Feedback Message Handling ---
$feedback_message_text = '';
$is_success_message = false; // Flag to determine if it's a success message
$is_error_message = false;   // Flag for error messages

// Check for success message from update_hero.php
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    // THIS IS WHERE YOU CHANGE THE MESSAGE TEXT
    $feedback_message_text = "Manage Section updated successfully!"; // Your desired message
    $is_success_message = true;
}
// Check for error message from update_hero.php
elseif (isset($_GET['update_error'])) {
    $feedback_message_text = "Error: " . htmlspecialchars(urldecode($_GET['update_error']));
    $is_error_message = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Hero Section - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_hero.css">
</head>
<body>

<div class="page-container">
  <h3 class="text-center">Manage Hero Section</h3>

  <?php if (!empty($feedback_message_text)): ?>
    <?php if ($is_success_message): ?>
      <div class="custom-success-alert alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($feedback_message_text) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif ($is_error_message): ?>
      <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
        <?= $feedback_message_text ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <form method="POST" action="update_hero.php" enctype="multipart/form-data" class="hero-form">
    <div class="mb-3">
      <label for="heading" class="form-label">Heading (HTML allowed):</label>
      <textarea class="form-control" id="heading" name="heading" rows="3"><?= htmlspecialchars($hero['heading']) ?></textarea>
    </div>

    <div class="mb-3">
      <label for="subheading" class="form-label">Subheading:</label>
      <textarea class="form-control" id="subheading" name="subheading" rows="2"><?= htmlspecialchars($hero['subheading']) ?></textarea>
    </div>

    <div class="row g-3">
        <div class="col-md-6 mb-3">
          <label for="years_excellence" class="form-label">Years of Excellence:</label>
          <input type="text" class="form-control" id="years_excellence" name="years_excellence" value="<?= htmlspecialchars($hero['years_excellence']) ?>">
        </div>

        <div class="col-md-6 mb-3">
          <label for="students_enrolled" class="form-label">Students Enrolled:</label>
          <input type="text" class="form-control" id="students_enrolled" name="students_enrolled" value="<?= htmlspecialchars($hero['students_enrolled']) ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Current Image:</label><br>
        <div class="image-preview-container">
            <?php if (!empty($hero['image_path']) && file_exists($hero['image_path'])): ?>
                <img src="<?= htmlspecialchars($hero['image_path']) ?>?t=<?= time() ?>" alt="Current Hero Image" class="image-preview">
            <?php else: ?>
                <p class="text-muted">No image currently set or image file missing.</p>
                <?php if (!empty($hero['image_path'])) { echo "<p class='text-danger small'>Missing: " . htmlspecialchars($hero['image_path']) . "</p>"; } ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3">
      <label for="hero_image" class="form-label">Change Image (optional):</label>
      <input type="file" class="form-control" id="hero_image" name="hero_image">
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-custom"><i class="fas fa-save"></i> Update Hero Section</button>
    </div>
  </form>

  <div class="back-container mt-4">
    <a href="dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Script for auto-hiding custom success alert
  const customSuccessAlert = document.querySelector('.custom-success-alert');
  if (customSuccessAlert) {
    setTimeout(() => {
      if (typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(customSuccessAlert);
        if (bsAlert) bsAlert.close();
      } else {
        customSuccessAlert.style.display = 'none'; // Fallback
      }
    }, 5000); // Hide after 5 seconds
  }

  // Script for auto-hiding standard Bootstrap danger alert (if used for errors)
  const dangerAlert = document.querySelector('.alert-danger');
  if (dangerAlert) {
    setTimeout(() => {
      if (typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(dangerAlert);
        if (bsAlert) bsAlert.close();
      } else {
        dangerAlert.style.display = 'none'; // Fallback
      }
    }, 5000); // Hide after 5 seconds
  }
</script>
</body>
</html>