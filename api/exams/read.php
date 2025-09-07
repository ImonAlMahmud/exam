<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in as Admin or Mentor
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to view exams.']);
    exit();
}

try {
    // Using LEFT JOIN is more robust. If the user who created the exam is deleted,
    // the exam will still be listed. COALESCE provides a default name in that case.
    $sql = "
        SELECT 
            e.id, 
            e.title, 
            e.created_at,
            e.is_omr_exam, -- Ensure this is fetched
            COALESCE(u.name, 'Deleted User') AS created_by,
            (SELECT COUNT(q.id) FROM questions q WHERE q.exam_id = e.id) AS question_count
        FROM exams e
        LEFT JOIN users u ON e.created_by = u.id
        ORDER BY e.created_at DESC
    ";
            
    $stmt = $pdo->query($sql);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if the fetch itself was successful, though query() would throw on failure
    if ($exams !== false) {
        echo json_encode(['status' => 'success', 'data' => $exams]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch exams.']);
    }

} catch (PDOException $e) {
    error_log("Database error in read.php API: " . $e->getMessage());
    http_response_code(500);
    // Provide a more specific error message for debugging
    echo json_encode(['status' => 'error', 'message' => 'Database Query Failed: ' . $e->getMessage()]);
}
?>