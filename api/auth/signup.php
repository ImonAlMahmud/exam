<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (for development)
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/config.php';

// Decode the incoming JSON data
$data = json_decode(file_get_contents("php://input"));

// 1. Validate Input
if (
    !isset($data->name) || empty(trim($data->name)) ||
    !isset($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL) ||
    !isset($data->password) || empty($data->password) ||
    !isset($data->role) || !in_array($data->role, ['Student', 'Mentor'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please fill all fields correctly.']);
    exit();
}

// Sanitize data
$name = htmlspecialchars(strip_tags(trim($data->name)));
$email = htmlspecialchars(strip_tags(trim($data->email)));
$password = $data->password;
$role = $data->role;

// 2. Check if email already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// 3. Hash the password for security
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// 4. Insert the new user into the database
try {
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$name, $email, $hashed_password, $role])) {
        http_response_code(201); // Created
        echo json_encode(['status' => 'success', 'message' => 'Account created successfully! You can now login.']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Failed to create account. Please try again.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signup-form');
    const messageContainer = document.getElementById('message-container');
    const signupBtn = document.getElementById('signup-btn');

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission (page reload)

        // Clear previous messages
        messageContainer.innerHTML = '';
        signupBtn.disabled = true;
        signupBtn.textContent = 'Processing...';

        // Get form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Send data to the API endpoint
        fetch('api/auth/signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            // Display the message from the server
            let messageClass = result.status === 'success' ? 'success' : 'error';
            messageContainer.innerHTML = `<div class="message ${messageClass}">${result.message}</div>`;
            
            if (result.status === 'success') {
                form.reset(); // Clear the form on success
                // Optionally, redirect to login page after a delay
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageContainer.innerHTML = `<div class="message error">An unexpected error occurred. Please try again.</div>`;
        })
        .finally(() => {
            // Re-enable the button
            signupBtn.disabled = false;
            signupBtn.textContent = 'Sign Up';
        });
    });
});
</script>

?>