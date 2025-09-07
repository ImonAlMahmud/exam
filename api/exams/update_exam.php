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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to update exams.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true); // Use true for associative array

// 1. Validate Input
if (
    !isset($data['examId']) || !is_numeric($data['examId']) ||
    !isset($data['title']) || empty(trim($data['title']))
) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Exam ID and title are required.']);
    exit();
}

$exam_id = (int)$data['examId'];
$title = trim($data['title']);
$description = trim($data['description'] ?? '');
$shuffle_questions = isset($data['shuffle_questions']) ? 1 : 0; // Simplified checkbox handling
$shuffle_options = isset($data['shuffle_options']) ? 1 : 0;
$is_omr_exam = isset($data['is_omr_exam']) ? 1 : 0; // New: Handle OMR exam flag

try {
    // --- Mentor Specific Security Check ---
    // A mentor can only update exams they created.
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND created_by = ?");
        $stmt_check_ownership->execute([$exam_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to update this exam.']);
            exit();
        }
    }

    // Update the exam
    $sql = "UPDATE exams SET title = ?, description = ?, shuffle_questions = ?, shuffle_options = ?, is_omr_exam = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $shuffle_questions, $shuffle_options, $is_omr_exam, $exam_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Exam updated successfully.']);
    } else {
        http_response_code(200); // OK, but no rows affected (e.g., no change)
        echo json_encode(['status' => 'success', 'message' => 'Exam details are already up-to-date or not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error updating exam: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while updating exam.']);
} catch (Exception $e) {
    error_log("General error updating exam: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}