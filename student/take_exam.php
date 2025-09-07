<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php'; // config.php now sets PHP default timezone to Asia/Dhaka
require_once '../includes/auth_check.php';
require_login('Student');

// Get schedule ID from URL and validate
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
if ($schedule_id === 0) {
    header("Location: student-dashboard.php");
    exit();
}

// Fetch exam details to verify it's currently running AND get duration
$stmt = $pdo->prepare("SELECT e.title, es.start_time, es.end_time, es.duration_minutes FROM exam_schedule es JOIN exams e ON es.exam_id = e.id WHERE es.id = ? AND es.status = 'Running'");
$stmt->execute([$schedule_id]);
$exam_schedule_details = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam_schedule_details) {
    // If exam is not running or doesn't exist, redirect
    header("Location: student-dashboard.php?error=exam_not_running");
    exit();
}

$exam_title = $exam_schedule_details['title'];
$scheduled_start_datetime_str = $exam_schedule_details['start_time'];
$scheduled_end_datetime_str = $exam_schedule_details['end_time'];
$duration_minutes = $exam_schedule_details['duration_minutes'];

// --- Server-side calculation of effective exam end time ---
// PHP's default timezone is now 'Asia/Dhaka'.
$dt_now = new DateTime('now');
$dt_scheduled_end = new DateTime($scheduled_end_datetime_str);

// Calculate end time based on duration from the current time
$dt_duration_end = (clone $dt_now)->modify("+{$duration_minutes} minutes");

// The effective end time is the earlier of the scheduled end time or the duration-based end time.
// Store this as a Unix timestamp in the session for consistency.
$_SESSION['exam_effective_end_time'] = min($dt_scheduled_end->getTimestamp(), $dt_duration_end->getTimestamp());
$_SESSION['taking_exam_id'] = $schedule_id; // Set session flag


// Ensure a default app name and logo path if settings are not fully loaded (e.g. during restore or DB issues)
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam: <?php echo htmlspecialchars($exam_title); ?> | <?php echo htmlspecialchars($appName); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/exam.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="exam-container">
        <header class="exam-header">
            <h1 id="examTitle"><?php echo htmlspecialchars($exam_title); ?></h1>
            <div id="countdown-timer" class="timer" data-endtime="<?php echo $_SESSION['exam_effective_end_time'] * 1000; ?>">
                <i class="fa-solid fa-clock"></i> Time Left: <span>--:--:--</span>
            </div>
        </header>

        <main class="exam-body">
            <div id="question-container">
                <!-- Questions will be loaded here by JavaScript -->
                <div class="loader">Loading Questions...</div>
            </div>
            
            <form id="examForm">
                <!-- This form is mainly for structure; submission is handled by JS -->
            </form>
        </main>

        <footer class="exam-footer">
            <div id="question-navigation">
                <!-- Navigation buttons will be generated here -->
            </div>
            <button id="submitExamBtn" class="btn-submit">Submit Exam</button>
        </footer>
    </div>
    
    <script>
        const scheduleId = <?php echo $schedule_id; ?>;
        const baseUrl = '<?php echo BASE_URL; ?>';
        // Pass the effective end time (timestamp in milliseconds) to JS for accurate countdown
        const effectiveExamEndTimeMillis = <?php echo $_SESSION['exam_effective_end_time'] * 1000; ?>;
        const questionsPerPage = 10; 
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/take_exam.js"></script>
</body>
</html>