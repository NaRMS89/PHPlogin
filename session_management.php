<?php
session_start();
include("../includes/database.php");

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to decrement session count
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["decrement_session"])) {
    $idNo = mysqli_real_escape_string($conn, $_POST['id_number']);
    $sql = "UPDATE students SET sessions = sessions - 1 WHERE id_number = '$idNo'";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Session count decremented successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// Function to reset sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_sessions"])) {
    session_unset();
    session_destroy();
    echo json_encode(["message" => "Sessions have been reset."]);
}
?>
