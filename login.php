<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HTEC Exam System</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="glass-container">
        <form id="login-form">
            <h1>HTEC Login</h1>

            <!-- Message placeholder -->
            <div id="message-container"></div>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="btn" id="login-btn">Login</button>

            <div class="helper-text">
                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            </div>
        </form>
    </div>

    <!-- JavaScript for API communication -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('login-form');
        const messageContainer = document.getElementById('message-container');
        const loginBtn = document.getElementById('login-btn');

        form.addEventListener('submit', function(e) {
            e.preventDefault(); // This is crucial to prevent the default GET request submission

            messageContainer.innerHTML = '';
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Use the BASE_URL constant defined in config.php for the fetch URL
            const baseUrl = '<?php echo BASE_URL; ?>';
            
            fetch(baseUrl + 'api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    // If response is not OK (e.g., 401, 400), get the JSON error message
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(result => {
                messageContainer.innerHTML = `<div class="message ${result.status === 'success' ? 'success' : 'error'}">${result.message}</div>`;
                
                if (result.status === 'success') {
                    // Redirect to the correct dashboard based on role
                    setTimeout(() => {
                        switch (result.role) {
                            case 'Admin':
                                window.location.href = baseUrl + 'admin/dashboard.php';
                                break;
                            case 'Mentor':
                                window.location.href = baseUrl + 'mentor/mentor-dashboard.php';
                                break;
                            case 'Student':
                                window.location.href = baseUrl + 'student/student-dashboard.php';
                                break;
                            default:
                                window.location.href = baseUrl + 'login.php'; // Fallback
                        }
                    }, 1500); // Wait 1.5 seconds before redirecting
                } else {
                    // Re-enable the button only on failure
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Login';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Display error message from server or a generic one
                const errorMessage = error.message || 'An unexpected error occurred. Please try again.';
                messageContainer.innerHTML = `<div class="message error">${errorMessage}</div>`;
                
                // Re-enable the button on any error
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            });
        });
    });
    </script>
</body>
</html>