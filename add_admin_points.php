<?php
// Include database connection
include '../includes/database.php';

// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $id_number = $_POST['id_number'];
    $points = $_POST['points'];
    $reason = $_POST['reason'];
    $date_added = date('Y-m-d H:i:s');
    
    // Validate points
    if (!is_numeric($points) || $points <= 0) {
        $_SESSION['error'] = "Points must be a positive number.";
        header('Location: admin_dashboard.php');
        exit;
    }
    
    // Check if student exists
    $check_sql = "SELECT * FROM info WHERE id_number = '$id_number'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "Student not found.";
        header('Location: admin_dashboard.php');
        exit;
    }
    
    // Check if student already has points
    $points_sql = "SELECT * FROM student_points WHERE id_number = '$id_number'";
    $points_result = mysqli_query($conn, $points_sql);
    
    if ($points_result && mysqli_num_rows($points_result) > 0) {
        // Update existing points
        $points_row = mysqli_fetch_assoc($points_result);
        $new_points = $points_row['points'] + $points;
        
        $update_sql = "UPDATE student_points SET points = $new_points WHERE id_number = '$id_number'";
        
        if (mysqli_query($conn, $update_sql)) {
            // Log points addition
            $log_sql = "INSERT INTO points_log (id_number, points, reason, date_added) 
                       VALUES ('$id_number', $points, '$reason', '$date_added')";
            mysqli_query($conn, $log_sql);
            
            $_SESSION['success'] = "Points added successfully.";
        } else {
            $_SESSION['error'] = "Error updating points: " . mysqli_error($conn);
        }
    } else {
        // Insert new points record
        $insert_sql = "INSERT INTO student_points (id_number, points) VALUES ('$id_number', $points)";
        
        if (mysqli_query($conn, $insert_sql)) {
            // Log points addition
            $log_sql = "INSERT INTO points_log (id_number, points, reason, date_added) 
                       VALUES ('$id_number', $points, '$reason', '$date_added')";
            mysqli_query($conn, $log_sql);
            
            $_SESSION['success'] = "Points added successfully.";
        } else {
            $_SESSION['error'] = "Error adding points: " . mysqli_error($conn);
        }
    }
    
    header('Location: admin_dashboard.php');
    exit;
} else {
    // If not submitted via POST, redirect to dashboard
    header('Location: admin_dashboard.php');
    exit;
}
?> 