<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Mentor'); // Only mentors can access this page

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

if ($schedule_id === 0) {
    header("Location: " . BASE_URL . "mentor/mentor-dashboard.php?error=no_schedule_id");
    exit();
}

// Fetch exam and schedule details
$stmt = $pdo->prepare("
    SELECT 
        e.title as exam_title, 
        es.scheduled_date, 
        es.start_time, 
        es.end_time, 
        es.duration_minutes
    FROM exam_schedule es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.id = ? AND es.mentor_id = ?
");
$stmt->execute([$schedule_id, $_SESSION['user_id']]);
$schedule_details = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule_details) {
    die("Error: Schedule not found or you do not have permission to view this leaderboard.");
}

// Define default values for app name and logo if settings are not available
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo htmlspecialchars($schedule_details['exam_title']); ?> | <?php echo htmlspecialchars($appName); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar"> <!-- Added ID 'sidebar' -->
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="logo">
                     <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?> Logo" style="height: 40px;">
                    <span class="logo-text">HTEC<span>Exam</span></span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'mentor-dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="nav-item" id="createExamNavLink"><i class="fa-solid fa-plus-square"></i> <span>Create Exam</span></a>
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php#scheduled-exams-section" class="nav-item"><i class="fa-solid fa-calendar-alt"></i> <span>Scheduled Exams</span></a>
                <a href="<?php echo BASE_URL; ?>mentor/leaderboard.php?schedule_id=<?php echo $schedule_id; ?>" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'leaderboard.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-chart-line"></i> <span>Leaderboards</span></a>
                <a href="<?php echo BASE_URL; ?>mentor/profile.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> <span>My Profile</span></a>
            </nav>
            <div class="sidebar-footer">
                 <a href="<?php echo BASE_URL; ?>logout.php" class="nav-item"><i class="fa-solid fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="main-content"> <!-- Added ID 'main-content' -->
            <div class="menu-toggle" id="menu-toggle"> <!-- Added menu-toggle directly in HTML -->
                <i class="fa-solid fa-bars"></i>
            </div>
            <header class="header">
                <div>
                    <h2>Leaderboard for: <?php echo htmlspecialchars($schedule_details['exam_title']); ?></h2>
                    <p>Date: <?php echo date('M d, Y', strtotime($schedule_details['scheduled_date'])); ?> | Time: <?php echo date('h:i A', strtotime($schedule_details['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule_details['end_time'])); ?> (<?php echo $schedule_details['duration_minutes']; ?> mins)</p>
                </div>
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="btn-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            </header>
            
            <section class="content-panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student Name</th>
                            <th>Roll No</th>
                            <th>Batch</th>
                            <th>Score</th>
                            <th>Submission Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboard-body">
                        <!-- Leaderboard data will be loaded here by JavaScript -->
                        <tr><td colspan="7" class="loader">Loading leaderboard...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const SCHEDULE_ID = <?php echo $schedule_id; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/mentor_leaderboard.js"></script>
</body>
</html>