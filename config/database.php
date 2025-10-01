<?php
// Start session
session_start();

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Empty password for default XAMPP
define('DB_NAME', 'appointment_system');

// Try multiple connection methods
$connection_attempts = [
    // Method 1: localhost without port
    "mysql:host=localhost;dbname=" . DB_NAME,
    // Method 2: 127.0.0.1 without port
    "mysql:host=127.0.0.1;dbname=" . DB_NAME,
    // Method 3: localhost with port 3306
    "mysql:host=localhost;port=3306;dbname=" . DB_NAME,
    // Method 4: 127.0.0.1 with port 3306
    "mysql:host=127.0.0.1;port=3306;dbname=" . DB_NAME,
];

$pdo = null;
$last_error = '';

foreach ($connection_attempts as $dsn) {
    try {
        // First try to connect without database
        $dsn_without_db = str_replace(";dbname=" . DB_NAME, "", $dsn);
        $temp_pdo = new PDO($dsn_without_db, DB_USERNAME, DB_PASSWORD);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        
        // Now connect with database
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Success! Break the loop
        break;
        
    } catch(PDOException $e) {
        $last_error = $e->getMessage();
        continue; // Try next method
    }
}

// If all attempts failed
if ($pdo === null) {
    $error_msg = "<div style='font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 50px auto; border: 2px solid #dc3545; border-radius: 8px; background: #fff;'>";
    $error_msg .= "<h2 style='color: #dc3545;'>❌ Database Connection Failed</h2>";
    $error_msg .= "<p style='background: #f8d7da; padding: 10px; border-radius: 4px;'><strong>Error:</strong> " . htmlspecialchars($last_error) . "</p>";
    $error_msg .= "<hr>";
    $error_msg .= "<h3>🔧 Quick Fix Steps:</h3>";
    $error_msg .= "<ol style='line-height: 1.8;'>";
    $error_msg .= "<li><strong>Open XAMPP Control Panel</strong></li>";
    $error_msg .= "<li><strong>Stop MySQL</strong> (click Stop button)</li>";
    $error_msg .= "<li><strong>Wait 5 seconds</strong></li>";
    $error_msg .= "<li><strong>Start MySQL again</strong> (click Start button)</li>";
    $error_msg .= "<li><strong>Refresh this page</strong></li>";
    $error_msg .= "</ol>";
    $error_msg .= "<hr>";
    $error_msg .= "<h3>📋 Alternative Solutions:</h3>";
    $error_msg .= "<ul style='line-height: 1.8;'>";
    $error_msg .= "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank' style='color: #007bff;'>http://localhost/phpmyadmin</a> (if this works, the issue is with PHP configuration)</li>";
    $error_msg .= "<li>Check if MySQL password is set - try changing DB_PASSWORD to 'root' in config/database.php line 7</li>";
    $error_msg .= "<li>Make sure no firewall is blocking MySQL</li>";
    $error_msg .= "<li>Check XAMPP error logs in: C:\\xampp\\mysql\\data\\mysql_error.log</li>";
    $error_msg .= "</ul>";
    $error_msg .= "<hr>";
    $error_msg .= "<div style='background: #d1ecf1; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
    $error_msg .= "<strong>💡 Most Common Fix:</strong> Restart MySQL service in XAMPP Control Panel";
    $error_msg .= "</div>";
    $error_msg .= "</div>";
    die($error_msg);
}
?>
