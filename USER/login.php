<?php
session_start();
include("../includes/database.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idnumber = mysqli_real_escape_string($conn, $_POST['idnumber']);
    $password = $_POST['password'];

    // First check admin credentials
    $admin_query = "SELECT * FROM admin WHERE id_number = '$idnumber'";
    $admin_result = mysqli_query($conn, $admin_query);

    if (mysqli_num_rows($admin_result) > 0) {
        $admin = mysqli_fetch_assoc($admin_result);
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id_number'];
            echo json_encode(['success' => true, 'redirect' => '../ADMIN/admin_dashboard.php']);
            exit();
        }
    }

    // If not admin, check student credentials
    $student_query = "SELECT * FROM info WHERE id_number = '$idnumber'";
    $student_result = mysqli_query($conn, $student_query);

    if (mysqli_num_rows($student_result) > 0) {
        $student = mysqli_fetch_assoc($student_result);
        if (password_verify($password, $student['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $student['id_number'];
            $_SESSION['username'] = $student['first_name'] . ' ' . $student['last_name'];
            echo json_encode(['success' => true, 'redirect' => 'user_dashboard.php']);
            exit();
        }
    }

    // If we get here, login failed
    error_log("Login failed for ID: $idnumber");
    echo json_encode(['success' => false, 'message' => 'Invalid ID number or password']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?> 