<?php
// add_category.php
// Handles the POST from manage_categories.php to add a new category
include __DIR__ . '/api/db_connect.php';
// CSRF protection
require_once __DIR__ . '/api/csrf.php';

// Validate CSRF token
$posted_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($posted_token)) {
    header('Location: manage_categories.php?msg=' . urlencode('Invalid or missing CSRF token.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back if accessed directly
    header('Location: manage_categories.php');
    exit;
}

$name = trim($_POST['new_name'] ?? '');
$slug = trim($_POST['new_slug'] ?? '');

// Basic validation
if ($name === '' || $slug === '') {
    $msg = urlencode('Name and slug are required.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;
}

// Slug must match the same pattern as the form: lowercase letters, numbers and hyphens
if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    $msg = urlencode('Invalid slug. Use lowercase letters, numbers and hyphens only.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;
}

try {
    // Prevent duplicates by slug or name
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug OR name = :name');
    $stmt->execute([':slug' => $slug, ':name' => $name]);
    $count = (int) $stmt->fetchColumn();

    if ($count > 0) {
        $msg = urlencode('A category with that name or slug already exists.');
        header("Location: manage_categories.php?msg={$msg}");
        exit;
    }

    $insert = $pdo->prepare('INSERT INTO categories (slug, name) VALUES (:slug, :name)');
    $insert->execute([':slug' => $slug, ':name' => $name]);

    $msg = urlencode('Category added successfully.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;

} catch (Exception $e) {
    // Log the error if you have logging; for now redirect back with a generic message
    error_log('add_category.php error: ' . $e->getMessage());
    $msg = urlencode('An error occurred while adding the category.');
    header("Location: manage_categories.php?msg={$msg}");
    exit;
}

?>
