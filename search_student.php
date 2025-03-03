<?php
include("database.php");

$student_id = $_GET['id'];
$sql = "SELECT id_number, CONCAT(last_name, ' ', first_name, ' ', middle_name) AS name, sessions FROM info WHERE id_number = '$student_id'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
    echo json_encode($student);
} else {
    echo json_encode(null);
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
