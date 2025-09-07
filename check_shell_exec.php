<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
echo "<h1>Shell_exec Test</h1>";
if (function_exists('shell_exec')) {
    echo "<p style='color:green;'>shell_exec is ENABLED.</p>";
    $output = shell_exec('echo "Hello from shell_exec!"');
    echo "<p>Output: " . htmlspecialchars($output) . "</p>";

    echo "<h2>Python Path Test</h2>";
    echo "<p>Attempting to find Python executables...</p>";
    
    $possible_python_paths = [
        'python3',
        'python',
        '/usr/bin/python3',
        '/usr/bin/python',
        '/usr/local/bin/python3',
        '/usr/local/bin/python',
        // Add specific paths if your host provided them, e.g., '/opt/alt/python39/bin/python3.9'
    ];
    $found_python = false;

    foreach ($possible_python_paths as $py_exec) {
        $command = "{$py_exec} --version 2>&1";
        $version_output = shell_exec($command);
        if ($version_output && strpos($version_output, 'Python') !== false) {
            echo "<p style='color:green;'>Found Python: <strong>{$py_exec}</strong> (Version: " . htmlspecialchars($version_output) . ")</p>";
            $found_python = true;

            // Test if OpenCV is installed for this Python executable
            echo "<p>Testing OpenCV for {$py_exec}...</p>";
            $opencv_command = "{$py_exec} -c \"import cv2; print('OpenCV FOUND for this Python!')\" 2>&1";
            $opencv_output = shell_exec($opencv_command);
            echo "<p>OpenCV test output: " . nl2br(htmlspecialchars($opencv_output)) . "</p>";

            // Test if this executable can run our script (permission test)
            echo "<p>Testing script execution permission for {$py_exec}...</p>";
            $test_script_path = realpath(dirname(__FILE__) . '/scripts/test_python_script.py'); // Create a dummy test script
            if (!file_exists($test_script_path)) {
                file_put_contents($test_script_path, "import sys; print('Test script ran successfully!', file=sys.stdout); sys.exit(0)");
            }
            $script_test_command = "{$py_exec} {$test_script_path} 2>&1";
            $script_test_output = shell_exec($script_test_command);
            echo "<p>Script execution test output: " . nl2br(htmlspecialchars($script_test_output)) . "</p>";
            
            // Clean up dummy script
            if (file_exists($test_script_path)) {
                unlink($test_script_path);
            }

            break; // Stop after finding the first working Python
        }
    }

    if (!$found_python) {
        echo "<p style='color:red;'><strong>No Python executable found in common paths or PATH.</strong> You need to install Python or specify its full path.</p>";
    }

} else {
    echo "<p style='color:red;'>shell_exec is DISABLED on this server. OMR functionality will not work.</p>";
}
echo "--- * আপনার ব্রাউজারে 'http://exam.htec-edu.com/check_shell_exec.php' ভিজিট করুন * ---";