<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Loaded Extensions:<br>";
print_r(get_loaded_extensions());

echo "<h2>SQLite Check</h2>";
if (extension_loaded('sqlite3')) {
    echo "SQLite3 extension is loaded<br>";
} else {
    echo "SQLite3 extension is NOT loaded<br>";
}

echo "<h2>Directory Permissions</h2>";
$current_dir = __DIR__;
echo "Current Directory: " . $current_dir . "<br>";
echo "Permissions: " . substr(sprintf('%o', fileperms($current_dir)), -4) . "<br>";

$db_file = $current_dir . '/semitone.db';
if (file_exists($db_file)) {
    echo "Database file exists<br>";
    echo "Database file permissions: " . substr(sprintf('%o', fileperms($db_file)), -4) . "<br>";
    echo "Database file owner: " . posix_getpwuid(fileowner($db_file))['name'] . "<br>";
} else {
    echo "Database file does not exist<br>";
}

echo "<h2>Web Server User</h2>";
echo "Running as user: " . posix_getpwuid(posix_geteuid())['name'] . "<br>";

// Try to connect to database
echo "<h2>Database Connection Test</h2>";
try {
    $db = new SQLite3('semitone.db');
    echo "Successfully connected to database<br>";
    
    // Test query
    $result = $db->query('SELECT COUNT(*) as count FROM users');
    $row = $result->fetchArray();
    echo "Number of users in database: " . $row['count'] . "<br>";
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Check session directory
echo "<h2>Session Directory</h2>";
echo "Session save path: " . session_save_path() . "<br>";
if (is_writable(session_save_path())) {
    echo "Session directory is writable<br>";
} else {
    echo "Session directory is NOT writable<br>";
}
?>
