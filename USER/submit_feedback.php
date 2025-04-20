<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sit_in_id = isset($_POST['sit_in_id']) ? (int)$_POST['sit_in_id'] : 0;
    $feedback = isset($_POST['feedback']) ? mysqli_real_escape_string($conn, $_POST['feedback']) : '';
    
    if ($sit_in_id > 0 && !empty($feedback)) {
        $sql = "UPDATE sitin_report 
                SET feedback = ?, feedback_date = CURRENT_TIMESTAMP 
                WHERE id = ? AND id_number = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sis", $feedback, $sit_in_id, $_SESSION['user_data']['id_number']);
        
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = "Feedback submitted successfully";
        } else {
            $response['success'] = false;
            $response['error'] = "Error submitting feedback: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $response['success'] = false;
        $response['error'] = "Invalid feedback data";
    }
} else {
    $response['success'] = false;
    $response['error'] = "Invalid request method";
}

header('Content-Type: application/json');
echo json_encode($response);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 