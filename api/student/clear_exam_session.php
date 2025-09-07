<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    // Clear the specific exam session variables
    if (isset($_SESSION['taking_exam_id'])) {
        unset($_SESSION['taking_exam_id']);
    }
    if (isset($_SESSION['exam_effective_end_time'])) {
        unset($_SESSION['exam_effective_end_time']);
    }

    echo json_encode(['status' => 'success', 'message' => 'Exam session cleared.']);
    
} catch (Exception $e) {
    error_log("Error clearing exam session: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear exam session: ' . $e->getMessage()]);
}