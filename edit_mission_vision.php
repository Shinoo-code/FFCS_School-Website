<?php
include 'api/db_connect.php'; // Establishes $pdo

$mission = '';
$vision = '';

$stmt = $pdo->query("SELECT type, content FROM mission_vision WHERE type IN ('mission', 'vision')");
$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches into an associative array: ['mission' => 'content', 'vision' => 'content']

$mission = $data['mission'] ?? 'Default mission statement if not found.';
$vision = $data['vision'] ?? 'Default vision statement if not found.';

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Mission and Vision updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Mission & Vision - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_mission_vision.css"> 
</head>
<body>

<div class="container page-container"> 
  <h2 class="text-center">Edit Mission & Vision</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="update_mission_vision.php" method="POST" class="mission-vision-form">
    <div class="mb-4"> 
      <label for="mission" class="form-label">Mission Statement *</label>
      <textarea class="form-control" id="mission" name="mission" rows="5" required><?= htmlspecialchars($mission) ?></textarea>
    </div>
    <div class="mb-4"> 
      <label for="vision" class="form-label">Vision Statement *</label>
      <textarea class="form-control" id="vision" name="vision" rows="5" required><?= htmlspecialchars($vision) ?></textarea>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-save-changes"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </form>

  <div class="back-container mt-4">
    <a href="dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const alertBox = document.querySelector('.alert-success');
  if (alertBox) {
    setTimeout(() => {
        bootstrap.Alert.getOrCreateInstance(alertBox).close();
    }, 3000);
  }
</script>
</body>
</html>