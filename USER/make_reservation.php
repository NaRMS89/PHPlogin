<?php
// Include database connection
include '../includes/database.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $lab = $_POST['lab'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = $_POST['purpose'];
    
    // Validate date (must be today or future)
    $today = date('Y-m-d');
    if ($date < $today) {
        $_SESSION['error'] = "Reservation date must be today or a future date.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Validate time (end time must be after start time)
    if ($start_time >= $end_time) {
        $_SESSION['error'] = "End time must be after start time.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Check if the time slot is available
    $check_sql = "SELECT COUNT(*) as count FROM reservations 
                  WHERE lab = '$lab' AND date = '$date' 
                  AND ((start_time <= '$start_time' AND end_time > '$start_time') 
                  OR (start_time < '$end_time' AND end_time >= '$end_time')
                  OR (start_time >= '$start_time' AND end_time <= '$end_time'))";
    
    $check_result = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] > 0) {
        $_SESSION['error'] = "This time slot is already reserved. Please choose a different time.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Insert reservation
    $insert_sql = "INSERT INTO reservations (id_number, lab, date, start_time, end_time, purpose, status) 
                   VALUES ('$user_id', '$lab', '$date', '$start_time', '$end_time', '$purpose', 'Pending')";
    
    if (mysqli_query($conn, $insert_sql)) {
        $_SESSION['success'] = "Reservation submitted successfully. Please wait for admin approval.";
    } else {
        $_SESSION['error'] = "Error submitting reservation: " . mysqli_error($conn);
    }
    
    header('Location: dashboard.php');
    exit;
} else {
    // If not submitted via POST, redirect to dashboard
    header('Location: dashboard.php');
    exit;
}
?> 