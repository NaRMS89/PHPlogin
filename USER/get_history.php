<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_data'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_data']['id_number'];

$sql = "SELECT sh.*, i.first_name, i.last_name 
        FROM sit_in_history sh 
        JOIN info i ON sh.id_number = i.id_number 
        WHERE sh.id_number = '$user_id' 
        ORDER BY sh.login_time DESC";

$result = mysqli_query($conn, $sql);
$history = [];

while ($row = mysqli_fetch_assoc($result)) {
    $history[] = [
        'id' => $row['id'],
        'id_number' => $row['id_number'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'purpose' => $row['purpose'],
        'lab' => $row['lab'],
        'login_time' => date('Y-m-d H:i:s', strtotime($row['login_time'])),
        'logout_time' => $row['logout_time'] ? date('Y-m-d H:i:s', strtotime($row['logout_time'])) : null,
        'date' => date('Y-m-d', strtotime($row['login_time'])),
        'status' => $row['status']
    ];
}

header('Content-Type: application/json');
echo json_encode($history);
?> 