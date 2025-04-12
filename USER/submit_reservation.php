<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_data'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_id = $_SESSION['user_data']['id_number'];
$lab = mysqli_real_escape_string($conn, $_POST['lab']);
$purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
$reservation_date = mysqli_real_escape_string($conn, $_POST['reservationDate']);
$start_time = mysqli_real_escape_string($conn, $_POST['startTime']);
$end_time = mysqli_real_escape_string($conn, $_POST['endTime']);

// Validate date and time
$reservation_datetime = strtotime($reservation_date . ' ' . $start_time);
$end_datetime = strtotime($reservation_date . ' ' . $end_time);

if ($reservation_datetime < time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot make reservations for past dates']);
    exit();
}

if ($end_datetime <= $reservation_datetime) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

// Check for existing reservations in the same time slot
$check_sql = "SELECT * FROM reservations 
              WHERE lab = '$lab' 
              AND reservation_date = '$reservation_date' 
              AND status != 'cancelled' 
              AND (
                  (start_time <= '$start_time' AND end_time > '$start_time') OR
                  (start_time < '$end_time' AND end_time >= '$end_time') OR
                  (start_time >= '$start_time' AND end_time <= '$end_time')
              )";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already reserved']);
    exit();
}

// Insert the reservation
$sql = "INSERT INTO reservations (student_id, lab, purpose, reservation_date, start_time, end_time, status) 
        VALUES ('$user_id', '$lab', '$purpose', '$reservation_date', '$start_time', '$end_time', 'pending')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Reservation submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error submitting reservation']);
}
?> 