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
    <title>Website Settings - <?php echo htmlspecialchars($appName); ?></title>
    
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
                <a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-bar"></i> <span>Reports</span></a>
                <a href="<?php echo BASE_URL; ?>admin/website_settings.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'website_settings.php') ? 'active' : ''; ?> active"><i class="fa-solid fa-cog"></i> <span>Website Settings</span></a>
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
                <h2>Website Settings</h2>
                <p>Manage the content and appearance of your website's homepage.</p>
            </header>
            
            <section class="content-panel">
                <form id="generalSettingsForm">
                    <h3>General Settings</h3>
                    <div class="form-group">
                        <label for="app_name">Application Name:</label>
                        <input type="text" id="app_name" name="app_name" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="developer_name">Developer Name:</label>
                        <input type="text" id="developer_name" name="developer_name" value="">
                    </div>
                    <div class="form-group">
                        <label for="support_email">Support Email:</label>
                        <input type="email" id="support_email" name="support_email" value="">
                    </div>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save General Settings</button>
                </form>
                <hr style="margin: 30px 0;">

                <form id="logoFaviconForm" enctype="multipart/form-data">
                    <h3>Logo & Favicon</h3>
                    <div class="form-group">
                        <label>Current Logo (Light):</label>
                        <img id="current_logo_light" src="" alt="Current Logo" class="setting-img-preview">
                        <input type="file" name="logo_light" id="logo_light_input" accept="image/*">
                        <small>Upload a new light-themed logo (e.g., for dark dashboards).</small>
                    </div>
                    <div class="form-group">
                        <label>Current Favicon:</label>
                        <img id="current_favicon" src="" alt="Current Favicon" class="setting-img-preview favicon">
                        <input type="file" name="favicon" id="favicon_input" accept="image/x-icon,image/png">
                        <small>Upload a new favicon (.ico or .png).</small>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-upload"></i> Upload Images</button>
                </form>
                <hr style="margin: 30px 0;">

                <form id="heroSectionForm">
                    <h3>Hero Section Content</h3>
                    <div class="form-group">
                        <label for="hero_title">Hero Title:</label>
                        <input type="text" id="hero_title" name="hero_title" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="hero_subtitle">Hero Subtitle:</label>
                        <textarea id="hero_subtitle" name="hero_subtitle" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Save Hero Content</button>
                </form>
                <hr style="margin: 30px 0;">

                <h3>Pricing Plans</h3>
                <div id="pricingPlansList">
                    <!-- Pricing plans will be loaded and managed here -->
                    <p class="loader">Loading pricing plans...</p>
                </div>
                <button id="addPricingPlanBtn" class="btn-primary" style="margin-top: 15px;"><i class="fa-solid fa-plus"></i> Add New Plan</button>
                <hr style="margin: 30px 0;">

                <h3>Testimonials</h3>
                <div id="testimonialsList">
                    <!-- Testimonials will be loaded and managed here -->
                    <p class="loader">Loading testimonials...</p>
                </div>
                <button id="addTestimonialBtn" class="btn-primary" style="margin-top: 15px;"><i class="fa-solid fa-plus"></i> Add New Testimonial</button>
            </section>
        </main>
    </div>

    <!-- Pricing Plan Modal -->
    <div id="pricingModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <form id="pricingForm">
                <h3 id="pricingModalTitle">Add/Edit Pricing Plan</h3>
                <input type="hidden" id="planId" name="id">
                <div class="form-group">
                    <label for="plan_name">Plan Name:</label>
                    <input type="text" id="plan_name" name="plan_name" required>
                </div>
                <div class="form-group">
                    <label for="pricing_description">Description:</label>
                    <textarea id="pricing_description" name="description" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (e.g., $29):</label>
                    <input type="text" id="price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="frequency">Frequency (e.g., /mo, /year, Contact Us):</label>
                    <input type="text" id="frequency" name="frequency">
                </div>
                <div class="form-group">
                    <label for="features">Features (one per line):</label>
                    <textarea id="features" name="features" rows="5"></textarea>
                </div>
                <div class="form-group-inline">
                    <input type="checkbox" id="is_popular" name="is_popular" value="1">
                    <label for="is_popular">Mark as Popular</label>
                </div>
                <div class="form-group">
                    <label for="pricing_display_order">Display Order:</label>
                    <input type="number" id="pricing_display_order" name="display_order" value="10">
                </div>
                <button type="submit" class="btn-primary">Save Plan</button>
            </form>
        </div>
    </div>

    <!-- Testimonial Modal -->
    <div id="testimonialModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <form id="testimonialForm" enctype="multipart/form-data">
                <h3 id="testimonialModalTitle">Add/Edit Testimonial</h3>
                <input type="hidden" id="testimonialId" name="id">
                <div class="form-group text-center">
                    <label>Current Image:</label><br>
                    <img id="current_author_image" src="" alt="Author Image" class="setting-img-preview profile">
                    <input type="file" name="author_image" id="author_image_input" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="author_name">Author Name:</label>
                    <input type="text" id="author_name" name="author_name" required>
                </div>
                <div class="form-group">
                    <label for="author_title">Author Title/Designation:</label>
                    <input type="text" id="author_title" name="author_title">
                </div>
                <div class="form-group">
                    <label for="quote_text">Quote Text:</label>
                    <textarea id="quote_text" name="quote_text" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="testimonial_display_order">Display Order:</label>
                    <input type="number" id="testimonial_display_order" name="display_order" value="10">
                </div>
                <button type="submit" class="btn-primary">Save Testimonial</button>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/common_dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/website_settings.js"></script>
</body>
</html>