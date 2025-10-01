<?php
// Database connection test script
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

if (isset($pdo)) {
    echo "<div style='color: green; font-weight: bold;'>✅ Database connection successful!</div>";

    try {
        // Test if we can execute a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();

        if ($result && $result['test'] == 1) {
            echo "<div style='color: green; font-weight: bold;'>✅ Query execution test passed!</div>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ Query execution test failed!</div>";
        }

        // Check if appointment_system database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE 'appointment_system'");
        $db_exists = $stmt->fetch();

        if ($db_exists) {
            echo "<div style='color: green; font-weight: bold;'>✅ Database 'appointment_system' exists!</div>";

            // Check if tables exist
            $pdo->exec("USE appointment_system");
            $tables = ['users', 'doctor_profiles', 'appointments', 'notifications', 'doctor_availability'];

            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->fetch()) {
                    echo "<div style='color: green;'>✅ Table '$table' exists!</div>";
                } else {
                    echo "<div style='color: orange;'>⚠️  Table '$table' missing - needs to be created</div>";
                }
            }
        } else {
            echo "<div style='color: orange; font-weight: bold;'>⚠️  Database 'appointment_system' not found - needs to be created</div>";
        }

    } catch (PDOException $e) {
        echo "<div style='color: red; font-weight: bold;'>❌ Query execution failed: " . $e->getMessage() . "</div>";
    }

} else {
    echo "<div style='color: red; font-weight: bold;'>❌ Database connection failed!</div>";
}

echo "<hr>";
echo "<p><a href='setup-database.php'>Create Database & Import Schema</a> | <a href='index.php'>Go to Homepage</a></p>";
?>
