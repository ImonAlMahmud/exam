<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../../includes/config.php';
require_once '../../includes/auth_check.php';
require_login('Admin');

// Set headers for download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="htec_exam_db_backup_' . date('Y-m-d_His') . '.sql"');
header('Pragma: no-cache');
header('Expires: 0');

$db_host = DB_HOST;
$db_user = DB_USERNAME;
$db_pass = DB_PASSWORD;
$db_name = DB_NAME;

// IMPORTANT: Clear output buffer before passthru to prevent "headers already sent" errors
if (ob_get_level()) {
    ob_end_clean();
}
// Turn off error reporting explicitly for outputting raw SQL
ini_set('display_errors', 0);
error_reporting(0);

// Use shell_exec to call mysqldump
// Important: mysqldump must be in your system's PATH, or provide the full path to it.
// Example for XAMPP on Windows: C:\xampp\mysql\bin\mysqldump
// Adjust this path according to your XAMPP installation.
$mysqldump_path = "C:\\xampp\\mysql\\bin\\mysqldump.exe"; // <--- CHANGE THIS IF YOUR XAMPP PATH IS DIFFERENT!
// For Linux/macOS, it might just be "mysqldump" or "/usr/bin/mysqldump"

$command = "{$mysqldump_path} -h{$db_host} -u{$db_user} ";
if (!empty($db_pass)) {
    $command .= "-p{$db_pass} ";
}
$command .= "{$db_name}";

// Execute the command and output the SQL directly to the browser for download
passthru($command, $return_var);

if ($return_var !== 0) {
    // If mysqldump failed, it might output to stderr, passthru sends it to stdout.
    // It's hard to capture errors with passthru, so a robust solution would use proc_open.
    // For simplicity, we just indicate a potential error.
    error_log("mysqldump failed with return code: {$return_var} for command: {$command}");
    echo "-- Backup failed or mysqldump not found. Error code: {$return_var}";
}

exit();
?>