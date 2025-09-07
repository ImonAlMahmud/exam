<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $testimonials]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>