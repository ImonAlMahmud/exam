<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid plan ID.']);
    exit();
}

$id = (int)$data['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM pricing_plans WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Pricing plan deleted.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Pricing plan not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>