<?php
include 'api/db_connect.php';

$faqs = $pdo->query("SELECT * FROM faqs ORDER BY id DESC")->fetchAll();

$success_message = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage FAQs - FFCS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_faqs.css">
</head>
<body>

<div class="page-container">
  <h2 class="text-center">Manage FAQs</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form class="faq-form mb-4" action="add_faq.php" method="post">
    <h3 class="form-section-title">Add FAQ</h3>
    <div class="mb-3">
      <label for="question" class="form-label">Question *</label>
      <input type="text" class="form-control" id="question" name="question" placeholder="Enter question" required>
    </div>
    <div class="mb-3">
      <label for="answer" class="form-label">Answer *</label>
      <textarea class="form-control" id="answer" name="answer" rows="4" placeholder="Enter answer" required></textarea>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-custom"><i class="fas fa-plus-circle"></i> Add</button>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing FAQs</h3>
  <?php if (empty($faqs)): ?>
    <p class="text-center text-muted">No FAQs found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Question</th>
            <th>Answer</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($faqs as $faq): ?>
            <tr>
              <td><?= htmlspecialchars($faq['question']) ?></td>
              <td><?= nl2br(htmlspecialchars($faq['answer'])) ?></td>
              <td>
                <div class="action-buttons-group">
                  <a class="btn btn-sm btn-edit" href="edit_faq.php?id=<?= $faq['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                  <form action="delete_faq.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                    <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                    <button class="btn btn-sm btn-delete" type="submit"><i class="fas fa-trash-alt"></i> Delete</button>
                  </form>
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