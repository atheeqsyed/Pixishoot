<?php
// Include the database connection file
require_once '../db_connection.php';

// Enable error reporting for debugging (only during development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set Content-Type header for JSON response
header('Content-Type: application/json');

// Check if the database connection is established
if (!$conn) {
    echo json_encode(['error' => 'Database connection not established']);
    exit;
}

// Get the updated event data from the POST request
$event_id = isset($_POST['id']) ? $_POST['id'] : null;
$title = isset($_POST['title']) ? $_POST['title'] : null;
$service = isset($_POST['service']) ? $_POST['service'] : null;
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
$duration = isset($_POST['duration']) ? $_POST['duration'] : null;

if (!$event_id || !$title || !$service || !$start_time || !$duration) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Prepare SQL query to update the event
$sql = "UPDATE new_events SET title = ?, service = ?, start_time = ?, duration = ? WHERE id = ?";

// Prepare and execute the SQL statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare SQL statement']);
    exit;
}

$stmt->bind_param("sssii", $title, $service, $start_time, $duration, $event_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => 'Record updated successfully']);
} else {
    echo json_encode(['error' => 'Failed to update record']);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
