<?php
session_start();
include("../includes/database.php");
include("../includes/reservation_functions.php");

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) && $_GET['status'] != 'all' ? $_GET['status'] : null;
$lab = isset($_GET['lab']) && $_GET['lab'] != 'all' ? $_GET['lab'] : null;
$date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;

// Get filtered reservations
$reservations = filterReservations($conn, $status, $lab, $date);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($reservations);

// Close the database connection
if ($conn instanceof mysqli) { 
    mysqli_close($conn); 
}
?>
