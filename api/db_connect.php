<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
// api/db_connect.php (or similar path)

$host = 'localhost'; 
$dbname = 'ffcs_dtb'; 
$username = 'root'; 
$password = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // In a production environment, log this error and show a generic message.
    // For now, we'll output the error for debugging, but this is not secure for production.
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check server logs.'
        // 'error_detail' => $e->getMessage() //  Comment out for production
    ]);
    exit; // Stop script execution if DB connection fails
}
?>