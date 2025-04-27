<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_id = $_POST['lab_id'];
    $computer_number = $_POST['computer_number'];
    $status = $_POST['status'];
    
    // Validate inputs
    if (!in_array($status, ['available', 'maintenance', 'reserved'])) {
        http_response_code(400);
        echo "Invalid status";
        exit;
    }
    
    // Update the computer status
    $sql = "UPDATE lab_computers 
            SET status = ? 
            WHERE lab_id = ? AND computer_number = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $status, $lab_id, $computer_number);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Success";
    } else {
        http_response_code(500);
        echo "Error updating status";
    }
    
    mysqli_stmt_close($stmt);
} else {
    http_response_code(405);
    echo "Method not allowed";
}
?>
