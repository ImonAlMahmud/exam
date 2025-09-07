<?php
/**
 * HTEC Exam System - Central Configuration File
 */

// Set default timezone for all date/time functions in PHP to 'Asia/Dhaka'
date_default_timezone_set('Asia/Dhaka'); 

// Enable error reporting at the very top for debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ROOT_PATH: For server-side file includes (like require_once).
// This determines the absolute physical path to your project's root on the server.
// For shared hosting, it might be something like /home/username/public_html/exam/
// dirname(__DIR__) gets the parent directory of 'includes'.
// We need to adjust this to point to the actual 'exam' root where index.php resides.
// If 'config.php' is in 'exam/includes/', then 'dirname(__DIR__)' points to 'exam/'.
define('ROOT_PATH', dirname(__DIR__) . '/');


// 3. BASE_URL: For client-side links (CSS, JS, images, page links).
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// IMPORTANT: This needs to be adjusted based on your exact hosting setup.
// If your project is directly in the public_html (root of domain), use:
// define('BASE_URL', $protocol . $host . '/'); 

// If your project is in a subfolder like 'exam' within public_html (http://yourdomain.com/exam/):
// define('BASE_URL', $protocol . $host . '/exam/');

// If your project is accessed via a subdomain like exam.htec-edu.com and 'exam' is the root folder:
define('BASE_URL', $protocol . $host . '/'); // <--- THIS IS THE CORRECT SETTING FOR exam.htec-edu.com

require_once(ROOT_PATH . 'includes/db.php');

$GLOBALS['app_settings'] = [];
$GLOBALS['settings_loaded_attempted'] = false; 

function _load_app_settings_from_db() {
    global $pdo;
    global $settings_loaded_attempted;

    if (isset($pdo) && $pdo instanceof PDO && !$settings_loaded_attempted) {
        $settings_loaded_attempted = true; 

        try {
            $table_exists_stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
            
            if ($table_exists_stmt && $table_exists_stmt->rowCount() > 0) {
                $settings_stmt = $pdo->query("SELECT setting_name, setting_value FROM settings");
                $GLOBALS['app_settings'] = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        } catch (PDOException $e) {
            error_log("Failed to load settings from DB in config.php: " . $e->getMessage());
        }
    }
}

function get_setting($key, $default = '') {
    if (empty($GLOBALS['app_settings']) && !$GLOBALS['settings_loaded_attempted']) {
        _load_app_settings_from_db();
    }
    return htmlspecialchars($GLOBALS['app_settings'][$key] ?? $default);
}

_load_app_settings_from_db();