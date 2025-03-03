<?php
include("database.php");

$id_number = $_POST['id_number'];
$purpose = $_POST['purpose'];
$lab = $_POST['lab'];

$sql = "INSERT INTO current_sit_ins (id_number, purpose, lab) VALUES ('$id_number', '$purpose', '$lab')";
if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
