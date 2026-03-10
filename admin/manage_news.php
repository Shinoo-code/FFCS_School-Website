<?php
include '../api/db_connect.php'; // Ensures $pdo is available

// Fetch existing news items
$stmt = $pdo->query("SELECT * FROM news ORDER BY date DESC");
$news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
if (isset($_GET['success'])) {
    $success_message = "News item added successfully!";
} elseif (isset($_GET['updated'])) {
    $success_message = "News item updated successfully!";
} elseif (isset($_GET['deleted'])) {
    $success_message = "News item deleted successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage News & Events - FFCS Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/manage_news.css"> 
</head>
<body>

<div class="container page-container"> 
  <h2 class="text-center">Manage News & Events</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
      <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form action="add_news.php" method="POST" enctype="multipart/form-data" class="mb-4 add-form">
    <h3 class="form-section-title">Add New Item</h3>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="news_title" class="form-label">Title *</label>
        <input type="text" class="form-control" id="news_title" name="title" placeholder="News Title" required>
      </div>
      <div class="col-md-6">
        <label for="news_date" class="form-label">Date *</label>
        <input type="date" class="form-control" id="news_date" name="date" required>
      </div>
      <div class="col-md-12">
        <label for="news_description" class="form-label">Description *</label>
        <textarea class="form-control" id="news_description" name="description" rows="3" placeholder="Short Description" required></textarea>
      </div>
      <div class="col-md-12">
        <label for="news_image" class="form-label">Image *</label>
        <input type="file" class="form-control" id="news_image" name="image" required>
      </div>
    </div>
    <div class="add-news-btn-container mt-3">  
      <button type="submit" class="btn add-news-btn"><i class="fas fa-plus-circle"></i> Add News Item</button>
    </div>
  </form>

  <hr class="my-4">

  <h3 class="form-section-title">Existing News & Events</h3>
  <?php if (empty($news_items)): ?>
    <p class="text-center text-muted">No news items found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Date</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($news_items as $item): ?>
            <tr>
              <td>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="table-image-preview">
              </td>
              <td><?= htmlspecialchars($item['title']) ?></td>
              <td><?= htmlspecialchars(date('M j, Y', strtotime($item['date']))) ?></td>
              <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
              <td>
                <div class="action-buttons-group">
                  <a href="edit_news.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-edit" title="Edit News Item">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form action="delete_news.php" method="get" onsubmit="return confirm('Are you sure you want to delete this news item?');" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-delete" title="Delete News Item">
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
    <a href="../dashboard.php#dashboard-section" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
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