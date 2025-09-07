<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_once '../../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to upload an answer key.']);
    exit();
}

if (!isset($_POST['exam_id']) || !isset($_FILES['answerKeyFile'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Exam ID and file are required.']);
    exit();
}

$exam_id = (int)$_POST['exam_id'];
$file = $_FILES['answerKeyFile'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $file['error']]);
    exit();
}
if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'xlsx') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Only .xlsx files are supported.']);
    exit();
}

$spreadsheet = IOFactory::load($file['tmp_name']);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

$answer_key_map = [];
for ($row = 2; $row <= $highestRow; $row++) {
    $question_num = trim($sheet->getCell('A' . $row)->getValue());
    $correct_option = trim($sheet->getCell('B' . $row)->getValue());
    if (!empty($question_num) && !empty($correct_option)) {
        $answer_key_map[$question_num] = (int)$correct_option;
    }
}

if (empty($answer_key_map)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Excel file is empty or has an invalid format.']);
    exit();
}

$answer_key_json = json_encode($answer_key_map);
$upload_dir = ROOT_PATH . 'uploads/answer_keys/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$safe_filename = uniqid('key_') . '-' . basename($file['name']);
$target_file_path = $upload_dir . $safe_filename;

if (!move_uploaded_file($file['tmp_name'], $target_file_path)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded answer key file.']);
    exit();
}

try {
    $sql = "INSERT INTO omr_answer_keys (exam_id, key_file_path, answer_key_json, uploaded_by) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id, $safe_filename, $answer_key_json, $user_id]);
    
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Answer key uploaded successfully.']);

} catch (PDOException $e) {
    unlink($target_file_path);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}