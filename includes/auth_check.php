<?php
// includes/auth_check.php
// This file checks if a user is authenticated and has the required role.

// It is crucial that config.php is included BEFORE this file
// as config.php starts the session and defines BASE_URL.

function require_login($required_role = null) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
    
    // If a specific role is required, check it
    if ($required_role !== null) {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $required_role) {
            // User is logged in but doesn't have the required role.
            // Redirect to a dashboard based on their current role or to login.
            if (isset($_SESSION['user_role'])) {
                switch ($_SESSION['user_role']) {
                    case 'Admin':
                        header("Location: " . BASE_URL . "admin/dashboard.php?error=unauthorized_access");
                        break;
                    case 'Mentor':
                        header("Location: " . BASE_URL . "mentor/mentor-dashboard.php?error=unauthorized_access");
                        break;
                    case 'Student':
                        header("Location: " . BASE_URL . "student/student-dashboard.php?error=unauthorized_access");
                        break;
                    default:
                        header("Location: " . BASE_URL . "login.php?error=unauthorized");
                        break;
                }
            } else {
                header("Location: " . BASE_URL . "login.php?error=unauthorized");
            }
            exit();
        }
    }
}