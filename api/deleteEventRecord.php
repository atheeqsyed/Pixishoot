<?php
// Include the database connection file
require_once '../db_connection.php';

// Enable error reporting for debugging (only in development; disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set Content-Type header for JSON response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false];

try {
    // Check if the database connection is established
    if (!$conn) {
        throw new Exception('Database connection not established');
    }

    // Validate and sanitize the event ID from the POST request
    $event_id = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if (!$event_id) {
        throw new Exception('Event ID not provided');
    }

    // SQL query to delete the event
    $sql = "DELETE FROM new_events WHERE id = ?";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
    }

    // Bind the event ID as an integer parameter
    $stmt->bind_param("i", $event_id);

    // Execute the SQL query
    $stmt->execute();

    // Check if the deletion was successful
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Record deleted successfully.';
    } else {
        if ($stmt->error) {
            throw new Exception('Error during deletion: ' . $stmt->error);
        } else {
            $response['message'] = 'No event found with the provided ID.';
        }
    }
} catch (Exception $e) {
    // Catch and handle exceptions
    $response['error'] = $e->getMessage();
} finally {
    // Output the JSON response
    echo json_encode($response);

    // Close the statement and database connection if they exist
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
