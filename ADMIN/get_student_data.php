<?php
// filepath: c:\xampp\htdocs\WEBSITE\get_student_data.php
include("../includes/database.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM info WHERE id_number = '$id'";
    $result = mysqli_query($conn, $sql);
    $student = mysqli_fetch_assoc($result);

    header('Content-Type: application/json');
    echo json_encode($student);
}
?>