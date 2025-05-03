<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$labs = json_decode($_POST['labs']);
$computers = [];

foreach ($labs as $lab) {
    // Get computer status from database
    $sql = "SELECT computer_number, status FROM computers WHERE lab = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $computers[] = [
            'lab' => $lab,
            'number' => $row['computer_number'],
            'status' => $row['status']
        ];
    }
    
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($computers);
$conn->close();
?> 