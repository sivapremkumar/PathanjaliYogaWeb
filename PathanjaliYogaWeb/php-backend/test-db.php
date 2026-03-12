<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

echo "Testing Database Connection\n";
echo "==========================\n\n";

echo "Settings:\n";
echo "  Host: " . getenv('DB_HOST') . "\n";
echo "  Database: " . getenv('DB_DATABASE') . "\n";
echo "  Username: " . getenv('DB_USERNAME') . "\n";
echo "  Password: " . (strlen(getenv('DB_PASSWORD')) > 0 ? "***" : "NOT SET") . "\n\n";

echo "Connection Test:\n";
try {
    $conn = new mysqli(
        getenv('DB_HOST'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        getenv('DB_DATABASE')
    );
    
    if ($conn->connect_error) {
        echo "  FAILED: " . $conn->connect_error . "\n";
    } else {
        echo "  SUCCESS: Connected\n";
        
        // Check tables
        $result = $conn->query("SHOW TABLES");
        echo "\nTables in database:\n";
        if ($result->num_rows > 0) {
            while($row = $result->fetch_row()) {
                echo "  - " . $row[0] . "\n";
            }
        } else {
            echo "  (No tables found)\n";
        }
        
        // Check admin_users table
        echo "\nAdmin users:\n";
        $result = $conn->query("SELECT username, password_hash FROM admin_users LIMIT 5");
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "  - Username: " . htmlspecialchars($row['username']) . "\n";
            }
        } else {
            echo "  (no admin_users table or no records)\n";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "  EXCEPTION: " . $e->getMessage() . "\n";
}
