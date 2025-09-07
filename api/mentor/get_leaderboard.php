<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Mentor'); // Only mentors can access this API

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

if ($schedule_id === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Schedule ID is required.']);
    exit();
}

try {
    // Ensure the mentor trying to view the leaderboard is assigned to this schedule
    $stmt_check_mentor = $pdo->prepare("SELECT id FROM exam_schedule WHERE id = ? AND mentor_id = ?");
    $stmt_check_mentor->execute([$schedule_id, $_SESSION['user_id']]);
    if ($stmt_check_mentor->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view this leaderboard.']);
        exit();
    }

    $sql = "
        SELECT 
            s.id AS submission_id,
            u.name AS student_name,
            u.roll_no,
            u.batch_name,
            s.marks_obtained,
            s.total_marks,
            s.submission_time,
            (
                SELECT COUNT(*) + 1
                FROM submissions s2
                WHERE s2.schedule_id = s.schedule_id AND (s2.marks_obtained > s.marks_obtained OR (s2.marks_obtained = s.marks_obtained AND s2.submission_time < s.submission_time))
            ) AS student_rank
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        WHERE s.schedule_id = ?
        ORDER BY student_rank ASC, s.submission_time ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id]);
    $leaderboard_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $leaderboard_data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>