<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php'; // config.php sets PHP default timezone to Asia/Dhaka
require_once '../../includes/auth_check.php';
// require_once '../../vendor/autoload.php'; // Not strictly needed if using raw cURL for Aspose OMR

require_login('Student');

// --- RapidAPI Credentials (REPLACE WITH YOURS) ---
define('RAPIDAPI_KEY', '7566cebbfcmshb570bfaf09d296bp19f320jsn6dcacc7d95e0');
define('RAPIDAPI_HOST', 'aspose-omr-cloud1.p.rapidapi.com');
define('ASPOSE_OMR_BASE_URL', 'https://aspose-omr-cloud1.p.rapidapi.com/omr'); // Base URL for OMR operations

// --- Validation ---
if (!isset($_FILES['omr_sheet']) || $_FILES['omr_sheet']['error'] !== UPLOAD_ERR_OK || !isset($_POST['schedule_id']) || !isset($_POST['answer_key_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'OMR sheet image, schedule ID, and answer key ID are required.']);
    exit();
}

$omr_image = $_FILES['omr_sheet'];
$schedule_id = (int)$_POST['schedule_id'];
$answer_key_id = (int)$_POST['answer_key_id'];
$student_id = $_SESSION['user_id'];

// --- Security Check: Prevent multiple submissions ---
try {
    $stmt_check_submission = $pdo->prepare("SELECT id FROM omr_submissions WHERE student_id = ? AND schedule_id = ?");
    $stmt_check_submission->execute([$student_id, $schedule_id]);
    if ($stmt_check_submission->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted an OMR sheet for this exam.']);
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Error checking prior OMR submission: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error during submission check.']);
    exit();
}

// --- 1. Save uploaded OMR image temporarily ---
$temp_dir = ROOT_PATH . 'uploads/omr_temp/';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}
$image_ext = strtolower(pathinfo($omr_image['name'], PATHINFO_EXTENSION));
if (!in_array($image_ext, ['jpg', 'jpeg', 'png'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG and PNG images are allowed for OMR sheet.']);
    exit();
}
$temp_image_filename = uniqid('omr_') . '.' . $image_ext;
$temp_image_path = $temp_dir . $temp_image_filename;

if (!move_uploaded_file($omr_image['tmp_name'], $temp_image_path)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded OMR sheet.']);
    exit();
}

// --- 2. Fetch answer key JSON ---
try {
    $stmt = $pdo->prepare("SELECT answer_key_json, exam_id FROM omr_answer_keys WHERE id = ?");
    $stmt->execute([$answer_key_id]);
    $answer_key_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$answer_key_data) {
        throw new Exception("Answer key not found.");
    }
    $exam_id = $answer_key_data['exam_id'];
    $answer_key_json = $answer_key_data['answer_key_json']; // This is the JSON string of your answer key (e.g., {"1":2, "2":1})

} catch (Exception $e) {
    unlink($temp_image_path);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve answer key: ' . $e->getMessage()]);
    exit();
}

// --- 3. Upload OMR Image to Aspose Cloud Storage via RapidAPI (before processing) ---
// Aspose.OMR usually requires files to be in their cloud storage first.
// This is a simplified "copy file" operation, in reality, you might need "upload file"
// Check Aspose.OMR Cloud API documentation for exact storage upload endpoint.
// The provided cURL snippet is for CopyFile, but we need to *upload* the file.

$omr_cloud_file_name = "omr_sheets/" . basename($temp_image_path); // Path in Aspose Cloud Storage

$upload_url = "https://aspose-omr-cloud1.p.rapidapi.com/omr/storage/file/" . $omr_cloud_file_name; // Adjust based on Aspose docs
$file_handle = fopen($temp_image_path, 'r');

$curl_upload = curl_init();
curl_setopt_array($curl_upload, [
    CURLOPT_URL => $upload_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PUT", // PUT request to upload file
    CURLOPT_POSTFIELDS => stream_get_contents($file_handle), // Send file content
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: " . RAPIDAPI_HOST,
        "x-rapidapi-key: " . RAPIDAPI_KEY,
        "Content-Type: image/png" // Or image/jpeg based on your file
    ],
]);
$response_upload = curl_exec($curl_upload);
$err_upload = curl_error($curl_upload);
curl_close($curl_upload);
fclose($file_handle);


if ($err_upload) {
    error_log("RapidAPI OMR Upload cURL Error: " . $err_upload);
    unlink($temp_image_path);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload OMR sheet to cloud storage. cURL Error: ' . $err_upload]);
    exit();
}
$upload_result = json_decode($response_upload, true);
if ($upload_result === null || (isset($upload_result['status']) && $upload_result['status'] === 'error')) {
    error_log("RapidAPI OMR Upload Error: " . ($response_upload ?: "No response"));
    unlink($temp_image_path);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload OMR sheet to cloud storage. API Response: ' . ($response_upload ?: "No response")]);
    exit();
}


// --- 4. Recognize OMR Sheet using Aspose.OMR Cloud API via RapidAPI ---
$recognize_url = ASPOS_OMR_BASE_URL . "/RecognizeOmr"; // Check Aspose docs for specific endpoint
$answer_key_arr = json_decode($answer_key_json, true);

// Aspose OMR requires a template. If you don't have a custom one uploaded,
// you might try without, but it's less reliable.
// For this example, we assume your template is named 'my_omr_template.omr'
// and is also uploaded to Aspose cloud storage.
$template_name = "my_omr_template.omr"; // <--- IMPORTANT: Replace with your actual template name in Aspose.OMR Cloud
$output_format = "json"; // Expected output format from Aspose

$omr_recognize_data = [
    'fileName' => $omr_cloud_file_name, // The path/name of the file in Aspose Cloud Storage
    'templateName' => $template_name,
    'outputFormat' => $output_format,
];

$curl_recognize = curl_init();
curl_setopt_array($curl_recognize, [
    CURLOPT_URL => $recognize_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 60, // Longer timeout for processing
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($omr_recognize_data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-rapidapi-host: " . RAPIDAPI_HOST,
        "x-rapidapi-key: " . RAPIDAPI_KEY
    ],
]);

$response_recognize = curl_exec($curl_recognize);
$err_recognize = curl_error($curl_recognize);
curl_close($curl_recognize);


// --- 5. Process the result from Aspose.OMR API ---
if ($err_recognize) {
    error_log("RapidAPI OMR Recognize cURL Error: " . $err_recognize);
    unlink($temp_image_path);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'OMR recognition failed. cURL Error: ' . $err_recognize]);
    exit();
}

