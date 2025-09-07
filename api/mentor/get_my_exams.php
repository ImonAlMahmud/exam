<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php'; // config.php now sets PHP default timezone to Asia/Dhaka
require_once '../../includes/auth_check.php';
require_login('Mentor'); // Only mentors can access this API

$mentor_id = $_SESSION['user_id'];
error_log("DEBUG: get_my_exams.php - Fetching exams for mentor_id: " . $mentor_id); // DEBUG

try {
    // --- Automatic Exam Status Update (Using Asia/Dhaka for consistency) ---
    // This runs every time a mentor loads their dashboard.
    
    $now_local = new DateTime('now'); // Get current time in Asia/Dhaka (PHP default)
    $current_datetime_local = $now_local->format('Y-m-d H:i:s');


    // 1. Set exams to 'Running':
    // If current local datetime is >= start_time (DB) AND current local datetime < end_time (DB)
    $stmt_running = $pdo->prepare("
        UPDATE exam_schedule 
        SET status = 'Running' 
        WHERE start_time <= ? 
          AND end_time > ?  
          AND status = 'Upcoming'
    ");
    $stmt_running->execute([$current_datetime_local, $current_datetime_local]);
    
    // 2. Set exams to 'Ended':
    // If current local datetime is >= end_time (DB)
    $stmt_ended = $pdo->prepare("
        UPDATE exam_schedule 
        SET status = 'Ended' 
        WHERE end_time <= ? 
           AND status != 'Ended'
    ");
    $stmt_ended->execute([$current_datetime_local]);

    // 3. Auto Remove 'Ended' Exams after 6 hours (cleanup)
    // Deletes schedules that ended more than 6 hours ago based on local time.
    $six_hours_ago_local = (clone $now_local)->modify('-6 hours')->format('Y-m-d H:i:s');
    $stmt_delete_old_ended = $pdo->prepare("
        DELETE FROM exam_schedule 
        WHERE status = 'Ended' 
          AND end_time < ?
    ");
    $stmt_delete_old_ended->execute([$six_hours_ago_local]);


    // --- Fetch exams created by this mentor ---
    $sql_created_exams = "
        SELECT 
            e.id, 
            e.title, 
            e.is_omr_exam,
            e.created_at,
            (SELECT COUNT(q.id) FROM questions q WHERE q.exam_id = e.id) AS question_count
        FROM exams e
        WHERE e.created_by = ?
        ORDER BY e.created_at DESC";
            
    $stmt_created = $pdo->prepare($sql_created_exams);
    $stmt_created->execute([$mentor_id]);
    $created_exams = $stmt_created->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch exams scheduled by this mentor ---
    $sql_scheduled_exams = "
        SELECT 
            es.id AS schedule_id,
            e.title AS exam_title, 
            es.start_time AS scheduled_start_datetime_local, -- Get DATETIME directly (local time)
            es.end_time AS scheduled_end_datetime_local,   -- Get DATETIME directly (local time)
            es.duration_minutes,
            es.status -- Use the updated status from the DB (which is based on local time)
        FROM exam_schedule es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.mentor_id = ?
        ORDER BY es.start_time DESC"; // Order by DATETIME
            
    $stmt_scheduled = $pdo->prepare($sql_scheduled_exams);
    $stmt_scheduled->execute([$mentor_id]);
    $scheduled_exams = $stmt_scheduled->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => ['created_exams' => $created_exams, 'scheduled_exams' => $scheduled_exams]]);

} catch (Exception $e) {
    error_log("Database error in get_my_exams.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}