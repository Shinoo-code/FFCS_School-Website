<?php
// delete_category.php
// Handles deletion requests from manage_categories.php via GET ?delete_id=...
include __DIR__ . '/api/db_connect.php';

// Only allow POST (forms should submit via POST with CSRF tokens)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_categories.php?msg=' . urlencode('Invalid request method.'));
    exit;
}

require_once __DIR__ . '/api/csrf.php';

$deleteId = isset($_POST['delete_id']) ? (int) $_POST['delete_id'] : 0;

// Validate CSRF token
$posted_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($posted_token)) {
    header('Location: manage_categories.php?msg=' . urlencode('Invalid or missing CSRF token.'));
    exit;
}

if ($deleteId <= 0) {
    $msg = urlencode('Invalid category id.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;
}

try {
    // Fetch the category to check its slug (don't allow deleting 'all')
    $stmt = $pdo->prepare('SELECT id, slug, name FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $deleteId]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        $msg = urlencode('Category not found.');
        header("Location: manage_categories.php?msg={$msg}");
        exit;
    }

    if ($cat['slug'] === 'all') {
        $msg = urlencode('The default category cannot be deleted.');
        header("Location: manage_categories.php?msg={$msg}");
        exit;
    }

    // Safe delete
    $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $del->execute([':id' => $deleteId]);

    $msg = urlencode('Category deleted successfully.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;

} catch (Exception $e) {
    error_log('delete_category.php error: ' . $e->getMessage());
    $msg = urlencode('An error occurred while deleting the category.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;
}

?>
