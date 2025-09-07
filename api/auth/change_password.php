<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
// Allows any logged-in user to change their password
if (!isset($_SESSION['user_id'])) { // Basic check
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to change password.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true); // Use true to get associative array

// 1. Validate Input
if (
    !isset($data['current_password']) || empty(trim($data['current_password'])) ||
    !isset($data['new_password']) || empty(trim($data['new_password'])) ||
    strlen(trim($data['new_password'])) < 6 // Basic new password length validation
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Current password and a new password (min 6 chars) are required.']);
    exit();
}

$current_password = trim($data['current_password']);
$new_password = trim($data['new_password']);

try {
    // 2. Fetch user's current hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit();
    }

    // 3. Verify current password
    if (!password_verify($current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
        exit();
    }

    // 4. Hash new password
    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);

    // 5. Update password in DB
    $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_update->execute([$hashed_new_password, $user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Password changed successfully.']);

} catch (PDOException $e) {
    error_log("Database error in change_password.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in change_password.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}