<?php
session_start();
include("../includes/database.php");
include("../includes/reservation_functions.php");

// Check if the user is logged in
if (!isset($_SESSION['user_data'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get parameters
$labId = isset($_GET['lab_id']) ? $_GET['lab_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$startTime = isset($_GET['start_time']) ? $_GET['start_time'] : null;
$endTime = isset($_GET['end_time']) ? $_GET['end_time'] : null;

// Validate parameters
if (!$labId || !$date || !$startTime || !$endTime) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// Get available computers
$computers = getAvailableComputers($conn, $labId, $date, $startTime, $endTime);

// Convert to the format needed for frontend
$formattedComputers = [];
foreach ($computers as $number => $data) {
    $formattedComputers[] = [
        'number' => $number,
        'status' => $data['status']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'lab_id' => $labId,
    'date' => $date,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'computers' => $formattedComputers
]);

// Close the database connection
if ($conn instanceof mysqli) { 
    mysqli_close($conn); 
}
?>
