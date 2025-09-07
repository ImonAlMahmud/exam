<?php
// Enable error reporting at the very top for debugging.
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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to view question details.']);
    exit();
}

$question_id = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

if ($question_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Question ID is required.']);
    exit();
}

try {
    // --- Mentor Specific Security Check ---
    // A mentor can only view questions for exams they created.
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("
            SELECT q.id FROM questions q 
            JOIN exams e ON q.exam_id = e.id 
            WHERE q.id = ? AND e.created_by = ?
        ");
        $stmt_check_ownership->execute([$question_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view details for this question.']);
            exit();
        }
    }

    // Fetch question details
    $stmt = $pdo->prepare("SELECT id, exam_id, question_text, option_1, option_2, option_3, option_4, correct_answer FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($question) {
        echo json_encode(['status' => 'success', 'data' => $question]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Question not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error fetching question details: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while fetching question details.']);
} catch (Exception $e) {
    error_log("General error fetching question details: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>