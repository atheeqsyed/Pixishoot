<?php
// Include the database connection file
require_once '../db_connection.php';

// Set Content-Type header for JSON response
header('Content-Type: application/json');

// Initialize the response structure
$response = ['success' => false];

// Enable error reporting for debugging (only during development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if the database connection is established
    if (!$conn) {
        throw new Exception('Database connection not established');
    }

    // Get the updated event data from the POST request
    $event_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $title = $_POST['title'] ?? null;
    $service = $_POST['service'] ?? null;
    $start_time = $_POST['startTime'] ?? null;
    $end_time = $_POST['endTime'] ?? null;
    $date = $_POST['date'] ?? null; // Ensure that date is received properly

    // Validate required fields
    if (!$event_id || !$title || !$service || !$start_time || !$end_time || !$date) {
        throw new Exception('Missing required fields');
    }

    // Prepare SQL query to update the event
    $sql = "UPDATE new_events SET title = ?, service = ?, start_time = ?, end_time = ?, date = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
    }

    // Bind parameters and execute the query
    $stmt->bind_param("sssssi", $title, $service, $start_time, $end_time, $date, $event_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Record updated successfully';
        } else {
            $response['message'] = 'No changes made to the record';
        }
    } else {
        throw new Exception('Failed to execute SQL statement: ' . $stmt->error);
    }
} catch (Exception $e) {
    // Handle exceptions and set the response message
    $response['message'] = $e->getMessage();
} finally {
    // Close statement and connection if they exist
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }

    // Output the response as JSON
    echo json_encode($response);
}
