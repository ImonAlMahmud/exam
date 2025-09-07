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
    <title>My Profile - <?php echo htmlspecialchars($appName); ?></title>
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
                <a href="<?php echo BASE_URL; ?>student/student-dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'student-dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                <a href="<?php echo BASE_URL; ?>student/profile.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-user"></i> <span>My Profile</span></a>
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
                <h2>My Profile</h2>
            </header>

            <div class="profile-layout">
                <!-- Profile Picture Section -->
                <section class="content-panel profile-picture-panel">
                    <h3>Profile Picture</h3>
                    <img id="profile-pic-preview" src="<?php echo BASE_URL; ?>uploads/profile_pictures/default.png" alt="Profile Picture" class="profile-pic-large">
                    <form id="pictureForm">
                        <input type="file" name="profile_picture" id="profile_picture_input" accept="image/*">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-upload"></i> Upload New Picture</button>
                    </form>
                </section>

                <!-- Profile Details Section -->
                <section class="content-panel profile-details-panel">
                    <h3>Profile Details</h3>
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (Cannot be changed):</label>
                            <input type="email" id="email" name="email" readonly>
                        </div>
                         <div class="form-group">
                            <label for="roll_no">Roll No:</label>
                            <input type="text" id="roll_no" name="roll_no">
                        </div>
                         <div class="form-group">
                            <label for="batch_name">Batch Name:</label>
                            <input type="text" id="batch_name" name="batch_name">
                        </div>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                    </form>
                    <hr>
                    <h3>Change Password</h3>
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                         <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-key"></i> Change Password</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/profile.js"></script>
</body>
</html>