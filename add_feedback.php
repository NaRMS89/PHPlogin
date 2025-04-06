<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $feedback_type = mysqli_real_escape_string($conn, $_POST['feedback_type']);
    $feedback_text = mysqli_real_escape_string($conn, $_POST['feedback_text']);
    $admin_id = $_SESSION['admin_id']; // Assuming you store admin_id in session

    $sql = "INSERT INTO feedback (student_id, admin_id, feedback_type, feedback_text, created_at) 
            VALUES ('$student_id', '$admin_id', '$feedback_type', '$feedback_text', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Feedback added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding feedback: ' . mysqli_error($conn)]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 