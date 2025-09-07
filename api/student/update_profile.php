<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
// This API should be accessible to any logged-in user
if (!isset($_SESSION['user_id'])) { // Basic check
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true); // Use true to get associative array

// Validation
if (!isset($data['name']) || empty(trim($data['name']))) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Name is required.']);
    exit();
}

$name = trim($data['name']);
// roll_no and batch_name are specific to students, but mentors might not have them.
// We handle them gracefully by setting to null if not provided in data.
$roll_no = isset($data['roll_no']) ? (trim($data['roll_no']) === '' ? null : trim($data['roll_no'])) : null;
$batch_name = isset($data['batch_name']) ? (trim($data['batch_name']) === '' ? null : trim($data['batch_name'])) : null;

try {
    // Update session name immediately
    $_SESSION['user_name'] = $name;

    // Check the user's role to determine which fields to update
    $stmt_check_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt_check_role->execute([$user_id]);
    $user_role = $stmt_check_role->fetchColumn();

    if ($user_role === 'Student') {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, roll_no = ?, batch_name = ? WHERE id = ?");
        $stmt->execute([$name, $roll_no, $batch_name, $user_id]);
    } else { // For Mentor or Admin, only update name
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $user_id]);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
} catch (Exception $e) {
    error_log("Database error in update_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}