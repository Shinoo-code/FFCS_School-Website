<?php
include 'api/db_connect.php';

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY date_posted DESC")->fetchAll();

$success_message = '';
$error_message = ''; // For potential errors

if (isset($_GET['success'])) {
    $success_message = "Announcement added successfully!";
} elseif (isset($_GET['updated'])) {
    $success_message = "Announcement updated successfully!";
} elseif (isset($_GET['deleted'])) {
    $success_message = "Announcement deleted successfully!";
} elseif (isset($_GET['error_message'])) {
    $error_message = htmlspecialchars($_GET['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Announcements - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/manage_announcements.css"> 
</head>
<body>

<div class="page-container">
  <h2 class="text-center">Manage Announcements</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
      <?= $error_message ?> 
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="add_announcements.php" method="post" class="announcement-form mb-4">
    <h3 class="form-section-title">Add Announcement</h3>
    <div class="mb-3">
      <label for="title" class="form-label">Title *</label>
      <input type="text" class="form-control" id="title" name="title" placeholder="Announcement Title" required>
    </div>
    <div class="mb-3">
      <label for="content" class="form-label">Content *</label>
      <textarea class="form-control" id="content" name="content" rows="4" placeholder="Announcement Content" required></textarea>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-custom"><i class="fas fa-plus-circle"></i> Add</button>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing Announcements</h3>
  <?php if (empty($announcements)): ?>
    <p class="text-center text-muted">No announcements found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Title</th>
            <th>Content</th>
            <th>Date Posted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($announcements as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['title']) ?></td>
              <td><?= nl2br(htmlspecialchars(substr($a['content'], 0, 100))) . (strlen($a['content']) > 100 ? '...' : '') ?></td> 
              <td><?= htmlspecialchars(date('M j, Y, g:i a', strtotime($a['date_posted']))) ?></td>
              <td>
                <div class="action-buttons-group">
                  <a href="edit_announcement.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-edit" title="Edit Announcement">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form action="delete_announcements.php" method="get" onsubmit="return confirm('Are you sure you want to delete this announcement?');" style="display: inline;">
                    <input type="hidden" name="delete" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-delete" title="Delete Announcement">
                      <i class="fas fa-trash-alt"></i>
                    </button>
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
  const BSalert = document.querySelector('.alert-success, .alert-danger'); // Target both success and error
  if (BSalert) {
    setTimeout(() => {
      if (bootstrap && bootstrap.Alert && bootstrap.Alert.getOrCreateInstance) {
        bootstrap.Alert.getOrCreateInstance(BSalert).close();
      } else {
        BSalert.style.display = 'none';
      }
    }, 4000); // Keep messages a bit longer
  }
</script>
</body>
</html>