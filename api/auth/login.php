<?php
// config.php ফাইলটি একদম শুরুতে যুক্ত করতে হবে কারণ এটি সেশন শুরু করে।
require_once '../../includes/config.php'; 

// JSON কন্টেন্ট পাঠানোর জন্য হেডার সেট করা হচ্ছে। এটি অবশ্যই অন্য কোনো আউটপুটের আগে হতে হবে।
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// php://input থেকে gelen JSON ডেটা পড়া হচ্ছে
$data = json_decode(file_get_contents("php://input"));

// 1. ইনপুট যাচাই
if (
    !isset($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL) ||
    !isset($data->password) || empty($data->password)
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please provide email and password.']);
    exit();
}

$email = htmlspecialchars(strip_tags(trim($data->email)));
$password = $data->password;

// 2. ডেটাবেজে ব্যবহারকারীকে খোঁজা হচ্ছে
try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch();
        
        // 3. পাসওয়ার্ড যাচাই করা হচ্ছে
        if (password_verify($password, $user['password'])) {
            // পাসওয়ার্ড সঠিক হলে সেশন তৈরি করা হচ্ছে
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // 4. সফল বার্তা পাঠানো হচ্ছে
            http_response_code(200);
            echo json_encode([
                'status' => 'success', 
                'message' => 'Login successful! Redirecting...',
                'role' => $user['role']
            ]);
            
        } else {
            // পাসওয়ার্ড ভুল
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        }
    } else {
        // ব্যবহারকারীকে খুঁজে পাওয়া যায়নি
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }

} catch (PDOException $e) {
    // ডেটাবেস এরর
    http_response_code(500);
    // ডিবাগিং এর জন্য: $e->getMessage() ব্যবহার করা ভালো, কিন্তু প্রোডাকশনে সাধারণ মেসেজ দেখানো উচিত।
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>