<?php
session_start();
require_once("../includes/database.php");

// Check for admin or student login
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['student_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Fetch resources from database
$sql = "SELECT id, title, description, type, file_path, DATE_FORMAT(date_added, '%M %d, %Y %h:%i %p') as formatted_date 
        FROM lab_resources 
        ORDER BY date_added DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    $resources = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Add base URL to file path if it's not a link
        if ($row['type'] !== 'link' && !empty($row['file_path'])) {
            $row['file_path'] = '../' . $row['file_path'];
        }
        $resources[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $resources]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error fetching resources']);
}
?> 