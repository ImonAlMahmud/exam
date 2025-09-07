<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin'); // Only Admin can update user details

$data = json_decode(file_get_contents("php://input"));

// 1. Validate Input
if (
    !isset($data->userId) || !is_numeric($data->userId) ||
    !isset($data->name) || empty(trim($data->name)) ||
    !isset($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL) ||
    !isset($data->role) || !in_array($data->role, ['Admin', 'Mentor', 'Student'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. User ID, Name, Email, and Role are required.']);
    exit();
}

$user_id = (int)$data->userId;
$name = trim($data->name);
$email = trim($data->email);
$role = trim($data->role);
$roll_no = trim($data->roll_no ?? '');
$batch_name = trim($data->batch_name ?? '');
$password = trim($data->password ?? ''); // Password is optional for update

try {
    // Check if email already exists for another user
    $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check_email->execute([$email, $user_id]);
    if ($stmt_check_email->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'This email is already used by another user.']);
        exit();
    }

    // Build update query dynamically
    $sql_parts = ["name = ?", "email = ?", "role = ?"];
    $params = [$name, $email, $role];

    if (!empty($password)) { // If password is provided, hash and update it
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql_parts[] = "password = ?";
        $params[] = $hashed_password;
    }

    // Update roll_no and batch_name only for Students, set to null for others
    if ($role === 'Student') {
        $sql_parts[] = "roll_no = ?";
        $params[] = $roll_no;
        $sql_parts[] = "batch_name = ?";
        $params[] = $batch_name;
    } else {
        // Explicitly set to NULL if changing role from Student or if it's not a Student
        $sql_parts[] = "roll_no = NULL";
        $sql_parts[] = "batch_name = NULL";
    }

    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully.']);
    } else {
        http_response_code(200); // OK, but no rows affected (e.g., no change)
        echo json_encode(['status' => 'success', 'message' => 'User details are already up-to-date or user not found.']);
    }

} catch (PDOException $e) {
    error_log("Database error updating user: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while updating user: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error updating user: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}