<?php 
require_once 'includes/config.php'; 

// Define appName and logoPath using get_setting() directly with fallback defaults.
$appName = get_setting('app_name', "HTEC Exam System");
$logoPath = BASE_URL . 'uploads/website/' . get_setting('logo_light', 'logo-light.png');

// Fetch pricing plans
$pricing_stmt = $pdo->query("SELECT * FROM pricing_plans ORDER BY display_order ASC");
$pricing_plans = $pricing_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch testimonials
$testimonials_stmt = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC");
$testimonials = $testimonials_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName); ?> - The Ultimate Online Examination Platform</title>
    
    <!-- Dynamic Favicon -->
    <link rel="icon" href="<?php echo BASE_URL . 'uploads/website/' . get_setting('favicon', 'favicon.ico'); ?>" type="image/x-icon">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/landing-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-left">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                     <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($appName); ?>" style="height: 40px;">
                </a>
                <div class="nav-buttons">
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-login">Login</a>
                    <a href="<?php echo BASE_URL; ?>signup.php" class="btn btn-signup">Sign Up</a>
                </div>
            </div>
            <!-- Future nav links like "Features", "Pricing" can be added here -->
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo get_setting('hero_title', 'The Future of Online Examinations is Here'); ?></h1>
                <p><?php echo get_setting('hero_subtitle', 'A seamless, secure, and powerful platform for creating, scheduling, and managing exams. Perfect for educational institutions and corporate training.'); ?></p>
                <a href="#pricing" class="btn btn-cta">Get Started Today</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fa-solid fa-users"></i>
                    <h3>10,000+</h3>
                    <p>Active Students</p>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <h3>500+</h3>
                    <p>Mentors & Teachers</p>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-file-alt"></i>
                    <h3>25,000+</h3>
                    <p>Exams Conducted</p>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-star"></i>
                    <h3>4.9/5.0</h3>
                    <p>User Rating</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="section-title">Why Choose HTEC Exam System?</h2>
            <div class="features-grid">
                <div class="feature-card"><i class="fa-solid fa-users-gear"></i><h3>Role-Based Access</h3><p>Dedicated dashboards for Admins, Mentors, and Students.</p></div>
                <div class="feature-card"><i class="fa-solid fa-file-excel"></i><h3>Easy Question Import</h3><p>Quickly import questions using a simple Excel file format.</p></div>
                <div class="feature-card"><i class="fa-solid fa-chart-line"></i><h3>Instant Results & Reports</h3><p>Generate leaderboards and download answer sheets in PDF & Excel.</p></div>
                <div class="feature-card"><i class="fa-solid fa-clock"></i><h3>Automated Scheduling</h3><p>Exams with countdown timers and automatic status management.</p></div>
                <div class="feature-card"><i class="fa-solid fa-shield-halved"></i><h3>Secure & Reliable</h3><p>Built with modern security practices like PDO and prepared statements.</p></div>
                <div class="feature-card"><i class="fa-solid fa-mobile-screen-button"></i><h3>Fully Responsive</h3><p>Access the system anytime, anywhere, from any device.</p></div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section">
        <div class="container">
            <h2 class="section-title">What Our Users Say</h2>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <p class="quote">"<?php echo htmlspecialchars($testimonial['quote_text']); ?>"</p>
                    <div class="testimonial-author">
                        <img src="<?php echo BASE_URL . 'uploads/website/' . htmlspecialchars($testimonial['author_image_url']); ?>" alt="<?php echo htmlspecialchars($testimonial['author_name']); ?>">
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($testimonial['author_name']); ?></h4>
                            <p><?php echo htmlspecialchars($testimonial['author_title']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Pricing Section -->
    <section id="pricing" class="section">
        <div class="container">
            <h2 class="section-title">Flexible Pricing Plans</h2>
            <div class="pricing-grid">
                <?php foreach ($pricing_plans as $plan): ?>
                <div class="pricing-card <?php if ($plan['is_popular']) echo 'popular'; ?>">
                    <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                    <p><?php echo htmlspecialchars($plan['description']); ?></p>
                    <div class="price"><?php echo htmlspecialchars($plan['price']); ?><span><?php echo htmlspecialchars($plan['frequency']); ?></span></div>
                    <ul>
                        <?php 
                            $features = explode("\n", trim($plan['features']));
                            foreach ($features as $feature) {
                                echo '<li>' . htmlspecialchars(trim($feature)) . '</li>';
                            }
                        ?>
                    </ul>
                    <a href="<?php echo BASE_URL; ?>signup.php" class="btn">Choose Plan</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>About <?php echo htmlspecialchars($appName); ?></h4>
                    <p>The ultimate online examination platform designed to be seamless, secure, and powerful for educational institutions and corporate training.</p>
                </div>
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <a href="#features">Features</a>
                    <a href="#testimonials">Testimonials</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#">Privacy Policy</a>
                </div>
                <div class="footer-column">
                    <h4>Contact Us</h4>
                    <p><i class="fa-solid fa-map-marker-alt"></i> Dhaka, Bangladesh</p>
                    <p><i class="fa-solid fa-envelope"></i> <a href="mailto:<?php echo get_setting('support_email', 'support@htec-exam.com'); ?>"><?php echo get_setting('support_email', 'support@htec-exam.com'); ?></a></p>
                    <p><i class="fa-solid fa-phone"></i> +880 1234 567890</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>Developer: <?php echo get_setting('developer_name', 'Mahmudur Rahman Imon'); ?> | Support: <a href="mailto:<?php echo get_setting('support_email', 'imon@htec-edu.com'); ?>"><?php echo get_setting('support_email', 'imon@htec-edu.com'); ?></a></p>
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>