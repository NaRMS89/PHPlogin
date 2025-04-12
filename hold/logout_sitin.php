<?php
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_number'])) {
    $idNo = $_POST['id_number'];

    $sql = "UPDATE sitin SET status = 'inactive' WHERE id_number = '$idNo' AND status = 'active'";
    if (mysqli_query($conn, $sql)) {
        $sql = "INSERT INTO sitin_report (id_number, purpose, lab, logout_time) SELECT id_number, purpose, lab, NOW() FROM sitin WHERE id_number = '$idNo' AND status = 'inactive'";
        mysqli_query($conn, $sql);
        echo "Logout successful";
    } else {
        echo "Error updating sitin status: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request";
}
?>
