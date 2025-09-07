<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid testimonial ID.']);
    exit();
}

$id = (int)$data['id'];

try {
    // Get image filename before deleting record
    $stmt_img = $pdo->prepare("SELECT author_image_url FROM testimonials WHERE id = ?");
    $stmt_img->execute([$id]);
    $image_to_delete = $stmt_img->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        // Delete image file if it's not the default
        if ($image_to_delete && $image_to_delete !== 'default-avatar.png' && file_exists(ROOT_PATH . 'uploads/website/' . $image_to_delete)) {
            unlink(ROOT_PATH . 'uploads/website/' . $image_to_delete);
        }
        echo json_encode(['status' => 'success', 'message' => 'Testimonial deleted.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Testimonial not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>