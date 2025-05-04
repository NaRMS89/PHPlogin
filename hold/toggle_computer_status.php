<?php
require_once 'config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['lab']) || !isset($_POST['pc_number']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$lab = $_POST['lab'];
$pcNumber = $_POST['pc_number'];
$status = $_POST['status'];

// Validate status
$validStatuses = ['available', 'maintenance', 'occupied'];
if (!in_array($status, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

try {
    $query = "UPDATE computers SET status = ? WHERE lab_id = ? AND pc_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $status, $lab, $pcNumber);
    $success = $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}

$conn->close();
?> 