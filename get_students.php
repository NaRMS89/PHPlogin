<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get all students with their points
$query = "SELECT id_number, first_name, last_name, course, year_level, sessions, points 
          FROM info 
          ORDER BY last_name, first_name";

$result = mysqli_query($conn, $query);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error fetching student data']);
    exit();
}

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);

mysqli_close($conn);
?> 