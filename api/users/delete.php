<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
// Add admin auth check here

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !is_numeric($data->id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit();
}

$userId = $data->id;

// Optional: Prevent admin from deleting their own account
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>