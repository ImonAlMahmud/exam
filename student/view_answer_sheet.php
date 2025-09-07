<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Dynamically determine required roles for viewing answer sheets
$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// If no submission ID, redirect to appropriate dashboard
if ($submission_id === 0) {
    if ($user_role === 'Student') {
        header("Location: " . BASE_URL . 'student/student-dashboard.php');
    } elseif ($user_role === 'Mentor' || $user_role === 'Admin') {
        header("Location: " . BASE_URL . 'mentor/mentor-dashboard.php'); // Redirect mentors/admins to their dashboard
    } else {
        header("Location: " . BASE_URL . 'login.php'); // Fallback
    }
    exit();
}

$submission = false; // Initialize submission variable

try {
    // This single query checks ownership for student, assignment for mentor, and allows admin.
    $sql = "
        SELECT 
            s.*, 
            e.title as exam_title, 
            u.name as student_name, 
            u.roll_no, 
            u.batch_name, 
            es.mentor_id,
            e.shuffle_options -- Get shuffle options config for correct answer display
        FROM submissions s 
        JOIN exams e ON s.exam_id = e.id 
        JOIN users u ON s.student_id = u.id
        JOIN exam_schedule es ON s.schedule_id = es.id  -- Join with exam_schedule to check mentor_id
        WHERE s.id = ? "; // Start with submission ID

    $params = [$submission_id];

    if ($user_role === 'Student') {
        $sql .= " AND u.id = ?"; // Student can only view their own submission
        $params[] = $user_id;
    } elseif ($user_role === 'Mentor') {
        $sql .= " AND es.mentor_id = ?"; // Mentor can only view submissions for exams they are assigned to
        $params[] = $user_id;
    } elseif ($user_role === 'Admin') {
        // Admin has full access, no additional WHERE clause needed for role check here
    } else {
        // Unknown role or not logged in, prevent access.
        die("Error: Insufficient privileges.");
    }
    
    $stmt_check = $pdo->prepare($sql);
    $stmt_check->execute($params);
    $submission = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        die("Error: Submission not found or you do not have permission to view this answer sheet.");
    }
    
} catch (PDOException $e) {
    error_log("Database error in view_answer_sheet.php: " . $e->getMessage());
    die("An unexpected database error occurred. Please try again later. Error: " . $e->getMessage()); // Show error for debugging
}

// If we reach here, and $submission is false, something went wrong with the logic/query.
if (!$submission) {
    header("Location: " . BASE_URL . "login.php"); // Should not happen if previous die() is hit.
    exit();
}

