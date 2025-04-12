<?php
session_start();
include("../includes/database.php");

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to get student data
function getStudentData($idNo, $conn) {
    $sql = "SELECT * FROM students WHERE id_number = '$idNo'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Function to test database connection
if (isset($_GET["test_connection"])) {
    if ($conn) {
        echo "Database connection successful!";
    } else {
        echo "Database connection failed!";
    }
}

// Function to search by ID
if (isset($_POST["search_id"])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $sql = "SELECT * FROM students WHERE id_number = '$id'";
    if ($conn) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $studentData = mysqli_fetch_assoc($result);
            echo json_encode($studentData);
        } else {
            echo json_encode(["error" => "Failed to fetch student data"]);
        }
    } else {
        echo json_encode(["error" => "Database connection failed"]);
    }
}
?>
