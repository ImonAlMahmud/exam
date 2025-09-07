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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to delete answer keys.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->answer_key_id) || !is_numeric($data->answer_key_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid Answer Key ID.']);
    exit();
}

$answer_key_id = (int)$data->answer_key_id;

try {
    // --- Security Check: A mentor can only delete keys for exams they created ---
    if ($user_role === 'Mentor') {
        $stmt_check_ownership = $pdo->prepare("SELECT id FROM omr_answer_keys WHERE id = ? AND uploaded_by = ?");
        $stmt_check_ownership->execute([$answer_key_id, $user_id]);
        if ($stmt_check_ownership->rowCount() === 0) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this answer key.']);
            exit();
        }
    }
    
    // Get the filename to delete from the filesystem
    $stmt_get_file = $pdo->prepare("SELECT key_file_path FROM omr_answer_keys WHERE id = ?");
    $stmt_get_file->execute([$answer_key_id]);
    $file_to_delete = $stmt_get_file->fetchColumn();

    // Delete the record from the database
    $stmt_delete = $pdo->prepare("DELETE FROM omr_answer_keys WHERE id = ?");
    $stmt_delete->execute([$answer_key_id]);

    if ($stmt_delete->rowCount() > 0) {
        // Delete the associated Excel file from the server
        $file_path = ROOT_PATH . 'uploads/answer_keys/' . $file_to_delete;
        if ($file_to_delete && file_exists($file_path)) {
            unlink($file_path);
        }
        
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Answer key deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Answer key not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error deleting answer key: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while deleting the answer key.']);
}
?>