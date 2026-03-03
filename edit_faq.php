<?php
include 'api/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_faqs.php?error=missing_id");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
$stmt->execute([$id]);
$faq = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faq) {
    header("Location: manage_faqs.php?error=not_found");
    exit;
}

// Update logic is in update_faq.php
$success_message = $_GET['updated_faq_success'] ?? ''; // Check for success message from update_faq.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit FAQ - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/edit_faq.css">
</head>
<body>

<div class="page-container">
  <h2 class="page-title">Edit FAQ</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form class="edit-faq-form" action="update_faq.php" method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($faq['id']) ?>">
    <div class="mb-3">
      <label for="question" class="form-label">Question *</label>
      <input type="text" class="form-control" id="question" name="question" value="<?= htmlspecialchars($faq['question']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="answer" class="form-label">Answer *</label>
      <textarea class="form-control" id="answer" name="answer" rows="5" required><?= htmlspecialchars($faq['answer']) ?></textarea>
    </div>
    <div class="text-center mt-4">
      <button type="submit" class="btn btn-submit-update"><i class="fas fa-save"></i> Update FAQ</button>
    </div>
  </form>

  <div class="back-container">
    <a href="manage_faqs.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
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