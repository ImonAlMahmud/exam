<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$upload_dir = ROOT_PATH . 'uploads/website/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
$errors = [];

$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
$allowed_favicon_types = ['image/x-icon', 'image/png']; // Favicon can be .ico or .png
$max_file_size = 5 * 1024 * 1024; // 5 MB

foreach (['logo_light', 'favicon'] as $field_name) {
    if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$field_name];

        // Validate file type and size
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error for {$field_name}: " . $file['error'];
            error_log("File upload failed for {$field_name}: " . $file['error']);
            continue;
        }
        if ($file['size'] > $max_file_size) {
            $errors[] = "File size for {$field_name} exceeds 5MB limit.";
            continue;
        }

        $is_valid_type = false;
        if ($field_name === 'logo_light' && in_array($file['type'], $allowed_image_types)) {
            $is_valid_type = true;
        } elseif ($field_name === 'favicon' && in_array($file['type'], $allowed_favicon_types)) {
            $is_valid_type = true;
        }

        if (!$is_valid_type) {
            $errors[] = "Invalid file type for {$field_name}.";
            continue;
        }

        // Sanitize filename and standardize name
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $field_name . '.' . $ext; // Standardize filenames (e.g., logo_light.png, favicon.ico)
        $target_file = $upload_dir . $new_filename;

        // Delete old file if it exists and has a different name
        $current_setting_value = get_setting($field_name, ''); // Get current value from DB
        if (!empty($current_setting_value) && $current_setting_value !== $new_filename && file_exists($upload_dir . $current_setting_value)) {
            unlink($upload_dir . $current_setting_value);
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $uploaded_files[$field_name] = $new_filename;
        } else {
            $errors[] = "Failed to move uploaded file for {$field_name}.";
        }
    }
}

if (empty($uploaded_files) && empty($errors)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No files uploaded or error occurred during processing.']);
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    foreach ($uploaded_files as $name => $filename) {
        $stmt->execute([$name, $filename, $filename]);
    }
    $pdo->commit();

    // After successful update, clear settings cache so next page load gets fresh data
    $GLOBALS['app_settings'] = []; 
    $GLOBALS['settings_loaded_attempted'] = false; // Reset flag to force reload

    echo json_encode(['status' => 'success', 'message' => 'Images updated.', 'files' => $uploaded_files, 'errors' => $errors]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error during image update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}