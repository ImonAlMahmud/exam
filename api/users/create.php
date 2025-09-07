<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
// Add admin auth check here in a real application

$data = json_decode(file_get_contents("php://input"));

// 1. Validate Input
if (
    !isset($data->name) || empty(trim($data->name)) ||
    !isset($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL) ||
    !isset($data->password) || empty($data->password) ||
    !isset($data->role) || !in_array($data->role, ['Student', 'Mentor', 'Admin'])
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please fill all fields.']);
    exit();
}

$name = trim($data->name);
$email = trim($data->email);
$password = password_hash($data->password, PASSWORD_BCRYPT); // Always hash the password
$role = $data->role;

// 2. Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
    exit();
}

// 3. Insert new user
try {
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $password, $role]);
    
    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'User created successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>