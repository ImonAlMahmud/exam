<?php
// Ensure this is the absolute first line, no spaces or newlines before it.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Student');

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
$student_id = $_SESSION['user_id'];

try {
    // --- Security Checks ---
    $stmt_schedule = $pdo->prepare("SELECT exam_id FROM exam_schedule WHERE id = ? AND status = 'Running'");
    $stmt_schedule->execute([$schedule_id]);
    $exam_id = $stmt_schedule->fetchColumn();
    if (!$exam_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Exam is not running or invalid schedule.']);
        exit();
    }
    $stmt_submitted = $pdo->prepare("SELECT id FROM submissions WHERE student_id = ? AND schedule_id = ?");
    $stmt_submitted->execute([$student_id, $schedule_id]);
    if ($stmt_submitted->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted this exam.']);
        exit();
    }
    if (!isset($_SESSION['taking_exam_id']) || $_SESSION['taking_exam_id'] !== $schedule_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid exam session. Please join from dashboard.']);
        exit();
    }
    if (!isset($_SESSION['exam_effective_end_time']) || $_SESSION['exam_effective_end_time'] < time()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Exam time has expired.']);
        exit();
    }

    // --- Fetch Questions and Shuffle Logic ---
    $stmt_questions = $pdo->prepare("SELECT id, question_text, option_1, option_2, option_3, option_4, correct_answer FROM questions WHERE exam_id = ?");
    $stmt_questions->execute([$exam_id]);
    $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

    $stmt_shuffle_config = $pdo->prepare("SELECT shuffle_questions, shuffle_options FROM exams WHERE id = ?");
    $stmt_shuffle_config->execute([$exam_id]);
    $shuffle_config = $stmt_shuffle_config->fetch(PDO::FETCH_ASSOC);

    if ($shuffle_config['shuffle_questions']) {
        shuffle($questions);
    }

    $response_questions = [];
    foreach ($questions as $original_question) {
        $question_to_send = [
            'id' => $original_question['id'],
            'question_text' => $original_question['question_text']
        ];

        // Prepare options for shuffling
        $options_with_original_indices = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($original_question["option_{$i}"])) {
                $options_with_original_indices[] = [
                    'text' => $original_question["option_{$i}"],
                    'original_index' => $i // Track original index (1, 2, 3, 4)
                ];
            }
        }

        if ($shuffle_config['shuffle_options']) {
            shuffle($options_with_original_indices);
        }

        // Reassign shuffled options and create a mapping for the client
        $shuffled_options_map = []; // Map: new_position (1-4) => original_index (1-4)
        foreach ($options_with_original_indices as $new_idx => $opt_data) {
            $question_to_send["option_".($new_idx + 1)] = $opt_data['text'];
            $shuffled_options_map[$new_idx + 1] = $opt_data['original_index'];
        }
        
        $question_to_send['shuffled_options_map'] = $shuffled_options_map; // Send map to client
        
        $response_questions[] = $question_to_send;
    }

    echo json_encode(['status' => 'success', 'data' => $response_questions]);

} catch (Exception $e) {
    error_log("Error in get_exam_questions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}