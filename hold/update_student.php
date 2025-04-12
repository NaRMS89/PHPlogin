<?php
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idNo = mysqli_real_escape_string($conn, $_POST['id_number']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $yearLevel = mysqli_real_escape_string($conn, $_POST['year_level']);

    $sql = "UPDATE info SET first_name='$firstName', last_name='$lastName', course='$course', year_level='$yearLevel' WHERE id_number='$idNo'";

    if (mysqli_query($conn, $sql)) {
        echo "Student updated successfully";
    } else {
        echo "Error updating student: " . mysqli_error($conn);
    }
}
?>
