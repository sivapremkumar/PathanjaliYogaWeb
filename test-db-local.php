<?php
$mysqli = new mysqli('auth-db982.hstgr.io', 'u625769188_pathanjalai', 'Pathanjalipv@27', 'u625769188_pathanjalai');

if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "Connection successful!\n";

// Try to query
$result = $mysqli->query('SELECT username FROM admin_users LIMIT 5');
if ($result) {
    echo "Found " . $result->num_rows . " admin users:\n";
    while($row = $result->fetch_assoc()) {
        echo "  - " . $row['username'] . "\n";
    }
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}

$mysqli->close();
