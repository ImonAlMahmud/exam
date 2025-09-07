<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin'); // Only Admins can access this API

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Exam ID is required.']);
    exit();
}

try {
    // Fetch all schedules for a specific exam, including mentor's name
    $sql = "
        SELECT 
            es.id AS schedule_id,
            es.scheduled_date,
            es.start_time,
            es.end_time,
            es.duration_minutes,
            es.status,
            u.name AS mentor_name
        FROM exam_schedule es
        JOIN users u ON es.mentor_id = u.id
        WHERE es.exam_id = ?
        ORDER BY es.scheduled_date DESC, es.start_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $schedules]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>