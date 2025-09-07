<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php'; // This must be the correct path
require_once '../includes/auth_check.php';
require_login('Admin'); // Ensure only Admins can access this page

// Define default values for app name and logo if settings are not available
$appName = "HTEC Exam System"; // Hardcode a default app name for this critical page
$logoPath = BASE_URL . 'uploads/website/logo-light.png'; // Use a hardcoded path for logo

// Only attempt to load settings if they are successfully loaded in config.php
// This check uses the global $GLOBALS['app_settings'] which is populated by _load_app_settings_from_db
// if the settings table exists and loading was successful.
if (!empty($GLOBALS['app_settings'])) {
    $appName = get_setting('app_name', $appName);
    $logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo htmlspecialchars($appName); ?></title>
    
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
                <a href="<?php echo BASE_URL; ?>admin/restore_backup.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'restore_backup.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-database"></i> <span>Backup & Restore</span></a>
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
                <h2>Backup & Restore</h2>
                <p>Manage your database backups and restore options.</p>
            </header>
            
            <!-- Backup Section -->
            <section class="content-panel">
                <h3>Create Database Backup</h3>
                <p>Download a complete SQL backup of your database.</p>
                <button id="createBackupBtn" class="btn-primary"><i class="fa-solid fa-download"></i> Create & Download Backup</button>
                <div id="backupMessage" style="margin-top: 15px;"></div>
            </section>

            <!-- Restore Section -->
            <section class="content-panel" style="margin-top: 30px;">
                <h3>Restore Database from Backup</h3>
                <p>Upload an .sql file to restore your database. This will overwrite existing data! Proceed with caution.</p>
                <form id="restoreForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="sqlFile">Select SQL Backup File (.sql):</label>
                        <input type="file" id="sqlFile" name="sqlFile" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn-danger"><i class="fa-solid fa-upload"></i> Restore Database</button>
                </form>
                <div id="restoreMessage" style="margin-top: 15px;"></div>
            </section>
        </main>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/backup_restore.js"></script>
</body>
</html>