$api_result = json_decode($response_recognize, true);

if ($api_result && isset($api_result['OMRResponse']['Results'])) {
    $extracted_omr_answers = []; // {question_number: selected_option_A_B_C_D}
    foreach ($api_result['OMRResponse']['Results'] as $result_item) {
        $question_name = $result_item['QuestionName']; // e.g., "Q1"
        $selected_bubble = $result_item['SelectedAnswer']; // e.g., "A", "B", "C", "D"
        
        // Convert 'Q1' to '1' and 'A' to 1, 'B' to 2 etc.
        $q_num = str_replace('Q', '', $question_name);
        $option_to_int = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
        if (isset($option_to_int[$selected_bubble])) {
            $extracted_omr_answers[$q_num] = $option_to_int[$selected_bubble];
        }
    }

    // Compare extracted answers with our database answer key
    $marks_obtained = 0;
    $total_questions = count($answer_key_arr); // Total questions from your JSON answer key

    foreach ($answer_key_arr as $q_num_key => $correct_option_val) {
        if (isset($extracted_omr_answers[$q_num_key]) && $extracted_omr_answers[$q_num_key] == $correct_option_val) {
            $marks_obtained++;
        }
    }
    
    // --- 6. Move processed OMR sheet to a permanent location ---
    $permanent_dir = ROOT_PATH . 'uploads/omr_processed/';
    if (!is_dir($permanent_dir)) {
        mkdir($permanent_dir, 0777, true);
    }
    $permanent_image_filename = uniqid('processed_') . '-' . basename($temp_image_path);
    $permanent_image_path = $permanent_dir . $permanent_image_filename;
    rename($temp_image_path, $permanent_image_path); // Move the file


    // --- 7. Insert into omr_submissions table ---
    try {
        $sql = "INSERT INTO omr_submissions (student_id, schedule_id, exam_id, answer_key_id, scanned_sheet_path, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([$student_id, $schedule_id, $exam_id, $answer_key_id, $permanent_image_filename, $marks_obtained, $total_marks]);
        
        echo json_encode(['status' => 'success', 'score' => $marks_obtained, 'total' => $total_marks, 'message' => 'OMR sheet submitted and evaluated successfully!']);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Failed to save OMR results to database (PDO): " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to save OMR results to database: ' . $e->getMessage()]);
    }

} else {
    // Aspose API returned an error or unexpected format
    http_response_code(500);
    $error_message = (isset($api_result['message'])) ? $api_result['message'] : 'Aspose.OMR API returned an unexpected response.';
    error_log("Aspose.OMR API Error: " . $error_message . " Full response: " . $response_recognize);
    echo json_encode(['status' => 'error', 'message' => 'OMR sheet evaluation failed (API Error): ' . $error_message]);
}

// --- 8. Clean up temporary files ---
if (file_exists($temp_key_path)) {
    unlink($temp_key_path);
}
// Note: $temp_image_path is either moved or deleted based on success/failure earlier