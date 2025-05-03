<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$reservation_id = $_POST['reservation_id'];
$action = $_POST['action'];

// Get reservation details
$sql = "SELECT * FROM reservations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    exit();
}

// Update reservation status
$new_status = $action === 'approve' ? 'approved' : 'denied';
$update_sql = "UPDATE reservations SET status = ? WHERE id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("si", $new_status, $reservation_id);

if ($stmt->execute()) {
    if ($action === 'approve') {
        // If approved, update computer status to in-use
        $computer_sql = "UPDATE computers SET status = 'in-use' 
                        WHERE lab = ? AND computer_number = ?";
        $stmt = $conn->prepare($computer_sql);
        $stmt->bind_param("si", $reservation['lab'], $reservation['computer_number']);
        $stmt->execute();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Reservation ' . $action . 'd successfully'
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error updating reservation status: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 