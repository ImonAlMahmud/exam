<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HTEC Exam System</title>
    
    <!-- General Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="glass-container">
        <form id="signup-form">
            <h1>Create Account</h1>

            <!-- Message placeholder -->
            <div id="message-container"></div>

            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <div class="input-group">
                <i class="fa-solid fa-users-viewfinder"></i>
                <select name="role" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="Student">Student</option>
                    <option value="Mentor">Mentor</option>
                </select>
            </div>

            <button type="submit" class="btn" id="signup-btn">Sign Up</button>

            <div class="helper-text">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>

    <!-- We will add JavaScript here later -->
    <script>
        // JavaScript for API communication will go here
    </script>
</body>
</html>