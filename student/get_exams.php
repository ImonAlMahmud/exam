<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php'; // config.php now sets PHP default timezone to Asia/Dhaka
require_once '../../includes/auth_check.php';
require_login('Student'); // Ensure the user is a logged-in student

try {
    // --- Automatic Exam Status Update (Using Asia/Dhaka for consistency) ---
    // All date/time comparisons are now strictly in Asia/Dhaka, matching PHP's default timezone.
    
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


    // --- Fetch Upcoming and Running exams for the student ---
    // Show Upcoming and Running exams. Also show Ended exams for 6 hours after they ended.
    $sql = "
        SELECT 
            es.id, 
            e.title, 
            e.description, 
            e.is_omr_exam, -- New: Fetch the OMR exam flag
            es.start_time AS scheduled_start_datetime_local, -- Get DATETIME directly (local time)
            es.end_time AS scheduled_end_datetime_local,     -- Get DATETIME directly (local time)
            es.duration_minutes,
            es.status
        FROM exam_schedule es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status IN ('Upcoming', 'Running') OR (es.status = 'Ended' AND es.end_time > ?)
        ORDER BY es.start_time ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$six_hours_ago_local]); // Pass the time for ended exams visibility
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $exams]);

} catch (Exception $e) {
    error_log("Database error in get_exams.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}