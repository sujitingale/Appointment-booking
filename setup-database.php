<?php
require_once 'config/database.php';

echo "<h2>Database Setup</h2>";

if (!isset($pdo)) {
    echo "<div style='color: red; font-weight: bold;'>❌ Cannot connect to database. Please check your configuration.</div>";
    echo "<p><a href='test-connection.php'>Back to Connection Test</a></p>";
    exit();
}

echo "<div style='background: #f0f8ff; padding: 15px; margin: 20px 0; border-left: 4px solid #007cba;'>";
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Make sure XAMPP/WAMP is running with MySQL service active</li>";
echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
echo "<li>Create a database named 'appointment_system'</li>";
echo "<li>Import the schema.sql file from your database folder</li>";
echo "</ol>";
echo "</div>";

try {
    // Try to create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS appointment_system");
    echo "<div style='color: green; font-weight: bold;'>✅ Database 'appointment_system' created successfully!</div>";

    // Select the database
    $pdo->exec("USE appointment_system");

    // Read and execute schema.sql
    $schema_file = 'database/schema.sql';
    if (file_exists($schema_file)) {
        $schema = file_get_contents($schema_file);

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));

        $success_count = 0;
        $error_count = 0;

        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip comments and empty statements
            }

            try {
                $pdo->exec($statement);
                $success_count++;
            } catch (PDOException $e) {
                echo "<div style='color: orange;'>⚠️  Warning for statement: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "</div>";
                $error_count++;
            }
        }

        echo "<div style='color: green; font-weight: bold;'>✅ Schema imported successfully! ($success_count statements executed)</div>";
        if ($error_count > 0) {
            echo "<div style='color: orange;'>⚠️  $error_count statements had warnings (usually OK for existing tables)</div>";
        }

        // Check if default admin user exists
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $admin_exists = $stmt->fetch()['count'];

        if ($admin_exists > 0) {
            echo "<div style='color: green;'>✅ Default admin user exists!</div>";
        } else {
            echo "<div style='color: red;'>❌ Default admin user not found!</div>";
        }

    } else {
        echo "<div style='color: red;'>❌ Schema file not found: $schema_file</div>";
    }

} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Database setup failed: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='test-connection.php' class='btn'>Test Connection</a> ";
echo "<a href='index.php' class='btn'>Go to Homepage</a> ";
echo "<a href='login.php' class='btn'>Login Page</a>";
echo "</div>";

echo "
<style>
.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}
.btn:hover {
    background: #005a87;
}
</style>
";
?>
