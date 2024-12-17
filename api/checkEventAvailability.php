<?php
// Include database connection file
require_once '../db_connection.php';

// Check if required POST data is provided
if (isset($_POST['date'], $_POST['startTime'], $_POST['endTime'])) {
    // Get data from the POST request
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];

    // SQL query to check for overlapping events
    $sql = "SELECT * 
            FROM new_events 
            WHERE date = ? AND (
                (start_time < ? AND end_time > ?) OR
                (start_time < ? AND end_time > ?)
            )";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind the parameters to the SQL query
        $stmt->bind_param('sssss', $date, $startTime, $startTime, $endTime, $endTime);

        // Execute the query
        $stmt->execute();

        // Get the result set
        $result = $stmt->get_result();

        // Check for conflicting events
        if ($result->num_rows > 0) {
            // Return response indicating unavailability
            echo json_encode(['success' => false, 'message' => 'The selected time slot is already booked.']);
        } else {
            // Return response indicating availability
            echo json_encode(['success' => true]);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Return response in case of SQL preparation failure
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL query.']);
    }
} else {
    // Return response if required POST data is missing
    echo json_encode(['success' => false, 'message' => 'Invalid input. Date, start time, and end time are required.']);
}

// Close the database connection
$conn->close();
?>
