<?php
// Include the database connection file
require_once '../db_connection.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set Content-Type header for JSON response
header('Content-Type: application/json');

// Initialize the response
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve and validate input data
        $date = $_POST['date'] ?? null;
        $duration = intval($_POST['duration'] ?? 0);

        if (!$date || $duration <= 0) {
            throw new Exception('Invalid input data.');
        }

        // Ensure the date is in the correct format (YYYY-MM-DD)
        $formattedDate = date('Y-m-d', strtotime($date));
        if ($formattedDate !== $date) {
            throw new Exception('Invalid date format.');
        }

        // Define the working hours (9:00 AM to 6:00 PM)
        $activeStart = strtotime('09:00:00');
        $activeEnd = strtotime('18:00:00');
        $allSlots = [];

        // Generate slots based on the requested duration (1, 2, or 3 hours)
        for ($time = $activeStart; $time + $duration * 3600 <= $activeEnd; $time += $duration * 3600) {
            $slotStart = date('H:i:s', $time);
            $slotEnd = date('H:i:s', $time + $duration * 3600);

            $allSlots[] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'display' => date('h:i A', $time) . ' - ' . date('h:i A', $time + $duration * 3600),
            ];
        }

        // Fetch existing bookings for the selected date
        $stmt = $conn->prepare("SELECT start_time, end_time FROM new_events WHERE date = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
        }

        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();

        // Collect booked slots
        $bookedSlots = [];
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[] = [
                'start' => $row['start_time'],
                'end' => $row['end_time'],
            ];
        }

        $stmt->close();

        // Filter out booked slots from all generated slots
        $availableSlots = array_filter($allSlots, function ($slot) use ($bookedSlots) {
            foreach ($bookedSlots as $booked) {
                if (
                    ($slot['start'] >= $booked['start'] && $slot['start'] < $booked['end']) || 
                    ($slot['end'] > $booked['start'] && $slot['end'] <= $booked['end']) || 
                    ($slot['start'] <= $booked['start'] && $slot['end'] >= $booked['end'])
                ) {
                    return false; // Slot overlaps or is enclosed
                }
            }
            return true;
        });

        // Set response based on availability
        if (empty($availableSlots)) {
            $response['message'] = 'No available slots for the selected date and duration.';
        } else {
            $response['success'] = true;
            $response['slots'] = array_values($availableSlots); // Re-index the array
        }
    } catch (Exception $e) {
        // Handle exceptions and set error messages
        $response['message'] = $e->getMessage();
    } finally {
        // Output the JSON response
        echo json_encode($response);

        // Close the database connection
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    // Handle invalid request methods
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
