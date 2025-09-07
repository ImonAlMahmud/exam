<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data) || !isset($data['plan_name']) || empty($data['plan_name']) || !isset($data['price'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Plan name and price are required.']);
    exit();
}

$id = $data['id'] ?? null;
$plan_name = trim($data['plan_name']);
$description = trim($data['description'] ?? '');
$price = trim($data['price']);
$frequency = trim($data['frequency'] ?? '/mo');
$features = trim($data['features'] ?? '');
$is_popular = isset($data['is_popular']) ? 1 : 0;
$display_order = (int)($data['display_order'] ?? 10);

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE pricing_plans SET plan_name = ?, description = ?, price = ?, frequency = ?, features = ?, is_popular = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$plan_name, $description, $price, $frequency, $features, $is_popular, $display_order, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Pricing plan updated.']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pricing_plans (plan_name, description, price, frequency, features, is_popular, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$plan_name, $description, $price, $frequency, $features, $is_popular, $display_order]);
        echo json_encode(['status' => 'success', 'message' => 'Pricing plan added.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>