<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin'); // Only Admin can view user details for management

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit();
}

try {
    // Fetch user details including roll_no and batch_name
    $stmt = $pdo->prepare("SELECT id, name, email, role, roll_no, batch_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error fetching user details: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while fetching user details.']);
} catch (Exception $e) {
    error_log("General error fetching user details: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}