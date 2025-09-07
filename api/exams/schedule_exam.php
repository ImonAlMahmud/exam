<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php'; // config.php now sets PHP default timezone to Asia/Dhaka
require_once '../../includes/auth_check.php';
require_once '../../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the user is logged in as Admin or Mentor
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in as an Admin or Mentor to schedule exams.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true); // Use true for associative array

// 1. Validate Input (Check for missing or invalid fields)
if (
    !isset($data['exam_id']) || !is_numeric($data['exam_id']) ||
    !isset($data['scheduled_date']) || empty(trim($data['scheduled_date'])) ||
    !isset($data['start_time']) || empty(trim($data['start_time'])) ||
    !isset($data['end_time']) || empty(trim($data['end_time'])) ||
    !isset($data['duration_minutes']) || !is_numeric($data['duration_minutes']) || (int)$data['duration_minutes'] <= 0 ||
    !isset($data['mentor_id']) || !is_numeric($data['mentor_id'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'All schedule fields, including a valid duration, are required. Missing data or invalid input.']);
    exit();
}

$exam_id = (int)$data['exam_id'];
$scheduled_date_str = trim($data['scheduled_date']); // e.g., '2025-08-26'
$start_time_str = trim($data['start_time']);         // e.g., '21:40'
$end_time_str = trim($data['end_time']);             // e.g., '23:00'
$duration_minutes = (int)$data['duration_minutes'];
$mentor_id = (int)$data['mentor_id'];
$status = 'Upcoming'; // Default status for new schedules

// Combine date and time strings directly. PHP is now set to Asia/Dhaka.
$start_datetime_local = "{$scheduled_date_str} {$start_time_str}";
$end_datetime_local = "{$scheduled_date_str} {$end_time_str}";

// 2. Add more robust date/time validation (using PHP's default timezone: Asia/Dhaka)
$dt_start = new DateTime($start_datetime_local);
$dt_end = new DateTime($end_datetime_local);
$dt_now = new DateTime('now'); // Will be in Asia/Dhaka

// Check if end_time is after start_time
if ($dt_start >= $dt_end) { 
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'End time must be after start time.']);
    exit();
}
// Check if the scheduled start time is not in the past (only for Upcoming status)
if ($dt_start < $dt_now && $status === 'Upcoming') { 
     http_response_code(400);
     echo json_encode(['status' => 'error', 'message' => 'Scheduled start time cannot be in the past for new schedules.']);
     exit();
}


// --- Security Check: Mentor can only assign themselves ---
if ($user_role === 'Mentor' && $mentor_id !== $user_id) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Mentors can only assign schedules to themselves.']);
    exit();
}


// 3. Insert into exam_schedule table (using local DATETIME)
try {
    $sql = "INSERT INTO exam_schedule (exam_id, mentor_id, scheduled_date, start_time, end_time, duration_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id, $mentor_id, $scheduled_date_str, $start_datetime_local, $end_datetime_local, $duration_minutes, $status]);
    
    // --- NEW: Send Email Notifications to ALL Students ---
    $stmt_students = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'Student'");
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Fetch exam title for email
    $stmt_exam_title = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt_exam_title->execute([$exam_id]);
    $exam_title_for_email = htmlspecialchars($stmt_exam_title->fetchColumn() ?? 'New Exam');
    
    $app_name_for_email = htmlspecialchars(get_setting('app_name', 'HTEC Exam System'));
    $mentor_name = htmlspecialchars($_SESSION['user_name']); // Current logged-in user is mentor/admin

    if (!empty($students)) {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                      // Enable verbose debug output (0 for production)
            $mail->isSMTP();                           // Send using SMTP
            $mail->Host       = 'smtp.example.com';    // Set the SMTP server to send through (CHANGE THIS)
            $mail->SMTPAuth   = true;                  // Enable SMTP authentication
            $mail->Username   = 'your_email@example.com'; // SMTP username (CHANGE THIS)
            $mail->Password   = 'your_email_password'; // SMTP password (CHANGE THIS)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port       = 587;                   // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom('no-reply@' . str_replace(['http://', 'https://', '/'], '', BASE_URL), $app_name_for_email); // Dynamic From Address

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = "New Exam Scheduled: " . $app_name_for_email . " - " . $exam_title_for_email;
            $mail->Body    = "
                <p>Dear student,</p>
                <p>A new exam has been scheduled:</p>
                <p><strong>Exam Title:</strong> " . $exam_title_for_email . "</p>
                <p><strong>Date:</strong> " . date('M d, Y', strtotime($scheduled_date_str)) . "</p>
                <p><strong>Time:</strong> " . date('h:i A', strtotime($start_time_str)) . " - " . date('h:i A', strtotime($end_time_str)) . " (Dhaka Time)</p>
                <p><strong>Duration:</strong> {$duration_minutes} minutes</p>
                <p>Please log in to your dashboard at <a href='" . BASE_URL . "login.php'>HTEC Exam System</a> to join the exam at the scheduled time.</p>
                <p>Regards,<br>{$mentor_name} ({$app_name_for_email})</p>
            ";
            $mail->AltBody = "Dear student, a new exam has been scheduled: Exam Title: {$exam_title_for_email}, Date: " . date('M d, Y', strtotime($scheduled_date_str)) . ", Time: " . date('h:i A', strtotime($start_time_str)) . " - " . date('h:i A', strtotime($end_time_str)) . " (Dhaka Time), Duration: {$duration_minutes} minutes. Please log in to your dashboard to join the exam at the scheduled time.";

            foreach ($students as $student_email_info) {
                $mail->addAddress($student_email_info['email'], $student_email_info['name']);
                $mail->send();
                $mail->clearAddresses();
            }
            error_log("DEBUG: Email notifications sent for exam_id: {$exam_id}");

        } catch (Exception $e) {
            error_log("Email sending failed for exam_id {$exam_id}. Mailer Error: {$mail->ErrorInfo}");
            // Continue even if email fails, scheduling is more critical
        }
    }
    
    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Exam scheduled successfully! Notifications (if configured) sent.']);

} catch (PDOException $e) {
    if ($e->getCode() == '23000') { // SQLSTATE 23000 is for integrity constraint violation (e.g., UNIQUE key)
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'An exam is already scheduled with the same Exam, Mentor, and Start Time.']);
    } else {
        error_log("Database error scheduling exam: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]); 
    }
} catch (Exception $e) {
    error_log("General error scheduling exam: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred during scheduling: ' . $e->getMessage()]);
}