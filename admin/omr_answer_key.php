<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Allow both Admin and Mentor roles to access this page
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id || ($user_role !== 'Admin' && $user_role !== 'Mentor')) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($exam_id === 0) {
    header("Location: " . BASE_URL . "admin/exams.php?error=no_exam_id");
    exit();
}

// Fetch exam details to verify it's an OMR exam and get its title
$stmt_exam_details = $pdo->prepare("SELECT title, is_omr_exam FROM exams WHERE id = ?");
$stmt_exam_details->execute([$exam_id]);
$exam = $stmt_exam_details->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Error: Exam not found.");
}
if (!$exam['is_omr_exam']) {
    die("Error: This is not an OMR-based exam.");
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
    <title>OMR Answer Key - <?php echo htmlspecialchars($exam['title']); ?> | <?php echo htmlspecialchars($appName); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL . ($user_role === 'Admin' ? 'admin/dashboard.php' : 'mentor/mentor-dashboard.php'); ?>" class="logo">
                     <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?> Logo" style="height: 40px;">
                    <span class="logo-text">HTEC<span>Exam</span></span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <?php if ($user_role === 'Admin'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-item"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                    <a href="<?php echo BASE_URL; ?>admin/exams.php" class="nav-item active"><i class="fa-solid fa-file-alt"></i> <span>Exam Management</span></a>
                    <a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> <span>Reports</span></a>
                    <a href="<?php echo BASE_URL; ?>admin/website_settings.php" class="nav-item"><i class="fa-solid fa-cog"></i> <span>Website Settings</span></a>
                    <a href="<?php echo BASE_URL; ?>admin/restore_backup.php" class="nav-item"><i class="fa-solid fa-database"></i> <span>Backup & Restore</span></a>
                <?php elseif ($user_role === 'Mentor'): ?>
                    <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="nav-item active"><i class="fa-solid fa-tachometer-alt"></i> <span>Dashboard</span></a>
                    <a href="#" class="nav-item" id="createExamNavLink"><i class="fa-solid fa-plus-square"></i> <span>Create Exam</span></a>
                    <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php#scheduled-exams-section" class="nav-item"><i class="fa-solid fa-calendar-alt"></i> <span>Scheduled Exams</span></a>
                    <a href="#" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Leaderboards</span></a>
                    <a href="<?php echo BASE_URL; ?>mentor/profile.php" class="nav-item"><i class="fa-solid fa-user"></i> <span>My Profile</span></a>
                <?php endif; ?>
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
                <div>
                    <h2>OMR Answer Key Management</h2>
                    <p>Exam: <strong><?php echo htmlspecialchars($exam['title']); ?></strong></p>
                </div>
                <a href="<?php echo BASE_URL . ($user_role === 'Admin' ? 'admin/exams.php' : 'mentor/mentor-dashboard.php'); ?>" class="btn-primary"><i class="fa-solid fa-arrow-left"></i> Back to Exams</a>
            </header>

            <!-- Upload Answer Key Section -->
            <section class="content-panel">
                <h3>Upload Answer Key from Excel</h3>
                <p>Upload an .xlsx file with the answer key. It should have two columns: `question_number` and `correct_option`.</p>
                
                <form id="answerKeyForm" enctype="multipart/form-data">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <div class="form-group">
                        <label for="answerKeyFile">Select Excel File (.xlsx):</label>
                        <input type="file" id="answerKeyFile" name="answerKeyFile" accept=".xlsx" required>
                    </div>
                    <button type="submit" class="btn-primary" id="uploadAnswerKeyBtn">
                        <i class="fa-solid fa-upload"></i> Upload Answer Key
                    </button>
                    <a href="<?php echo BASE_URL; ?>assets/templates/omr_answer_key_template.xlsx" download class="btn-secondary">
                        <i class="fa-solid fa-download"></i> Download Template
                    </a>
                </form>
                <div id="uploadResultMessage" style="margin-top: 20px;"></div>
            </section>

            <!-- Existing Answer Key List -->
            <section class="content-panel" style="margin-top: 30px;">
                <h3>Existing Answer Keys</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Uploaded By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="answer-keys-table-body">
                        <!-- Answer Keys will be loaded here -->
                        <tr><td colspan="4" class="loader">Loading answer keys...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const EXAM_ID = <?php echo $exam_id; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/omr_answer_key.js"></script>
</body>
</html>