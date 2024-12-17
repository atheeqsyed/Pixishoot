<?php
// Include database connection file
require_once '../db_connection.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Initialize response array
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and fetch POST input
        $title = trim($_POST['title'] ?? '');
        $service = trim($_POST['service'] ?? '');
        $date = $_POST['date'] ?? '';
        $startTime = $_POST['startTime'] ?? '';
        $duration = intval($_POST['duration'] ?? 0);

        // Calculate end time
        $endTime = date('H:i:s', strtotime("$startTime + $duration hours"));

        // Input validation
        if (empty($title) || empty($service) || empty($date) || empty($startTime) || $duration <= 0) {
            throw new Exception('All fields are required, and duration must be greater than 0.');
        }

        // Validate active hours
        $activeStart = '09:00:00';
        $activeEnd = '18:00:00';
        if ($startTime < $activeStart || $endTime > $activeEnd) {
            throw new Exception('Event timing must be within active hours (09:00 AM - 06:00 PM).');
        }

        // Validate event date is not in the past
        $currentDate = date('Y-m-d');
        if ($date < $currentDate) {
            throw new Exception('Events cannot be created on past dates.');
        }

        // Restrict event creation on Sundays
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek == 0) {
            throw new Exception('Events cannot be created on Sundays.');
        }

        // Check for conflicting events
        $overlapQuery = "SELECT COUNT(*) as count 
                         FROM new_events 
                         WHERE date = ? 
                         AND (
                             (? >= start_time AND ? < end_time) OR 
                             (? > start_time AND ? <= end_time)
                         )";
        $stmt = $conn->prepare($overlapQuery);
        if (!$stmt) {
            throw new Exception('Failed to prepare the overlap query.');
        }

        $stmt->bind_param('sssss', $date, $startTime, $startTime, $endTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            throw new Exception('Conflict detected: Another event is scheduled for this time.');
        }

        // Insert new event into database
        $insertQuery = "INSERT INTO new_events (title, service, date, start_time, end_time) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        if (!$stmt) {
            throw new Exception('Failed to prepare the insert query.');
        }

        $stmt->bind_param('sssss', $title, $service, $date, $startTime, $endTime);

        if (!$stmt->execute()) {
            throw new Exception('Database error: Failed to create the event.');
        }

        // Event creation successful
        $response['success'] = true;
        $response['message'] = 'Event created successfully.';
    } catch (Exception $e) {
        // Catch and handle any exceptions
        $response['message'] = $e->getMessage();
    } finally {
        // Send response and close resources
        echo json_encode($response);
        $conn->close();
    }
} else {
    // Handle invalid request methods
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
