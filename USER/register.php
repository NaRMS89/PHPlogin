<?php
session_start();
include("../includes/database.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idnumber = mysqli_real_escape_string($conn, $_POST['idnumber']);
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $yearlvl = mysqli_real_escape_string($conn, $_POST['yearlvl']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if ID number already exists
    $check_query = "SELECT id_number FROM info WHERE id_number = '$idnumber'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'ID Number already exists']);
        exit();
    }

    // Insert new student
    $sql = "INSERT INTO info (id_number, first_name, last_name, email, course, year_level, password, sessions) 
            VALUES ('$idnumber', '$firstname', '$lastname', '$email', '$course', '$yearlvl', '$password', 10)";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . mysqli_error($conn)]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 