<?php
// Enable error reporting at the very top for debugging.
// This should be removed or set to '0' in a production environment.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Admin'); // Ensure only Admins can access this page

// Fetch dashboard stats from the database (Example queries)
// Initialize variables with default values before the try-catch block
$total_students = 0;
$total_mentors = 0;
$total_exams = 0;
$total_questions = 0;
$last_exam_date = 'N/A'; // Initialize with a string for clarity

try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $total_students = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'Student'")->fetchColumn();
        $total_mentors = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'Mentor'")->fetchColumn();
        $total_exams = $pdo->query("SELECT COUNT(id) FROM exams")->fetchColumn();
        $total_questions = $pdo->query("SELECT COUNT(id) FROM questions")->fetchColumn();
        
        $last_exam_stmt = $pdo->query("SELECT MAX(scheduled_date) FROM exam_schedule");
        $last_exam_date_raw = $last_exam_stmt->fetchColumn();
        if ($last_exam_date_raw) {
            $last_exam_date = date('M d, Y', strtotime($last_exam_date_raw));
        }
    } else {
        // If PDO is not set up, log it but don't crash
        error_log("DEBUG: dashboard.php - PDO connection not available for stats.");
    }
} catch (PDOException $e) {
    error_log("Database error fetching dashboard stats: " . $e->getMessage());
    // In case of error, variables remain at their default initialized values.
}

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($appName); ?></title>
    
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
                <a href="<?php echo BASE_URL; ?>admin/exams.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'exams.php' || basename($_SERVER['PHP_SELF']) == 'import.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-alt"></i> <span>Exam Management</span></a>
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
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p>Here's a summary of your application.</p>
            </header>
            
            <!-- Stats Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <i class="fa-solid fa-user-graduate"></i>
                    <div>
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <div>
                        <h3><?php echo $total_mentors; ?></h3>
                        <p>Total Mentors</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-file-signature"></i>
                    <div>
                        <h3><?php echo $total_exams; ?></h3>
                        <p>Total Exams</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-question-circle"></i>
                    <div>
                        <h3><?php echo $total_questions; ?></h3>
                        <p>Total Questions</p>
                    </div>
                </div>
                 <div class="stat-card">
                    <i class="fa-solid fa-calendar-alt"></i>
                    <div>
                        <h3><?php echo $last_exam_date; ?></h3>
                        <p>Last Exam Date</p>
                    </div>
                </div>
            </section>
            
            <!-- Other dashboard widgets will go here -->
            <section class="content-panel">
                <h3>Recent Activities</h3>
                <p>Activity log will be displayed here in the future.</p>
            </section>

        </main>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <!-- Add page-specific scripts here if any -->
</body>
</html>