<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_once '../../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Security and Validation ---
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to upload an answer key.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

if (!isset($_POST['exam_id']) || !isset($_FILES['answerKeyFile'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Exam ID and file are required.']);
    exit();
}

$exam_id = (int)$_POST['exam_id'];
$file = $_FILES['answerKeyFile'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $file['error']]);
    exit();
}

// Ensure file is .xlsx
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($file_ext), ['xlsx'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Only .xlsx Excel files are supported.']);
    exit();
}

// --- Process the Excel File ---
$spreadsheet = IOFactory::load($file['tmp_name']);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
$highestColumn = 'B'; // A=question_number, B=correct_option

$answer_key_map = []; // To store question_number => correct_option

try {
    // Loop through each row of the worksheet (starting from row 2 to skip header)
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
        
        $question_num = trim($rowData[0] ?? '');
        $correct_option = trim($rowData[1] ?? '');

        if (!empty($question_num) && !empty($correct_option)) {
            $answer_key_map[$question_num] = (int)$correct_option;
        }
    }

    if (empty($answer_key_map)) {
        throw new Exception("Excel file is empty or has an invalid format.");
    }

    // Convert answer key to JSON
    $answer_key_json = json_encode($answer_key_map);

    // Save uploaded answer key file permanently
    $upload_dir = ROOT_PATH . 'uploads/answer_keys/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $safe_filename = uniqid('key_') . '-' . basename($file['name']);
    $target_file_path = $upload_dir . $safe_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_file_path)) {
        throw new Exception("Failed to save the uploaded answer key file.");
    }

    // Insert into omr_answer_keys table
    $sql = "INSERT INTO omr_answer_keys (exam_id, key_file_path, answer_key_json, uploaded_by) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id, $safe_filename, $answer_key_json, $user_id]);

    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Answer key uploaded successfully.']);

} catch (Exception $e) {
    // If an error occurred, remove any temporary files
    if (isset($target_file_path) && file_exists($target_file_path)) {
        unlink($target_file_path);
    }
    error_log("OMR Answer Key Upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred during upload: ' . $e->getMessage()]);
}
?>