<?php
include("../includes/database.php");

// Get both current and historical sit-in data
$sql = "SELECT s.*, i.first_name, i.last_name, i.sessions, s.login_time 
        FROM sitin s 
        JOIN info i ON s.id_number = i.id_number 
        WHERE s.status = 'active'
        UNION ALL
        SELECT sr.*, i.first_name, i.last_name, i.sessions, sr.login_time 
        FROM sitin_report sr 
        JOIN info i ON sr.id_number = i.id_number 
        ORDER BY login_time DESC";

$result = mysqli_query($conn, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

mysqli_close($conn);
?> 