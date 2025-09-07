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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to update questions.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// 1. Validate Input
if (
    !isset($data->question_id) || !is_numeric($data->question_id) ||
    !isset($data->exam_id) || !is_numeric($data->exam_id) ||
    !isset($data->question_text) || empty(trim($data->question_text)) ||
    !isset($data->option_1) || empty(trim($data->option_1)) ||
    !isset($data->option_2) || empty(trim($data->option_2)) ||
    !isset($data->option_3) || empty(trim($data->option_3)) ||
    !isset($data->option_4) || empty(trim($data->option_4)) ||
    !isset($data->correct_answer) || !is_numeric($data->correct_answer) || (int)$data->correct_answer < 1 || (int)$data->correct_answer > 4
) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. All fields are required and valid.']);
    exit();
}

$question_id = (int)$data->question_id;
$exam_id = (int)$data->exam_id;
$question_text = trim($data->question_text);
$option_1 = trim($data->option_1);
$option_2 = trim($data->option_2);
$option_3 = trim($data->option_3);
$option_4 = trim($data->option_4);
$correct_answer = (int)$data->correct_answer;

try {
    // --- Mentor Specific Security Check ---
    // A mentor can only update questions for exams they created.
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("
            SELECT q.id FROM questions q 
            JOIN exams e ON q.exam_id = e.id 
            WHERE q.id = ? AND e.created_by = ?
        ");
        $stmt_check_ownership->execute([$question_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to update this question.']);
            exit();
        }
    }

    // Update the question
    $sql = "UPDATE questions SET question_text = ?, option_1 = ?, option_2 = ?, option_3 = ?, option_4 = ?, correct_answer = ? WHERE id = ? AND exam_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$question_text, $option_1, $option_2, $option_3, $option_4, $correct_answer, $question_id, $exam_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Question updated successfully.']);
    } else {
        http_response_code(200); // OK, but no rows affected (e.g., no change)
        echo json_encode(['status' => 'success', 'message' => 'Question details are already up-to-date or not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error updating question: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while updating question.']);
} catch (Exception $e) {
    error_log("General error updating question: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>