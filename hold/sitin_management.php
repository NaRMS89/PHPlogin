<?php
session_start();
include("../includes/database.php");

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to add a sit-in record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_sitin"])) {
    $idNo = mysqli_real_escape_string($conn, $_POST['id_number']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);

    $sql = "INSERT INTO sitin (id_number, purpose, lab, status) VALUES ('$idNo', '$purpose', '$lab', 'active')";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Sit-in record added successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// Function to get current sit-in data
if (isset($_GET["get_current_sitin_data"])) {
    $sql = "SELECT s.*, i.first_name, i.last_name, i.sessions, s.id AS sitin_id FROM sitin s JOIN info i ON s.id_number = i.id_number WHERE s.status = 'active'";
    if ($conn) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $sitinData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $sitinData[] = $row;
            }
            echo json_encode($sitinData);
        } else {
            echo json_encode(["error" => "Failed to fetch sit-in data"]);
        }
    } else {
        echo json_encode(["error" => "Database connection failed"]);
    }
}

// Function to log out a sit-in
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout_sitin"])) {
    $idNo = mysqli_real_escape_string($conn, $_POST['id_number']);
    $sql = "UPDATE sitin SET status = 'inactive' WHERE id_number = '$idNo' AND status = 'active'";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Sit-in logged out successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>
