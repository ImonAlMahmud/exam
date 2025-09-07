<?php
// Database Configuration
define('DB_HOST', 'shareddb-p.hosting.stackcp.net');
define('DB_USERNAME', 'wordpress-313135f2ca'); // আপনার ডাটাবেস ইউজারনেম দিন
define('DB_PASSWORD', '100%Imon?');     // আপনার ডাটাবেস পাসওয়ার্ড দিন
define('DB_NAME', 'wordpress-313135f2ca'); // আপনার ডাটাবেসের নাম দিন

// Set DSN (Data Source Name)
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

// Set PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turn on errors in the form of exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Make the default fetch be an associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Turn off emulation mode for real prepared statements
];

// Create a new PDO instance
try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error
    die("Database connection failed: " . $e->getMessage());
}
?>