<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Function to get current sit-in students
function getCurrentSitInStudents($conn) {
    $sql = "SELECT s.id as sitin_id, s.id_number, s.purpose, s.lab, s.status, 
            i.first_name, i.last_name, i.sessions 
            FROM sitin s 
            JOIN info i ON s.id_number = i.id_number 
            WHERE s.status = 'active' 
            ORDER BY s.id DESC";
            
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Error in getCurrentSitInStudents: " . mysqli_error($conn));
        return [];
    }
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

// Get the data
if ($conn instanceof mysqli) {
    $currentSitInStudents = getCurrentSitInStudents($conn);
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($currentSitInStudents);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
}

// Close database connection
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
