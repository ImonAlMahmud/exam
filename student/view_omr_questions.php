<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Student');

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($exam_id === 0) {
    die("Error: Invalid Exam ID provided.");
}

try {
    // Fetch exam details to verify it's an OMR exam and get its title
    $stmt_exam_details = $pdo->prepare("SELECT title, is_omr_exam, shuffle_questions, shuffle_options FROM exams WHERE id = ?");
    $stmt_exam_details->execute([$exam_id]);
    $exam = $stmt_exam_details->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        die("Error: Exam not found.");
    }
    if (!$exam['is_omr_exam']) {
        die("Error: This is not an OMR-based exam.");
    }

    // Fetch all questions for the exam
    $stmt_questions = $pdo->prepare("SELECT id, question_text, option_1, option_2, option_3, option_4 FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $stmt_questions->execute([$exam_id]);
    $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        die("Error: No questions found for this exam.");
    }

    // Apply shuffle logic if configured for the exam
    if ($exam['shuffle_questions']) {
        shuffle($questions);
    }

    $processed_questions = [];
    foreach ($questions as $original_question) {
        $question_to_add = [
            'id' => $original_question['id'],
            'question_text' => htmlspecialchars_decode($original_question['question_text'], ENT_QUOTES)
        ];
        
        $options_to_shuffle = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($original_question["option_{$i}"])) {
                $options_to_shuffle[] = [
                    'text' => htmlspecialchars_decode($original_question["option_{$i}"], ENT_QUOTES)
                ];
            }
        }

        if ($exam['shuffle_options']) {
            shuffle($options_to_shuffle);
        }
        
        // Reassign shuffled options for display
        foreach ($options_to_shuffle as $new_idx => $opt_data) {
            $question_to_add["option_".($new_idx + 1)] = $opt_data['text'];
        }
        $processed_questions[] = $question_to_add;
    }

} catch (PDOException $e) {
    error_log("DB Error in view_omr_questions.php: " . $e->getMessage());
    die("Database error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMR Question Paper - <?php echo htmlspecialchars($exam['title']); ?></title>
    <style>
        /* Import a clean font for both English and Bangla */
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap');
        
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            .question-paper-container { box-shadow: none; margin: 0; }
        }
        
        body {
            font-family: 'Hind Siliguri', 'Roboto', sans-serif; /* Hind Siliguri for Bangla, Roboto as fallback */
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .question-paper-container {
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            background-color: white;
            padding: 15mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 { font-size: 24px; margin: 0; }
        .header p { font-size: 16px; margin: 5px 0; }

        .question-columns {
            columns: 2; /* Create two columns */
            column-gap: 15mm; /* Space between columns */
        }
        
        .question-block {
            margin-bottom: 15px;
            padding-bottom: 10px;
            break-inside: avoid-column; /* Prevent questions from breaking across columns */
            font-size: 14px;
        }
        .question-block .question-text {
            font-weight: 700;
            margin-bottom: 8px;
        }
        .question-block .options {
            list-style: none;
            padding-left: 20px;
        }
        .question-block .options li {
            margin-bottom: 4px;
        }
        
        .print-button {
            margin-bottom: 20px;
            padding: 12px 24px;
            font-size: 18px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Print or Save as PDF</button>

    <div class="question-paper-container">
        <header class="header">
            <h1>OMR Question Paper</h1>
            <p><?php echo htmlspecialchars(get_setting('app_name', 'HTEC Exam System')); ?></p>
            <p><strong>Exam:</strong> <?php echo htmlspecialchars($exam['title']); ?></p>
        </header>

        <div class="question-columns">
            <?php $question_num = 1; ?>
            <?php foreach ($processed_questions as $q): ?>
                <div class="question-block">
                    <div class="question-text">
                        <?php echo $question_num++; ?>. <?php echo $q['question_text']; ?>
                    </div>
                    <ul class="options">
                        <li>(a) <?php echo $q['option_1'] ?? ''; ?></li>
                        <li>(b) <?php echo $q['option_2'] ?? ''; ?></li>
                        <li>(c) <?php echo $q['option_3'] ?? ''; ?></li>
                        <li>(d) <?php echo $q['option_4'] ?? ''; ?></li>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>