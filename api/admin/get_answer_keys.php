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
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to view answer keys.']);
    exit();
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($exam_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Exam ID is required to fetch answer keys.']);
    exit();
}

try {
    // --- Security Check: A mentor can only view keys for exams they created ---
    $sql = "
        SELECT 
            ak.id,
            ak.created_at,
            u.name AS uploaded_by
        FROM omr_answer_keys ak
        JOIN users u ON ak.uploaded_by = u.id
        WHERE ak.exam_id = ?
    ";
    
    $params = [$exam_id];

    if ($user_role === 'Mentor') {
        $sql .= " AND ak.uploaded_by = ?";
        $params[] = $user_id;
    }

    $sql .= " ORDER BY ak.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $answer_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $answer_keys]);

} catch (PDOException $e) {
    error_log("Database error in get_answer_keys.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred while fetching answer keys.']);
}
?>