<?php
$servername = "localhost"; // Your server name, often "localhost"
$username = "normaluser"; // Your MySQL username
$password = "password"; // Your MySQL password
$dbname = "security_demo";   // The database you want to connect to

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, display error and stop script execution
    die("❌ Connection failed: " . $conn->connect_error);
}

// If connection succeeds, display success message
echo "✅ Connected to the database successfully!";

// Close the connection
$conn->close();

?>