<?php
require_once 'config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['lab'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Lab parameter is required']);
    exit;
}

$lab = $_GET['lab'];

try {
    $query = "SELECT * FROM computers WHERE lab_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $computers = array();
    while ($row = $result->fetch_assoc()) {
        $computers[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($computers);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}

$conn->close();
?> 