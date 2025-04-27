<?php
session_start();
include("../includes/database.php");
include("../includes/reservation_functions.php");

// Check if user is logged in
if (!isset($_SESSION['user_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to make a reservation']);
    exit();
}

// Check if this is a reservation request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_reservation'])) {
    // Get form data
    $idNumber = $_SESSION['user_data']['id_number'];
    $labId = filter_input(INPUT_POST, 'lab', FILTER_SANITIZE_STRING);
    $computerNumber = filter_input(INPUT_POST, 'computer_number', FILTER_SANITIZE_NUMBER_INT);
    $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);

    // Validate inputs
    if (!$labId || !$computerNumber || !$purpose || !$date || !$startTime || !$endTime) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Create reservation
    $result = createReservation($conn, $idNumber, $labId, $computerNumber, $purpose, $date, $startTime, $endTime);

    // Return response
    header('Content-Type: application/json');
    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Reservation request submitted successfully! Please wait for admin approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => $result]);
    }
    exit();
}

// If not a valid request, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>
