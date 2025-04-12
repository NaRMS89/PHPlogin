<?php
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idNo = $_POST['id_number'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];

    $sql = "INSERT INTO sitin (id_number, purpose, lab, status) VALUES ('$idNo', '$purpose', '$lab', 'active')";
    if (mysqli_query($conn, $sql)) {
        echo "Sit-in added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>
