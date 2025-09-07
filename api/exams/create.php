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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to create an exam.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true); // Use true for associative array

// 2. Validate input
if (!isset($data['title']) || empty(trim($data['title']))) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Exam title is required.']);
    exit();
}

$title = trim($data['title']);
$description = isset($data['description']) ? trim($data['description']) : null;
$shuffle_questions = isset($data['shuffle_questions']) && $data['shuffle_questions'] == 1 ? 1 : 0;
$shuffle_options = isset($data['shuffle_options']) && $data['shuffle_options'] == 1 ? 1 : 0;
$is_omr_exam = isset($data['is_omr_exam']) && $data['is_omr_exam'] == 1 ? 1 : 0; // New: Handle OMR exam flag
$created_by = $_SESSION['user_id'];

// 3. Insert new exam into the database
try {
    $sql = "INSERT INTO exams (title, description, created_by, shuffle_questions, shuffle_options, is_omr_exam) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $created_by, $shuffle_questions, $shuffle_options, $is_omr_exam]);
    
    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Exam created successfully.']);

} catch (PDOException $e) {
    error_log("Database error creating exam: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error creating exam: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}