<?php
// Include the database connection file
include '../db_connection.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set the response header to JSON
header('Content-Type: application/json');

// Retrieve and validate query parameters
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$service = !empty($_GET['service']) ? $_GET['service'] : null;

// Check if start and end dates are provided
if (!$start || !$end) {
    http_response_code(400);
    echo json_encode(['error' => 'Start and end dates are required']);
    exit;
}

try {
    // Prepare the base SQL query
    $sql = "SELECT id, title, service, date, start_time, end_time FROM new_events WHERE date BETWEEN ? AND ?";
    $params = [$start, $end];

    // Append service filter to the query if provided
    if ($service) {
        $sql .= " AND service = ?";
        $params[] = $service;
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
    }

    // Bind parameters dynamically
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute SQL statement: ' . $stmt->error);
    }

    // Get the result set
    $result = $stmt->get_result();

    // Fetch events and format them for JSON response
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'service' => $row['service'],
            'start' => $row['date'] . 'T' . $row['start_time'],
            'end' => $row['date'] . 'T' . $row['end_time'],
        ];
    }

    // Return the events as JSON
    echo json_encode($events);

} catch (Exception $e) {
    // Handle exceptions and return error response
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Close the statement and database connection
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
