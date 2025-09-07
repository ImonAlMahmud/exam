<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
// Add admin auth check here

$data = json_decode(file_get_contents("php://input"));

// 1. Validate Input
if (
    !isset($data->id) || !is_numeric($data->id) ||
    !isset($data->password) || empty(trim($data->password))
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'User ID and a new password are required.']);
    exit();
}

$userId = $data->id;
$newPassword = trim($data->password);

// 2. Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// 3. Update the user's password in the database
try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'User not found or password is the same.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>