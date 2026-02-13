<?php
// Database connection parameters
$host = 'localhost';          // Your server name, often "localhost"
$db   = 'security_demo'; // The database you want to connect to
$user = 'normaluser';   // Your MySQL username
$pass = 'password';   // Your MySQL password
$charset = 'utf8mb4';         // Character set for broad compatibility

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements for security
];

try {
    // Attempt to establish the connection
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // If the connection succeeds, display success message
    echo "✅ Connected to the database successfully using PDO!";
    
} catch (\PDOException $e) {
    // If connection fails, display error and stop script execution
    // The \PDOException class is used to catch specific PDO errors
    die("❌ Connection failed: " . $e->getMessage());
}

// In PDO, the connection is automatically closed when the $pdo object goes out of scope (at the end of the script).

?>