<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json'); // Ensure JSON header is sent first

require_once '../../includes/config.php'; // Correct path to config.php
require_once '../../includes/auth_check.php';

// Check if user is logged in as Admin or Mentor
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to view questions.']);
    exit();
}

// Ensure exam_id is provided
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Exam ID is required to fetch questions.']);
    exit();
}

try {
    // --- Mentor Specific Security Check ---
    // A mentor can only view questions for exams they created.
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND created_by = ?");
        $stmt_check_ownership->execute([$exam_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view questions for this exam.']);
            exit();
        }
    }

    // Fetch all questions for the given exam_id
    // IMPORTANT: Do NOT include correct_answer here as this data goes to frontend (even admin UI needs to be secure)
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $questions]);

} catch (PDOException $e) {
    // Log the actual database error for debugging purposes
    error_log("Database error in get_questions.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while fetching questions.']);
} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log("General error in get_questions.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>