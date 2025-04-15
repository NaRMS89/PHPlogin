<?php
include("../includes/database.php");

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No sit-in ID provided']);
    exit();
}

$sitInId = $_GET['id'];

// Get feedback from database
$sql = "SELECT f.*, s.id_number, s.purpose, s.lab 
        FROM feedback f 
        JOIN sitin s ON f.sit_in_id = s.id 
        WHERE s.id_number = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $sitInId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$feedbacks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $feedbacks[] = [
        'feedback_text' => $row['feedback_text'],
        'feedback_date' => $row['feedback_date'],
        'purpose' => $row['purpose'],
        'lab' => $row['lab']
    ];
}

echo json_encode([
    'success' => true,
    'feedbacks' => $feedbacks
]);

mysqli_close($conn);
?> 