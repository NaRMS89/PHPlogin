<?php
// Include database connection
include '../includes/database.php';

// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Resource ID not provided']);
    exit;
}

$id = $_GET['id'];

// Get resource details
$sql = "SELECT * FROM lab_resources WHERE id = $id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $resource = mysqli_fetch_assoc($result);
    
    // Delete file if it's not a link
    if ($resource['type'] != 'link') {
        $file_path = '../' . $resource['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete from database
    $delete_sql = "DELETE FROM lab_resources WHERE id = $id";
    
    if (mysqli_query($conn, $delete_sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting resource: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
}
?> 