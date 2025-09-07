<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Student');

$data = json_decode(file_get_contents("php://input"), true); // Use true for associative array

// Validation
if (!isset($data['schedule_id']) || !isset($data['answers']) || !is_array($data['answers'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided. Schedule ID or answers missing/invalid.']);
    exit();
}

$schedule_id = (int)$data['schedule_id'];
$student_answers_raw = $data['answers']; // Raw answers from student
$student_id = $_SESSION['user_id'];

// --- Security Check 1: Prevent multiple submissions ---
try {
    $stmt_check_submission = $pdo->prepare("SELECT id FROM submissions WHERE student_id = ? AND schedule_id = ?");
    $stmt_check_submission->execute([$student_id, $schedule_id]);
    if ($stmt_check_submission->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted this exam.']);
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Error checking prior submission: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error during submission check: ' . $e->getMessage()]);
    exit();
}

// --- Security Check 2: Server-side time check to prevent late submissions ---
// This uses the effective end time stored in the session when the student started the exam.
if (!isset($_SESSION['exam_effective_end_time']) || $_SESSION['exam_effective_end_time'] < time()) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Exam submission time has expired. Your attempt may not be recorded fully.']);
    exit();
}
// Clear exam session flags immediately after successful time check to prevent re-submission if browser back button is used
unset($_SESSION['taking_exam_id']);
unset($_SESSION['exam_effective_end_time']);


$pdo->beginTransaction();

try {
    // 1. Get exam_id and correct answers from DB for all questions in this exam
    $stmt_exam_id = $pdo->prepare("SELECT exam_id FROM exam_schedule WHERE id = ?");
    $stmt_exam_id->execute([$schedule_id]);
    $exam_id = $stmt_exam_id->fetchColumn();

    if (!$exam_id) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Exam schedule not found.']);
        exit();
    }

    $stmt_correct = $pdo->prepare("SELECT id, correct_answer FROM questions WHERE exam_id = ?");
    $stmt_correct->execute([$exam_id]);
    $correct_answers_map = $stmt_correct->fetchAll(PDO::FETCH_KEY_PAIR); // Map: question_id => correct_answer_option

    // 2. Process student answers and calculate score
    $marks_obtained = 0;
    $total_questions_in_exam = count($correct_answers_map); 

    // Create a map of student's submitted answers for easier lookup
    $student_selected_options_map = [];
    foreach ($student_answers_raw as $answer) {
        $student_selected_options_map[$answer['question_id']] = $answer['selected_option'];
    }

    // Calculate marks by iterating through ALL questions of the exam
    foreach ($correct_answers_map as $question_id => $correct_option) {
        $selected_option = $student_selected_options_map[$question_id] ?? null; 

        if ($selected_option !== null && $correct_option == $selected_option) {
            $marks_obtained++;
        }
    }
    
    // 3. Insert into `submissions` table
    $stmt_sub = $pdo->prepare("INSERT INTO submissions (student_id, exam_id, schedule_id, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)");
    $stmt_sub->execute([$student_id, $exam_id, $schedule_id, $marks_obtained, $total_questions_in_exam]);
    $submission_id = $pdo->lastInsertId();

    // 4. Insert each answer into `answers` table
    $stmt_ans = $pdo->prepare("INSERT INTO answers (submission_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
    foreach ($correct_answers_map as $question_id => $correct_option) { // Iterate through all questions in exam
        $selected_option = $student_selected_options_map[$question_id] ?? null; 

        $is_correct = ($selected_option !== null && $correct_option == $selected_option) ? 1 : 0;
        
        // selected_option can be NULL now, as per schema update
        $stmt_ans->execute([$submission_id, $question_id, $selected_option, $is_correct]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Exam submitted successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error submitting exam: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit exam (DB): ' . $e->getMessage()]); // Show DB error for debugging
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error submitting exam: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred during submission: ' . $e->getMessage()]);
}