<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$lab = $_POST['lab'];
$computers = [];

// Get computer status from database
$sql = "SELECT computer_number, status FROM computers WHERE lab = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $lab);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $computers[] = [
        'number' => $row['computer_number'],
        'status' => $row['status']
    ];
}

// If no computers exist for this lab, create them
if (empty($computers)) {
    for ($i = 1; $i <= 50; $i++) {
        $insert_sql = "INSERT INTO computers (lab, computer_number, status) VALUES (?, ?, 'available')";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("si", $lab, $i);
        $stmt->execute();
        
        $computers[] = [
            'number' => $i,
            'status' => 'available'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($computers);
$stmt->close();
$conn->close();
?> 