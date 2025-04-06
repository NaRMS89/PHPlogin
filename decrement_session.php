<?php
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_number'])) {
    $idNo = $_POST['id_number'];

    // Decrement the sessions count for the given id_number
    $sql = "UPDATE info SET sessions = sessions - 1 WHERE id_number = '$idNo' AND sessions > 0";
    if (mysqli_query($conn, $sql)) {
        echo "Session decremented successfully.";
    } else {
        echo "Error decrementing session: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request.";
}
?>
