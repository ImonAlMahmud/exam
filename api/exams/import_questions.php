<?php
// Include Composer's autoloader
require_once '../../vendor/autoload.php';
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// --- Basic Security & Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// Ensure Admin or Mentor is logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to import questions.']);
    exit();
}


if (!isset($_POST['exam_id']) || !isset($_FILES['questionFile'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Exam ID and file are required.']);
    exit();
}

$exam_id = (int)$_POST['exam_id'];
$file = $_FILES['questionFile'];

// --- Mentor Specific Security Check (Double Check) ---
if ($user_role === 'Mentor') {
    try {
        $stmt_check_ownership = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND created_by = ?");
        $stmt_check_ownership->execute([$exam_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to import questions for this exam.']);
            exit();
        }
    } catch (PDOException $e) {
        error_log("DB Error checking exam ownership in import_questions.php API: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error during permission check.']);
        exit();
    }
}

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $file['error']]); // Include actual error code
    exit();
}

// Ensure file is .xlsx
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($file_ext), ['xlsx'])) { // Only allow .xlsx
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Only .xlsx Excel files are supported.']);
    exit();
}

// --- Process the Excel File ---
$spreadsheet = IOFactory::load($file['tmp_name']);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
$highestColumn = 'F'; // A=question_text, B-E=options, F=correct_answer

$importedCount = 0;
$errorCount = 0;
$errors = [];

// Start transaction for atomic insertion
$pdo->beginTransaction();

try {
    $sql = "INSERT INTO questions (exam_id, question_text, option_1, option_2, option_3, option_4, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // Loop through each row of the worksheet (starting from row 2 to skip header)
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];
        
        // Basic validation for the row
        // Ensure question_text and correct_answer are not empty
        // And correct_answer is a valid number between 1 and 4
        if (empty(trim($rowData[0] ?? '')) || empty(trim($rowData[5] ?? '')) || !is_numeric($rowData[5]) || (int)$rowData[5] < 1 || (int)$rowData[5] > 4) {
            $errorCount++;
            $errors[] = "Row $row skipped: Missing or invalid question_text or correct_answer.";
            continue;
        }

        $stmt->execute([
            $exam_id,
            trim($rowData[0]), // question_text
            trim($rowData[1] ?? ''), // option_1 (use empty string if null)
            trim($rowData[2] ?? ''), // option_2
            trim($rowData[3] ?? ''), // option_3
            trim($rowData[4] ?? ''), // option_4
            (int)$rowData[5]  // correct_answer
        ]);
        $importedCount++;
    }

    // Commit the transaction
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Import complete! $importedCount questions imported successfully.",
        'details' => [
            'imported' => $importedCount,
            'skipped' => $errorCount,
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    // Rollback the transaction if something goes wrong
    $pdo->rollBack();
    http_response_code(500);
    error_log("Excel import error: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['status' => 'error', 'message' => 'An error occurred during import: ' . $e->getMessage()]);
}
?>