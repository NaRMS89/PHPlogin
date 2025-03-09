<?php
include("database.php");

$sql = "SELECT s.*, i.first_name, i.last_name, i.sessions, s.id AS sitin_id FROM sitin s JOIN info i ON s.id_number = i.id_number WHERE s.status = 'active'";
$result = mysqli_query($conn, $sql);

$students = array();
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
?>