// --- Fetch all questions and student's answers for this submission ---
$sql = "
    SELECT 
        q.id as question_id,
        q.question_text, 
        q.option_1, q.option_2, q.option_3, q.option_4, 
        q.correct_answer,
        a.selected_option
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.submission_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id ASC
";
$stmt_answers = $pdo->prepare($sql);
$stmt_answers->execute([$submission_id, $submission['exam_id']]);
$questions = $stmt_answers->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Answer Sheet - <?php echo htmlspecialchars($submission['exam_title']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Custom styles for answer sheet view */
        body {
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            font-family: 'Poppins', sans-serif;
            color: #f0f0f0;
        }
        .answer-sheet-container {
            max-width: 800px;
            margin: 30px auto;
            background: #1a1d2e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .answer-sheet-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .answer-sheet-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .answer-sheet-info p {
            margin: 5px 0;
            font-size: 1.1em;
        }
        .logo-img {
            height: 50px;
            margin-bottom: 15px;
        }
        .question-block {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        }
        .question-block:last-child {
            border-bottom: none;
        }
        .question-block h4 {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        .options-list {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }
        .options-list li {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            word-wrap: break-word; /* Ensure long options wrap */
        }
        .options-list li.correct {
            background-color: rgba(40, 167, 69, 0.3); /* Green for correct */
            border-color: #28a745;
        }
        .options-list li.selected-incorrect {
            background-color: rgba(220, 53, 69, 0.3); /* Red for selected incorrect */
            border-color: #dc3545;
        }
        .options-list li.selected-correct {
            background-color: rgba(40, 167, 69, 0.5); /* Darker green if selected and correct */
            border-color: #28a745;
        }
        .options-list li.not-answered { /* Style for questions not answered */
            background-color: rgba(108, 117, 125, 0.2); /* Grey for not answered */
            border-color: #6c757d;
        }
        .options-list li i {
            margin-right: 10px;
            font-size: 1.1em;
        }
        .options-list li.correct i { color: #28a745; }
        .options-list li.selected-incorrect i { color: #dc3545; }
        .options-list li.selected-correct i { color: #fff; } /* White icon for selected correct for better contrast */
        .options-list li.not-answered i { color: #6c757d; }

        /* Back button styling */
        .back-button-container {
            text-align: center;
            margin-top: 30px;
        }
        .btn-back-to-dashboard {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-back-to-dashboard:hover {
            background-color: #0056b3;
        }
        .btn-back-to-dashboard i {
            margin-right: 8px;
        }
        .no-answers-message {
            text-align: center;
            padding: 20px;
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #ffcccc; /* Light red text */
        }
    </style>
</head>
<body>
    <div class="answer-sheet-container">
        <div class="answer-sheet-header">
            <img src="<?php echo BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png'); ?>" alt="<?php echo get_setting('app_name'); ?> Logo" class="logo-img">
            <h1><?php echo htmlspecialchars($submission['exam_title']); ?> Answer Sheet</h1>
            <p><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_name']); ?></p>
            <?php if (!empty($submission['roll_no'])): ?><p><strong>Roll No:</strong> <?php echo htmlspecialchars($submission['roll_no']); ?></p><?php endif; ?>
            <?php if (!empty($submission['batch_name'])): ?><p><strong>Batch:</strong> <?php echo htmlspecialchars($submission['batch_name']); ?></p><?php endif; ?>
            <p><strong>Score:</strong> <?php echo $submission['marks_obtained']; ?> / <?php echo $submission['total_marks']; ?></p>
            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($submission['submission_time'])); ?></p>
        </div>

        <?php if (empty($questions) || ($submission['marks_obtained'] === 0 && $submission['total_marks'] === 0)): ?>
            <div class="no-answers-message">
                <p><i class="fa-solid fa-triangle-exclamation"></i> No answers were submitted for this exam, or the exam had no questions.</p>
                <p>Below are the correct answers for the questions in this exam:</p>
            </div>
            <?php 
                // Fetch actual questions with correct answers to display if no submission
                $stmt_all_questions = $pdo->prepare("SELECT id, question_text, option_1, option_2, option_3, option_4, correct_answer FROM questions WHERE exam_id = ? ORDER BY id ASC");
                $stmt_all_questions->execute([$submission['exam_id']]);
                $all_questions_for_exam = $stmt_all_questions->fetchAll(PDO::FETCH_ASSOC);

                $question_num = 1;
                foreach ($all_questions_for_exam as $q): ?>
                    <div class="question-block">
                        <h4>Q<?php echo $question_num++; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h4>
                        <ul class="options-list">
                            <?php for ($i = 1; $i <= 4; $i++): 
                                $option_text = htmlspecialchars($q['option_' . $i]);
                                $class = ($i == $q['correct_answer']) ? 'correct' : '';
                                $icon = ($i == $q['correct_answer']) ? '<i class="fa-solid fa-check"></i>' : '';
                            ?>
                                <li class="<?php echo $class; ?>">
                                    <?php echo $icon; ?> (<?php echo $i; ?>) <?php echo $option_text; ?>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

        <?php else: ?>
            <?php $question_num = 1; ?>
            <?php foreach ($questions as $q): ?>
                <div class="question-block">
                    <h4>Q<?php echo $question_num++; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h4>
                    <ul class="options-list">
                        <?php for ($i = 1; $i <= 4; $i++): 
                            $option_text = htmlspecialchars($q['option_' . $i]);
                            $is_correct_answer = ($i == $q['correct_answer']);
                            $is_selected_answer = ($q['selected_option'] !== null && $i == $q['selected_option']);
                            
                            $class = '';
                            $icon = '';

                            if ($is_selected_answer) {
                                if ($is_correct_answer) {
                                    $class = 'selected-correct';
                                    $icon = '<i class="fa-solid fa-check"></i>';
                                } else {
                                    $class = 'selected-incorrect';
                                    $icon = '<i class="fa-solid fa-times"></i>';
                                }
                            } elseif ($is_correct_answer) {
                                $class = 'correct';
                                $icon = '<i class="fa-solid fa-check"></i>';
                            } elseif ($q['selected_option'] === null) {
                                $class = 'not-answered';
                                $icon = '<i class="fa-solid fa-minus"></i>'; // Icon for not answered
                            }
                        ?>
                            <li class="<?php echo $class; ?>">
                                <?php echo $icon; ?> (<?php echo $i; ?>) <?php echo $option_text; ?>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="back-button-container">
            <a href="<?php echo BASE_URL . ($user_role === 'Student' ? 'student/student-dashboard.php' : 'mentor/mentor-dashboard.php'); ?>" class="btn-back-to-dashboard">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>