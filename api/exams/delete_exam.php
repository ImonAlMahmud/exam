<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

// Check if user is logged in as Admin
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || $user_role !== 'Admin') {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin to delete exams.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->exam_id) || !is_numeric($data->exam_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid exam ID.']);
    exit();
}

$exam_id = (int)$data->exam_id;

try {
    // Delete the exam. Due to CASCADE, associated schedules, questions, submissions, answers will also be deleted.
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Exam and all associated data deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Exam not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error deleting exam: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while deleting exam: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error deleting exam: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}