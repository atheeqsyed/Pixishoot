<?php
// db_connection.php
$servername = "localhost";
$username = "root"; // Use your MySQL username
$password = "P@55w0rd"; // Use your MySQL password
$dbname = "calendar_app"; // Use your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Instead of echoing a message, return an error in JSON format
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// No need to echo "Connected successfully", just return the connection
?>
