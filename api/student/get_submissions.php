<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php'; // config.php now sets PHP default timezone to Asia/Dhaka
require_once '../../includes/auth_check.php';
require_login('Student');

$student_id = $_SESSION['user_id'];

try {
    // This complex query fetches submissions, joins with exam and schedule tables,
    // and calculates the rank of the student for each exam.
    // It uses DATETIME columns for start_time, so we extract date part for scheduled_date
    $sql = "
        SELECT 
            s.id AS submission_id,
            e.title AS exam_title,
            DATE(es.start_time) AS scheduled_date, -- Extract date part from DATETIME
            s.marks_obtained,
            s.total_marks,
            s.submission_time,
            (
                SELECT COUNT(*) + 1
                FROM submissions s2
                WHERE s2.schedule_id = s.schedule_id 
                  AND (s2.marks_obtained > s.marks_obtained 
                       OR (s2.marks_obtained = s.marks_obtained AND s2.submission_time < s.submission_time))
            ) AS student_rank
        FROM submissions s
        JOIN exams e ON s.exam_id = e.id
        JOIN exam_schedule es ON s.schedule_id = es.id
        WHERE s.student_id = ?
        ORDER BY es.start_time DESC, s.submission_time DESC -- Order by DATETIME
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
    
    echo json_encode(['status' => 'success', 'data' => $submissions]);

} catch (PDOException $e) {
    error_log("Database error in get_submissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in get_submissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}