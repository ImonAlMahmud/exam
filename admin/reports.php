<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_login('Admin'); // Only Admins can access this page

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo htmlspecialchars($appName); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar"> <!-- Added ID 'sidebar' -->
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
                <a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-chart-bar"></i> <span>Reports</span></a>
                <a href="<?php echo BASE_URL; ?>admin/website_settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'website_settings.php') ? 'active' : ''; ?>"><i class="fa-solid fa-cog"></i> <span>Website Settings</span></a>
                <a href="<?php echo BASE_URL; ?>admin/restore_backup.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'restore_backup.php') ? 'active' : ''; ?>"><i class="fa-solid fa-database"></i> <span>Backup & Restore</span></a>
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
                <h2>Reports</h2>
                <p>Generate various reports about exam submissions.</p>
            </header>
            
            <!-- Generate Reports Section -->
            <section class="content-panel">
                <h3>Generate Submission Reports</h3>
                <form id="reportForm" class="report-form">
                    <div class="form-group">
                        <label for="report_type">Select Report Type:</label>
                        <select id="report_type" name="report_type" required>
                            <option value="all_submissions">All Submissions</option>
                            <option value="submissions_by_exam">Submissions by Exam</option>
                            <!-- More report types can be added here -->
                        </select>
                    </div>
                    <div class="form-group" id="examSelectGroup" style="display: none;">
                        <label for="exam_id">Select Exam:</label>
                        <select id="exam_id" name="exam_id">
                            <option value="">Select an Exam</option>
                            <!-- Exams will be loaded here by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group-inline">
                        <button type="button" id="downloadPdfBtn" class="btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</button>
                        <button type="button" id="downloadExcelBtn" class="btn-primary"><i class="fa-solid fa-file-excel"></i> Download Excel</button>
                    </div>
                </form>
                <div id="reportMessage" style="margin-top: 20px;"></div>
            </section>
        </main>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/reports.js"></script>
</body>
</html>