<?php
include("database.php");

$sql = "SELECT c.id_number, CONCAT(i.last_name, ' ', i.first_name, ' ', i.middle_name) AS name FROM current_sit_ins c JOIN info i ON c.id_number = i.id_number";
$result = mysqli_query($conn, $sql);

$sitIns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sitIns[] = $row;
}

echo json_encode($sitIns);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
