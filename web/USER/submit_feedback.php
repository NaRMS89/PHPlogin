<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_data']['id_number'];
$purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);
$experience = filter_input(INPUT_POST, 'experience', FILTER_SANITIZE_STRING);
$comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_STRING);

if (empty($purpose) || empty($experience)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

// Get the current lab from the active sit-in record
$lab_sql = "SELECT lab FROM sitin WHERE id_number = ? AND status = 'active' ORDER BY login_time DESC LIMIT 1";
$lab_stmt = mysqli_prepare($conn, $lab_sql);
mysqli_stmt_bind_param($lab_stmt, 's', $user_id);
mysqli_stmt_execute($lab_stmt);
$lab_result = mysqli_stmt_get_result($lab_stmt);
$lab_data = mysqli_fetch_assoc($lab_result);
$lab = $lab_data ? $lab_data['lab'] : 'Unknown';

// Insert feedback into database
$sql = "INSERT INTO feedback (id_number, lab, feedback_text, rating, date) VALUES (?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $sql);

// Combine experience and comments for feedback_text
$feedback_text = $experience . "\n\nAdditional Comments:\n" . $comments;

// For now, we'll set a default rating of 5
$rating = 5;

mysqli_stmt_bind_param($stmt, 'sssi', $user_id, $lab, $feedback_text, $rating);

if (mysqli_stmt_execute($stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error submitting feedback: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_stmt_close($lab_stmt);
mysqli_close($conn);
?> 