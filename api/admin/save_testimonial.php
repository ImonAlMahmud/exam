<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$id = $_POST['id'] ?? null;
$author_name = trim($_POST['author_name'] ?? '');
$author_title = trim($_POST['author_title'] ?? '');
$quote_text = trim($_POST['quote_text'] ?? '');
$display_order = (int)($_POST['display_order'] ?? 10);

if (empty($author_name) || empty($quote_text)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Author name and quote text are required.']);
    exit();
}

$image_filename = $_POST['current_author_image_val'] ?? 'default-avatar.png'; // Default to existing or fallback
$upload_dir = ROOT_PATH . 'uploads/website/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

// Handle image upload
if (isset($_FILES['author_image']) && $_FILES['author_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['author_image'];

    // Validate file type and size
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'File upload error for author image: ' . $file['error']]);
        exit();
    }
    if (!in_array($file['type'], $allowed_image_types)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type for author image. Only JPG, PNG, GIF are allowed.']);
        exit();
    }
    if ($file['size'] > $max_file_size) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Author image file size exceeds 5MB limit.']);
        exit();
    }

    // Sanitize filename and create unique name
    $original_filename = basename($file['name']);
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $safe_filename = uniqid('author_') . '-' . md5($original_filename . time()) . '.' . $extension;
    $target_file = $upload_dir . $safe_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Delete old image file if it's not the default and different from new one
        if ($image_filename && $image_filename !== 'default-avatar.png' && file_exists($upload_dir . $image_filename)) {
            unlink($upload_dir . $image_filename);
        }
        $image_filename = $safe_filename; // Update filename to the new one
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded author image.']);
        exit();
    }
}


try {
    if ($id) {
        $sql = "UPDATE testimonials SET author_name = ?, author_title = ?, quote_text = ?, author_image_url = ?, display_order = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$author_name, $author_title, $quote_text, $image_filename, $display_order, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Testimonial updated.']);
    } else {
        $sql = "INSERT INTO testimonials (author_name, author_title, quote_text, author_image_url, display_order) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$author_name, $author_title, $quote_text, $image_filename, $display_order]);
        echo json_encode(['status' => 'success', 'message' => 'Testimonial added.']);
    }

} catch (PDOException $e) {
    error_log("Database error saving testimonial: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}