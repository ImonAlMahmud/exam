<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Admin');

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management - <?php echo htmlspecialchars($appName); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="logo">
                     <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?> Logo" style="height: 40px;">
                    <span class="logo-text">HTEC<span>Exam</span></span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="<?php echo BASE_URL; ?>admin/users.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="<?php echo BASE_URL; ?>admin/exams.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'exams.php' || basename($_SERVER['PHP_SELF']) == 'import.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-file-alt"></i> <span>Exam Management</span></a>
                <a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-bar"></i> <span>Reports</span></a>
                <a href="<?php echo BASE_URL; ?>admin/website_settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'website_settings.php') ? 'active' : ''; ?>"><i class="fa-solid fa-cog"></i> <span>Website Settings</span></a>
                <a href="<?php echo BASE_URL; ?>admin/restore_backup.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'restore_backup.php') ? 'active' : ''; ?>"><i class="fa-solid fa-database"></i> <span>Backup & Restore</span></a>
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
                <h2>Exam Management</h2>
                <button id="createExamBtn" class="btn-primary"><i class="fa-solid fa-plus"></i> Create New Exam</button>
            </header>
            
            <!-- Exams Table -->
            <section class="content-panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Type</th>
                            <th>Questions</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="exams-table-body">
                        <!-- Exam data will be loaded here by JavaScript -->
                        <tr><td colspan="6" class="loader">Loading exams...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Create/Edit Exam Modal -->
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

    <!-- Schedule Exam Modal -->
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

    <!-- View Schedules Modal -->
    <div id="viewSchedulesModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeViewSchedulesModalBtn">&times;</span>
            <h3 id="viewSchedulesModalTitle">Schedules for: <span id="viewSchedulesExamTitle"></span></h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Mentor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="schedules-for-exam-body">
                        <!-- Scheduled exams for selected exam will be loaded here by JavaScript -->
                        <tr><td colspan="6" class="loader">Loading schedules...</td></tr>
                    </tbody>
                </table>
            </div>
            <button id="scheduleNewFromView" class="btn-primary" style="margin-top: 20px;"><i class="fa-solid fa-calendar-plus"></i> Schedule New</button>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/exam_management.js"></script>
</body>
</html>