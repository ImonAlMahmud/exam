<?php
// Ensure session is started before including config, as config also starts it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL); // Enable for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../../includes/config.php'; // Includes DB connection
require_once '../../includes/auth_check.php';

require_login('Admin'); // Ensure only Admins can access this API

// Set headers for JSON response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// Check if a file was uploaded and if there were any errors
if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'No SQL file uploaded or there was an upload error.']);
    exit();
}

$file = $_FILES['sqlFile'];

// Validate file extension
if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Only .sql files are allowed for database restoration.']);
    exit();
}

// Read the content of the uploaded SQL file
$sql_content = file_get_contents($file['tmp_name']);

if ($sql_content === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to read the uploaded SQL file content.']);
    exit();
}

try {
    // IMPORTANT: Clear output buffer before executing SQL if there's any chance of previous output.
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Turn off error reporting explicitly during SQL execution to prevent "headers already sent"
    // from database errors, which might conflict with JSON output.
    ini_set('display_errors', 0);
    error_reporting(0);

    // --- Start Restoration Process ---
    // Disable foreign key checks to allow dropping/creating tables without order issues
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Drop all existing tables to ensure a clean restore
    // This is a destructive operation, warn the user in frontend!
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`;");
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Execute the SQL content from the uploaded file
    // For very large SQL files, PDO->exec might hit memory limits.
    // For production, consider parsing the SQL file line by line or using the 'mysql' command-line client.
    $pdo->exec($sql_content);

    // After successful restore, clear the session as user roles/permissions might have changed.
    $_SESSION = array(); // Unset all session variables
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy(); // Destroy the session

    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => 'Database restored successfully! Please log in again.']);

} catch (PDOException $e) {
    // Catch PDO (database) specific errors
    error_log("Database restore error (PDO): " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database restore failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log("Database restore error (General): " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred during database restoration: ' . $e->getMessage()]);
}
?>