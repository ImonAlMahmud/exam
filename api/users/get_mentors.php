<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
// This API should ideally be restricted to Admin/Mentor roles
// For simplicity, we'll allow any logged-in user to fetch mentors for now,
// but in a production environment, you might want to call require_login('Admin') or ('Mentor')

try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'Mentor' ORDER BY name ASC");
    $stmt->execute();
    $mentors = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'data' => $mentors]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>