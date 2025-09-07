<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$data = json_decode(file_get_contents("php://input"), true); // true for associative array

if (empty($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No data provided.']);
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    
    foreach ($data as $name => $value) {
        $stmt->execute([$name, $value, $value]);
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'General settings updated.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>