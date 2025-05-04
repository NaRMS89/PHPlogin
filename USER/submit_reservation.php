<?php
session_start();
include '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if all required fields are present
$required_fields = ['lab', 'date', 'start_time', 'end_time', 'purpose', 'computer_number'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

// Get form data
$user_id = $_SESSION['user_id'];
$lab = $_POST['lab'];
$date = $_POST['date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$purpose = $_POST['purpose'];
$computer_number = $_POST['computer_number'];

// Validate date (must be today or future)
$today = date('Y-m-d');
if ($date < $today) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reservation date must be today or a future date']);
    exit;
}

// Validate time (end time must be after start time)
if ($start_time >= $end_time) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit;
}

// Check if the computer is already reserved for the selected time slot
$check_sql = "SELECT COUNT(*) as count FROM reservations 
              WHERE lab = ? AND date = ? AND computer_number = ?
              AND ((start_time <= ? AND end_time > ?) 
              OR (start_time < ? AND end_time >= ?)
              OR (start_time >= ? AND end_time <= ?))
              AND status != 'denied'";

$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ssissssss", $lab, $date, $computer_number, 
                 $start_time, $start_time, $end_time, $end_time,
                 $start_time, $end_time);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This computer is already reserved for the selected time slot']);
    exit;
}

// Insert reservation
$insert_sql = "INSERT INTO reservations (id_number, lab, date, start_time, end_time, purpose, computer_number, status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("ssssssi", $user_id, $lab, $date, $start_time, $end_time, $purpose, $computer_number);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Reservation submitted successfully. Please wait for admin approval.']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error submitting reservation: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 