<?php
// api/auth/delete_user.php
// Use centralized session initializer and CSRF helper
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../db_connect.php'; // Adjust path as needed

header('Content-Type: application/json');

// Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// CSRF validation (accept header or JSON body)
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? null;
if (!validate_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// --- Authentication & Authorization ---
if (!isset($_SESSION['faculty_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// Ensure only admins can delete users (adjust role check as needed)
if (!isset($_SESSION['faculty_role']) || $_SESSION['faculty_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete users.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userIdToDelete = $input['id'] ?? null;

    if (empty($userIdToDelete)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required.']);
        exit;
    }

    // Prevent an admin from deleting their own account through this API endpoint
    if (isset($_SESSION['faculty_id']) && $userIdToDelete == $_SESSION['faculty_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }
    
    // It might also be wise to prevent deletion of a specific super-admin account
    // For example, if user ID 1 is the main super-admin:
    // if ($userIdToDelete == 1) {
    //    http_response_code(403);
    //    echo json_encode(['success' => false, 'message' => 'This primary admin account cannot be deleted.']);
    //    exit;
    // }


    try {
        $stmt = $pdo->prepare("DELETE FROM faculty WHERE id = :id");
        $stmt->bindParam(':id', $userIdToDelete, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'User account deleted successfully.']);
            } else {
                // This could happen if the user was already deleted by another action
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User account not found or already deleted.']);
            }
        } else {
            http_response_code(500);
            $errorInfo = $stmt->errorInfo();
            error_log("DB Execute Failed (Delete User): " . print_r($errorInfo, true));
            echo json_encode(['success' => false, 'message' => 'Database error during user deletion.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Delete User PDOException: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
}
?>