<?php
session_start();
include("../includes/database.php");

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit();
}

$sitin_id = $_GET['id'];
$id_number = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// If user is logged in, verify the sit-in belongs to them
if ($id_number) {
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
}

// Get the feedback
$sql = "SELECT id_number, feedback, login_time FROM sitin_report WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $sitin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'id_number' => $row['id_number'],
        'feedback' => $row['feedback'],
        'date' => $row['login_time']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No feedback found']);
}
?> 