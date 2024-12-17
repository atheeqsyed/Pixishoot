<?php
// Include the database connection file
include_once '../db_connection.php'; // Ensure the path is correct

// Set content type to JSON
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false];

try {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize the event ID
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $eventId = (int) $_POST['id']; // Explicit cast to integer for safety

            // SQL query to delete the event by ID
            $sql = "DELETE FROM new_events WHERE id = ?";

            // Prepare the SQL statement
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }

            // Bind the event ID parameter
            $stmt->bind_param("i", $eventId);

            // Execute the SQL statement
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    // Event successfully deleted
                    $response['success'] = true;
                    $response['message'] = 'Event deleted successfully.';
                } else {
                    // Event not found or already deleted
                    $response['message'] = 'Event not found or already deleted.';
                }
            } else {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }

            // Close the statement
            $stmt->close();
        } else {
            // Invalid or missing event ID
            $response['message'] = 'Invalid or missing event ID.';
        }
    } else {
        // Invalid request method
        $response['message'] = 'Invalid request method.';
    }
} catch (Exception $e) {
    // Catch and handle exceptions
    $response['message'] = $e->getMessage();
} finally {
    // Output the response in JSON format
    echo json_encode($response);

    // Close the database connection if it exists
    if (isset($conn)) {
        $conn->close();
    }
}
?>
