<?php
// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Allow both Admin and Mentor roles to access this page
// If the user is neither Admin nor Mentor, require_login() will redirect them.
if ($_SESSION['user_role'] === 'Admin') {
    require_login('Admin');
} elseif ($_SESSION['user_role'] === 'Mentor') {
    require_login('Mentor');
    // For mentors, add an additional check: they can only import questions for exams they created.
    $exam_id_from_url = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
    if ($exam_id_from_url > 0) {
        try {
            $stmt_check_ownership = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND created_by = ?");
            $stmt_check_ownership->execute([$exam_id_from_url, $_SESSION['user_id']]);
            if ($stmt_check_ownership->rowCount() === 0) {
                // Mentor is trying to access an exam they didn't create
                header("Location: " . BASE_URL . "mentor/mentor-dashboard.php?error=unauthorized_exam");
                exit();
            }
        } catch (PDOException $e) {
            error_log("DB Error checking exam ownership in import.php: " . $e->getMessage());
            die("Database error. Please try again.");
        }
    }
} else {
    // If not Admin or Mentor, redirect to login (auth_check.php would have already done this usually)
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Get the exam ID from the URL and validate it
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if ($exam_id === 0) {
    // Redirect if no exam_id is provided, or invalid.
    // Redirect based on role if no exam_id is present
    if ($_SESSION['user_role'] === 'Admin') {
        header("Location: " . BASE_URL . "admin/exams.php?error=no_exam_id");
    } elseif ($_SESSION['user_role'] === 'Mentor') {
        header("Location: " . BASE_URL . "mentor/mentor-dashboard.php?error=no_exam_id");
    } else {
        header("Location: " . BASE_URL . "login.php"); // Fallback
    }
    exit();
}

// Fetch exam details to display on the page
$stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array for easier access

if (!$exam) {
    // Redirect if exam not found
    if ($_SESSION['user_role'] === 'Admin') {
        header("Location: " . BASE_URL . "admin/exams.php?error=exam_not_found");
    } elseif ($_SESSION['user_role'] === 'Mentor') {
        header("Location: " . BASE_URL . "mentor/mentor-dashboard.php?error=exam_not_found");
    } else {
        header("Location: " . BASE_URL . "login.php"); // Fallback
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo htmlspecialchars($exam['title']); ?> | <?php echo get_setting('app_name'); ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL . ($_SESSION['user_role'] === 'Admin' ? 'admin/dashboard.php' : 'mentor/mentor-dashboard.php'); ?>" class="logo">
                     <img src="<?php echo BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png'); ?>" alt="Logo" style="height: 40px;">
                </a>
            </div>
            <nav class="sidebar-nav">
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-item"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="nav-item"><i class="fa-solid fa-users"></i> User Management</a>
                    <a href="<?php echo BASE_URL; ?>admin/exams.php" class="nav-item active"><i class="fa-solid fa-file-alt"></i> Exam Management</a>
                    <a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
                    <a href="<?php echo BASE_URL; ?>admin/website_settings.php" class="nav-item"><i class="fa-solid fa-cog"></i> Website Settings</a>
                    <a href="<?php echo BASE_URL; ?>admin/restore_backup.php" class="nav-item"><i class="fa-solid fa-database"></i> Backup & Restore</a>
                <?php elseif ($_SESSION['user_role'] === 'Mentor'): ?>
                    <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php" class="nav-item active"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a>
                    <a href="#" class="nav-item" id="createExamNavLink"><i class="fa-solid fa-plus-square"></i> Create Exam</a>
                    <a href="<?php echo BASE_URL; ?>mentor/mentor-dashboard.php#scheduled-exams-section" class="nav-item"><i class="fa-solid fa-calendar-alt"></i> Scheduled Exams</a>
                    <a href="#" class="nav-item"><i class="fa-solid fa-chart-line"></i> Leaderboards</a>
                    <a href="<?php echo BASE_URL; ?>mentor/profile.php" class="nav-item"><i class="fa-solid fa-user"></i> My Profile</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                 <a href="<?php echo BASE_URL; ?>logout.php" class="nav-item"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h2>Manage Questions</h2>
                    <p>Exam: <strong><?php echo htmlspecialchars($exam['title']); ?></strong></p>
                </div>
                <a href="<?php echo BASE_URL . ($_SESSION['user_role'] === 'Admin' ? 'admin/exams.php' : 'mentor/mentor-dashboard.php'); ?>" class="btn-primary"><i class="fa-solid fa-arrow-left"></i> Back to Exams</a>
            </header>

            <!-- Import Section -->
            <section class="content-panel">
                <h3>Import Questions from Excel</h3>
                <p>Upload an .xlsx file with questions. Please follow the correct format.</p>
                
                <form id="importForm" enctype="multipart/form-data">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <div class="form-group">
                        <label for="questionFile">Select Excel File (.xlsx):</label>
                        <input type="file" id="questionFile" name="questionFile" accept=".xlsx" required>
                    </div>
                    <button type="submit" class="btn-primary" id="importBtn">
                        <i class="fa-solid fa-upload"></i> Upload & Import
                    </button>
                    <!-- Link to download template -->
                    <a href="<?php echo BASE_URL; ?>assets/templates/question_template.xlsx" download class="btn-secondary">
                        <i class="fa-solid fa-download"></i> Download Template
                    </a>
                </form>
                <div id="importResult" style="margin-top: 20px;"></div>
            </section>

            <!-- Existing Questions List -->
            <section class="content-panel" style="margin-top: 30px;">
                <h3>Existing Questions</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Question Text</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="questions-table-body">
                        <!-- Questions will be loaded here by JavaScript -->
                        <tr><td colspan="2" class="loader">Loading questions...</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
<!-- Existing Questions List -->
            <!-- ... (existing code for questions list) ... -->
        </main>
    </div>

    <!-- Edit Question Modal -->
    <div id="editQuestionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeEditQuestionModal">&times;</span>
            <form id="editQuestionForm">
                <h3 id="editQuestionModalTitle">Edit Question</h3>
                <input type="hidden" id="editQuestionId" name="question_id">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>"> <!-- Pass exam_id as well -->
                
                <div class="form-group">
                    <label for="edit_question_text">Question Text:</label>
                    <textarea id="edit_question_text" name="question_text" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_option_1">Option 1:</label>
                    <input type="text" id="edit_option_1" name="option_1" required>
                </div>
                <div class="form-group">
                    <label for="edit_option_2">Option 2:</label>
                    <input type="text" id="edit_option_2" name="option_2" required>
                </div>
                <div class="form-group">
                    <label for="edit_option_3">Option 3:</label>
                    <input type="text" id="edit_option_3" name="option_3" required>
                </div>
                <div class="form-group">
                    <label for="edit_option_4">Option 4:</label>
                    <input type="text" id="edit_option_4" name="option_4" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_correct_answer">Correct Answer (Option Number 1-4):</label>
                    <input type="number" id="edit_correct_answer" name="correct_answer" min="1" max="4" required>
                </div>
                
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const EXAM_ID = <?php echo $exam_id; ?>;
        const CURRENT_USER_ROLE = '<?php echo $_SESSION['user_role']; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/import_management.js"></script>
</body>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const EXAM_ID = <?php echo $exam_id; ?>;
        const CURRENT_USER_ROLE = '<?php echo $_SESSION['user_role']; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/import_management.js"></script>
</body>
</html>