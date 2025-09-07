<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $upload_dir = ROOT_PATH . 'uploads/profile_pictures/'; 
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    // Basic file validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $file['error']]);
        exit();
    }
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF are allowed.']);
        exit();
    }
    if ($file['size'] > $max_file_size) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit.']);
        exit();
    }

    // Sanitize filename to prevent directory traversal or other attacks
    $original_filename = basename($file['name']); // Get just the filename
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $safe_filename = uniqid('profile_') . '-' . md5($original_filename . time()) . '.' . $extension; // Unique & safe filename
    $target_file = $upload_dir . $safe_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Delete old profile picture if it's not 'default.png'
        $stmt_get_old_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt_get_old_pic->execute([$user_id]);
        $old_pic = $stmt_get_old_pic->fetchColumn();

        if ($old_pic && $old_pic !== 'default.png' && file_exists($upload_dir . $old_pic)) {
            unlink($upload_dir . $old_pic);
        }

        // Update database with new picture filename
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$safe_filename, $user_id]);
        echo json_encode(['status' => 'success', 'message' => 'Profile picture updated.', 'filepath' => $safe_filename]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload file. Error: ' . $file['error']]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}