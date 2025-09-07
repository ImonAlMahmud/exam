<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// We can filter users by role
$role = isset($_GET['role']) ? $_GET['role'] : 'all';

$sql = "SELECT id, name, email, role FROM users";
$params = [];

if ($role !== 'all' && in_array($role, ['Admin', 'Mentor', 'Student'])) {
    $sql .= " WHERE role = ?";
    $params[] = $role;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'data' => $users]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>