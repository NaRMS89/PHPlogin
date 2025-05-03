<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$sql = "SELECT r.*, i.first_name, i.last_name 
        FROM reservations r 
        JOIN info i ON r.id_number = i.id_number 
        WHERE r.status = 'pending' 
        ORDER BY r.date, r.start_time";

$result = mysqli_query($conn, $sql);
$requests = [];

while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = [
        'id' => $row['id'],
        'id_number' => $row['id_number'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'date' => $row['date'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'lab' => $row['lab'],
        'computer_number' => $row['computer_number'],
        'purpose' => $row['purpose']
    ];
}

header('Content-Type: application/json');
echo json_encode($requests);
$conn->close();
?> 