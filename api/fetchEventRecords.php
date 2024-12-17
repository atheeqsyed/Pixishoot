<?php
// Include the database connection file
require_once '../db_connection.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set Content-Type header for JSON response
header('Content-Type: application/json');

// Initialize the response
$response = [];

try {
    // Check if the database connection is established
    if (!$conn) {
        throw new Exception('Database connection not established');
    }

    // Get parameters from the GET request and sanitize them
    $service = isset($_GET['service']) && !empty($_GET['service']) ? trim($_GET['service']) : null;
    $start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

    // Default to today's date if start and end date are not provided
    if (!$start_date || !$end_date) {
        $start_date = $end_date = date('Y-m-d');
    }

    // SQL query to select records within the specified date range, with optional service filter
    $sql = "SELECT * FROM new_events WHERE date BETWEEN ? AND ?";

    // Add service filter to the query if provided
    if ($service) {
        $sql .= " AND service = ?";
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
    }

    // Bind parameters dynamically based on the presence of the service filter
    if ($service) {
        $stmt->bind_param("sss", $start_date, $end_date, $service);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch records into an array
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    // Set response as the fetched records
    $response = $records;
} catch (Exception $e) {
    // Handle errors and exceptions
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
} finally {
    // Output the JSON response
    echo json_encode($response);

    // Clean up resources
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
