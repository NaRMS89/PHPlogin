<?php
session_start();
include("../includes/database.php");

$sql = "SELECT s.*, i.first_name, i.last_name, i.course, i.sessions 
        FROM sitin s 
        JOIN info i ON s.id_number = i.id_number 
        WHERE s.status = 'active'";

$result = mysqli_query($conn, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?> 