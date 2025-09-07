<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Mentor'); // Ensure only Mentors can access this page

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - <?php echo htmlspecialchars($appName); ?></title>
    
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
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'mentor-dashboard.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="#" class="nav-item" id="createExamNavLink"><i class="fa-solid fa-plus-square"></i> <span>Create Exam</span></a>
                <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php#scheduled-exams-section" class="nav-item"><i class="fa-solid fa-calendar-alt"></i> <span>Scheduled Exams</span></a>
                <a href="#" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Leaderboards</span></a> <!-- This link needs a specific exam schedule -->
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
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p>Overview of your created and scheduled exams.</p>
            </header>

            <!-- Exams Created by Mentor -->
            <section class="content-panel">
                <h3><i class="fa-solid fa-file-alt"></i> My Exams</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Type</th>
                            <th>Questions</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mentor-exams-table-body">
                        <!-- Exams created by this mentor will be loaded here by JavaScript -->
                        <tr><td colspan="5" class="loader">Loading exams...</td></tr>
                    </tbody>
                </table>
            </section>
            
            <!-- Scheduled Exams by Mentor -->
            <section class="content-panel" style="margin-top: 30px;" id="scheduled-exams-section">
                <h3><i class="fa-solid fa-calendar-check"></i> My Scheduled Exams</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mentor-scheduled-exams-table-body">
                        <!-- Exams scheduled by this mentor will be loaded here by JavaScript -->
                        <tr><td colspan="6" class="loader">Loading schedules...</td></tr>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <!-- Create/Edit Exam Modal (Reusing Admin's Modal structure) -->
    <div id="examModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeExamModalBtn">&times;</span>
            <form id="examForm">
                <h3 id="examModalTitle">Create New Exam</h3>
                <input type="hidden" id="examId" name="examId">
                <div class="form-group">
                    <label for="title">Exam Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group-inline">
                    <input type="checkbox" id="shuffle_questions" name="shuffle_questions" value="1">
                    <label for="shuffle_questions">Shuffle Questions</label>
                </div>
                 <div class="form-group-inline">
                    <input type="checkbox" id="shuffle_options" name="shuffle_options" value="1">
                    <label for="shuffle_options">Shuffle Options</label>
                </div>
                <div class="form-group-inline">
                    <input type="checkbox" id="is_omr_exam" name="is_omr_exam" value="1">
                    <label for="is_omr_exam">This is an OMR-based Exam</label>
                </div>
                <button type="submit" class="btn-primary">Save Exam</button>
            </form>
        </div>
    </div>

    <!-- Schedule Exam Modal (Reusing Admin's Modal structure) -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeScheduleModalBtn">&times;</span>
            <form id="scheduleForm">
                <h3 id="scheduleModalTitle">Schedule Exam: <span id="scheduledExamTitle"></span></h3>
                <input type="hidden" id="scheduleExamId" name="exam_id">
                <div class="form-group">
                    <label for="scheduled_date">Date:</label>
                    <input type="date" id="scheduled_date" name="scheduled_date" required>
                </div>
                <div class="form-group">
                    <label for="start_time">Start Time:</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time:</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label for="duration_minutes">Duration (minutes):</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="60" required>
                </div>
                <div class="form-group">
                    <label for="mentor_id">Assign Mentor:</label>
                    <select id="mentor_id" name="mentor_id" required>
                        <option value="">Select a Mentor</option>
                        <!-- Mentors will be loaded here by JavaScript -->
                    </select>
                </div>
                <button type="submit" class="btn-primary">Create Schedule</button>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const CURRENT_MENTOR_ID = <?php echo $_SESSION['user_id']; ?>; 
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/mentor_dashboard.js"></script>
</body>
</html>