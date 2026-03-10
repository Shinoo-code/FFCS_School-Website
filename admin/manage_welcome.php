<?php
include '../api/db_connect.php'; // Ensures $pdo is available

$stmt = $pdo->query("SELECT * FROM welcome_section WHERE id = 1"); // Assuming ID 1
$welcome_data = $stmt->fetch(PDO::FETCH_ASSOC);

$welcome = [ /* Initialize with defaults */
    'welcome_title' => 'Welcome to FFCS!',
    'welcome_subtitle' => 'Your Child\'s Bright Future Starts Here',
    'welcome_paragraph' => 'Discover a nurturing environment where young minds blossom. We are dedicated to providing quality education that inspires a lifelong love for learning.',
    'welcome_image_path' => 'FFCS Pics/default_welcome.png',
    'reason1_title' => 'Dedicated Teachers', 'reason1_desc' => 'Passionate educators committed to your child\'s success.',
    'reason2_title' => 'Engaging Curriculum', 'reason2_desc' => 'Innovative programs that spark curiosity and foster growth.',
    'reason3_title' => 'Supportive Community', 'reason3_desc' => 'A warm and inclusive atmosphere for all students and families.'
];
if ($welcome_data) {
    $welcome = array_merge($welcome, $welcome_data); // Overwrite with DB data if exists
}

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'

if (isset($_GET['updated'])) {
    $feedback_message = "Welcome section updated successfully!";
    $feedback_type = 'success';
} elseif (isset($_GET['update_error'])) {
    $feedback_message = "Error updating: " . htmlspecialchars($_GET['update_error']);
    $feedback_type = 'danger'; // Use 'danger' for Bootstrap error alerts
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Welcome Section - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/manage_welcome.css"/> 
</head>
<body>

<div class="manage-welcome-container page-container"> 
  <h3 class="text-center">Update Welcome Section</h3>

  <?php if ($feedback_message): ?>
    <div class="alert alert-<?= $feedback_type ?> alert-dismissible fade show text-center" role="alert">
      <?= $feedback_message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form method="POST" action="update_welcome.php" enctype="multipart/form-data" class="welcome-content-form">
    <div class="mb-3">
        <label for="welcome_title" class="form-label">Welcome Title *</label>
        <input type="text" id="welcome_title" name="welcome_title" class="form-control" value="<?= htmlspecialchars($welcome['welcome_title']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="welcome_subtitle" class="form-label">Welcome Subtitle *</label>
        <input type="text" id="welcome_subtitle" name="welcome_subtitle" class="form-control" value="<?= htmlspecialchars($welcome['welcome_subtitle']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="welcome_paragraph" class="form-label">Welcome Paragraph *</label>
        <textarea id="welcome_paragraph" name="welcome_paragraph" rows="4" class="form-control" required><?= htmlspecialchars($welcome['welcome_paragraph']) ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Current Image:</label><br>
            <div class="image-preview-container text-center">
                <?php if (!empty($welcome['welcome_image_path']) && file_exists($welcome['welcome_image_path'])): ?>
                    <img src="<?= htmlspecialchars($welcome['welcome_image_path']) ?>" alt="Welcome Image" class="img-thumbnail current-image-preview">
                <?php else: ?>
                    <p class="text-muted">No image currently set or file missing.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="welcome_image" class="form-label">Change Image (optional):</label>
            <input type="file" id="welcome_image" name="welcome_image" class="form-control">
        </div>
    </div>
    
    <hr class="my-4">
    <h4 class="form-subsection-title">Reasons to Choose Us</h4>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="reason1_title" class="form-label">Reason 1 Title *</label>
            <input type="text" id="reason1_title" name="reason1_title" class="form-control" value="<?= htmlspecialchars($welcome['reason1_title']) ?>" required>
            <label for="reason1_desc" class="form-label mt-2">Reason 1 Description *</label>
            <textarea id="reason1_desc" name="reason1_desc" rows="3" class="form-control" required><?= htmlspecialchars($welcome['reason1_desc']) ?></textarea>
        </div>

        <div class="col-md-4 mb-3">
            <label for="reason2_title" class="form-label">Reason 2 Title *</label>
            <input type="text" id="reason2_title" name="reason2_title" class="form-control" value="<?= htmlspecialchars($welcome['reason2_title']) ?>" required>
            <label for="reason2_desc" class="form-label mt-2">Reason 2 Description *</label>
            <textarea id="reason2_desc" name="reason2_desc" rows="3" class="form-control" required><?= htmlspecialchars($welcome['reason2_desc']) ?></textarea>
        </div>

        <div class="col-md-4 mb-3">
            <label for="reason3_title" class="form-label">Reason 3 Title *</label>
            <input type="text" id="reason3_title" name="reason3_title" class="form-control" value="<?= htmlspecialchars($welcome['reason3_title']) ?>" required>
            <label for="reason3_desc" class="form-label mt-2">Reason 3 Description *</label>
            <textarea id="reason3_desc" name="reason3_desc" rows="3" class="form-control" required><?= htmlspecialchars($welcome['reason3_desc']) ?></textarea>
        </div>
    </div>

    <div class="text-center mt-3">
      <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Update Welcome Section</button>
    </div>
  </form>

  <div class="back-container mt-4">
    <a href="../dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const feedbackAlert = document.querySelector('.alert');
  if (feedbackAlert) {
    setTimeout(() => {
      bootstrap.Alert.getOrCreateInstance(feedbackAlert).close();
    }, 3000);
  }
</script>
</body>
</html>