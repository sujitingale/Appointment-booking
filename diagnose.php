<!DOCTYPE html>
<html>
<head>
    <title>MySQL Connection Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #ccc; background: #f8f9fa; }
        .test-item.pass { border-left-color: #28a745; }
        .test-item.fail { border-left-color: #dc3545; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MySQL Connection Diagnostics</h1>
        
        <?php
        echo "<h2>1. PHP Extensions Check</h2>";
        
        // Check PDO
        if (extension_loaded('pdo')) {
            echo "<div class='test-item pass'><span class='success'>✓</span> PDO extension is loaded</div>";
        } else {
            echo "<div class='test-item fail'><span class='error'>✗</span> PDO extension is NOT loaded</div>";
        }
        
        // Check PDO MySQL
        if (extension_loaded('pdo_mysql')) {
            echo "<div class='test-item pass'><span class='success'>✓</span> PDO MySQL extension is loaded</div>";
        } else {
            echo "<div class='test-item fail'><span class='error'>✗</span> PDO MySQL extension is NOT loaded</div>";
        }
        
        // Check MySQLi
        if (extension_loaded('mysqli')) {
            echo "<div class='test-item pass'><span class='success'>✓</span> MySQLi extension is loaded</div>";
        } else {
            echo "<div class='test-item fail'><span class='error'>✗</span> MySQLi extension is NOT loaded</div>";
        }
        
        echo "<h2>2. MySQL Connection Tests</h2>";
        
        $hosts = ['localhost', '127.0.0.1'];
        $ports = [3306, 3307];
        $passwords = ['', 'root'];
        
        $success = false;
        
        foreach ($hosts as $host) {
            foreach ($ports as $port) {
                foreach ($passwords as $password) {
                    $password_display = empty($password) ? '(empty)' : $password;
                    
                    try {
                        $dsn = "mysql:host=$host;port=$port";
                        $pdo = new PDO($dsn, 'root', $password);
                        echo "<div class='test-item pass'>";
                        echo "<span class='success'>✓ SUCCESS!</span><br>";
                        echo "Connected with: <code>host=$host, port=$port, password=$password_display</code><br>";
                        echo "<strong>Use these settings in your database.php!</strong>";
                        echo "</div>";
                        $success = true;
                        
                        // Try to show databases
                        $stmt = $pdo->query("SHOW DATABASES");
                        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo "<div class='test-item'>";
                        echo "<strong>Available databases:</strong> " . implode(', ', $databases);
                        echo "</div>";
                        
                        break 3; // Exit all loops
                        
                    } catch (PDOException $e) {
                        // Silent fail, try next combination
                    }
                }
            }
        }
        
        if (!$success) {
            echo "<div class='test-item fail'>";
            echo "<span class='error'>✗ All connection attempts failed</span><br>";
            echo "Tried all combinations of:<br>";
            echo "- Hosts: localhost, 127.0.0.1<br>";
            echo "- Ports: 3306, 3307<br>";
            echo "- Passwords: (empty), root<br>";
            echo "</div>";
        }
        
        echo "<h2>3. Socket Connection Test (Alternative)</h2>";
        
        // Try socket connection
        if (function_exists('mysqli_connect')) {
            $socket_paths = [
                'C:/xampp/mysql/mysql.sock',
                '/tmp/mysql.sock',
                'localhost'
            ];
            
            foreach ($socket_paths as $socket) {
                $mysqli = @mysqli_connect($socket, 'root', '');
                if ($mysqli) {
                    echo "<div class='test-item pass'>";
                    echo "<span class='success'>✓ Socket connection successful!</span><br>";
                    echo "Connected via: <code>$socket</code>";
                    echo "</div>";
                    mysqli_close($mysqli);
                    break;
                }
            }
        }
        
        echo "<h2>4. System Information</h2>";
        echo "<div class='test-item'>";
        echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
        echo "<strong>Operating System:</strong> " . PHP_OS . "<br>";
        echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
        echo "</div>";
        
        echo "<h2>5. Recommendations</h2>";
        if (!$success) {
            echo "<div class='test-item fail'>";
            echo "<strong>⚠️ MySQL is not accepting connections. Try these steps:</strong><br><br>";
            echo "1. <strong>Restart MySQL in XAMPP Control Panel</strong><br>";
            echo "2. Check if MySQL is running on a different port<br>";
            echo "3. Check Windows Firewall settings<br>";
            echo "4. Look at MySQL error log: <code>C:\\xampp\\mysql\\data\\mysql_error.log</code><br>";
            echo "5. Try running XAMPP as Administrator<br>";
            echo "6. Check if another MySQL service is running (conflict)<br>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><a href="phpinfo.php" target="_blank">View Full PHP Info</a> | <a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>
