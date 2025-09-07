<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Student'); // Ensure only students can access this page

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($appName); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>student/student-dashboard.php" class="logo">
                     <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?> Logo" style="height: 40px;">
                    <span class="logo-text">HTEC<span>Exam</span></span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_URL; ?>student/student-dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'student-dashboard.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="<?php echo BASE_URL; ?>student/profile.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> <span>My Profile</span></a>
                <a href="<?php echo BASE_URL; ?>student/exam_history.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'exam_history.php') ? 'active' : ''; ?>"><i class="fa-solid fa-history"></i> <span>Exam History</span></a>
            </nav>
            <div class="sidebar-footer">
                 <a href="<?php echo BASE_URL; ?>logout.php" class="nav-item"><i class="fa-solid fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <div class="menu-toggle" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </div>
            <header class="header">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p>Ready for your next challenge?</p>
            </header>

            <!-- Upcoming Exams -->
            <section class="content-panel">
                <div class="header" style="margin-bottom: 20px;">
                    <h3><i class="fa-solid fa-hourglass-half"></i> Upcoming & Running Exams</h3>
                    <a href="<?php echo BASE_URL; ?>assets/templates/omr_sheet_template.pdf" download class="btn-primary">
                        <i class="fa-solid fa-download"></i> Download Blank OMR Sheet
                    </a>
                </div>
                <div id="upcoming-exams-list">
                    <!-- Upcoming exams will be loaded here by JavaScript -->
                    <div class="loader">Loading exams...</div>
                </div>
            </section>
            
            <!-- Exam Stats -->
            <section class="content-panel" style="margin-top: 30px;">
                 <h3><i class="fa-solid fa-chart-pie"></i> Your Performance</h3>
                 <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fa-solid fa-file-alt"></i>
                        <div>
                            <h3 id="totalExamsTaken">0</h3>
                            <p>Exams Taken</p>
                        </div>
                    </div>
                     <div class="stat-card">
                        <i class="fa-solid fa-bullseye"></i>
                        <div>
                            <h3 id="averageScore">0%</h3>
                            <p>Average Score</p>
                        </div>
                    </div>
                 </div>
            </section>
            
            <!-- Exam History -->
            <section class="content-panel" style="margin-top: 30px;">
                 <h3><i class="fa-solid fa-history"></i> Exam History</h3>
                 <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Rank</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="exam-history-body">
                        <!-- Submission history will be loaded here -->
                        <tr><td colspan="5" class="loader">Loading history...</td></tr>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <!-- OMR Submission Modal -->
    <div id="omrSubmissionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeOmrModalBtn">&times;</span>
            <h3 id="omrModalTitle">Submit OMR Answer Sheet</h3>
            <p>Exam: <strong id="omrExamTitle"></strong></p>
            <div id="omrSubmissionMessage" style="margin-top: 15px;"></div>

            <div class="omr-camera-container">
                <video id="omrVideoStream" autoplay></video>
                <canvas id="omrCanvas" style="display: none;"></canvas>
                <img id="omrImagePreview" src="" style="display: none; max-width: 100%; height: auto; margin-top: 10px;">
            </div>

            <div class="omr-actions">
                <button class="btn-primary" id="startCameraButton"><i class="fa-solid fa-camera"></i> Start Camera</button>
                <button class="btn-secondary" id="takePictureButton" style="display: none;"><i class="fa-solid fa-circle-dot"></i> Take Picture</button>
                <button class="btn-danger" id="retakePictureButton" style="display: none;"><i class="fa-solid fa-redo"></i> Retake</button>
                <button class="btn-primary" id="uploadOcrButton" style="display: none;"><i class="fa-solid fa-upload"></i> Upload & Evaluate</button>
            </div>
            
            <!-- Hidden form for actual submission -->
            <form id="omrSubmissionForm" enctype="multipart/form-data" style="display: none;">
                <input type="hidden" name="schedule_id" id="omrScheduleId">
                <input type="hidden" name="answer_key_id" id="omrAnswerKeyId">
                <input type="hidden" name="exam_title" id="omrHiddenExamTitle">
                <input type="hidden" name="omr_sheet_data" id="omrSheetData"> <!-- To hold image data from canvas -->
            </form>

        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/student_dashboard.js"></script>
</body>
</html>