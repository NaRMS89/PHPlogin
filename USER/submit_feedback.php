<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sitin_id = $_POST['sitin_id'];
    $feedback = $_POST['feedback'];
    $id_number = $_SESSION['user_id'];

    // Verify that the sit-in belongs to the user
    $verify_sql = "SELECT id FROM sitin_report WHERE id = ? AND id_number = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ss", $sitin_id, $id_number);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if (mysqli_num_rows($verify_result) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid sit-in record']);
        exit();
    }

    // Update the feedback
    $sql = "UPDATE sitin_report SET feedback = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $feedback, $sitin_id);

    if (mysqli_stmt_execute($stmt)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error submitting feedback']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 