<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$lab = $_POST['lab'];
$computer_number = $_POST['computer_number'];

// Get current status
$sql = "SELECT status FROM computers WHERE lab = ? AND computer_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $lab, $computer_number);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    // Computer doesn't exist, create it
    $insert_sql = "INSERT INTO computers (lab, computer_number, status) VALUES (?, ?, 'disabled')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("si", $lab, $computer_number);
    $new_status = 'disabled';
} else {
    // Toggle status
    $current_status = $row['status'];
    $new_status = $current_status === 'disabled' ? 'available' : 'disabled';
    
    $update_sql = "UPDATE computers SET status = ? WHERE lab = ? AND computer_number = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $new_status, $lab, $computer_number);
}

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Computer status updated successfully',
        'new_status' => $new_status
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error updating computer status: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 