<?php
session_start();
include("../includes/database.php");

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to add a new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_student"])) {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $lastname = filter_input(INPUT_POST, "lastname", FILTER_SANITIZE_SPECIAL_CHARS);
    $firstname = filter_input(INPUT_POST, "firstname", FILTER_SANITIZE_SPECIAL_CHARS);
    $middlename = filter_input(INPUT_POST, "middlename", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

    $sql = "INSERT INTO students (id_number, last_name, first_name, middle_name, password) VALUES ('$idno', '$lastname', '$firstname', '$middlename', '$password')";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Student added successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// Function to update student details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_student"])) {
    $idNo = mysqli_real_escape_string($conn, $_POST['id_number']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $sql = "UPDATE students SET first_name='$firstName', last_name='$lastName', middle_name='$middleName', email='$email', contact_number='$contactNumber', address='$address' WHERE id_number='$idNo'";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Student details updated successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

// Function to get student data
if (isset($_GET["get_student_data"])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
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
