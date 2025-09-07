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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to delete questions.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->question_id) || !is_numeric($data->question_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid question ID.']);
    exit();
}

$question_id = (int)$data->question_id;

try {
    // --- Security Check: Ensure mentor can only delete their own exam's questions ---
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("
            SELECT q.id FROM questions q 
            JOIN exams e ON q.exam_id = e.id 
            WHERE q.id = ? AND e.created_by = ?
        ");
        $stmt_check_ownership->execute([$question_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this question.']);
            exit();
        }
    }

    // Delete the question
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Question deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Question not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error deleting question: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while deleting question.']);
} catch (Exception $e) {
    error_log("General error deleting question: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>