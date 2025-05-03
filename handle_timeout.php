<?php
session_start();
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['id_number'];
    $option = $_POST['timeout_option'];
    
    // Get current points and sessions
    $sql = "SELECT points, sessions FROM info WHERE id_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if ($option == 'point_and_timeout') {
        // Add point and timeout
        $new_points = $student['points'] + 1;
        
        // Check if points reach 3 to add session
        if ($new_points >= 3) {
            $new_sessions = $student['sessions'] + 1;
            $sql = "UPDATE info SET points = ?, sessions = ? WHERE id_number = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $new_points, $new_sessions, $id_number);
        } else {
            $sql = "UPDATE info SET points = ? WHERE id_number = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_points, $id_number);
        }
        
        // Timeout the student
        $timeout_sql = "UPDATE sitin SET status = 'timeout', logout_time = NOW() WHERE id_number = ? AND status = 'active'";
        $timeout_stmt = $conn->prepare($timeout_sql);
        $timeout_stmt->bind_param("s", $id_number);
        
        if ($stmt->execute() && $timeout_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Student timed out and point added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating student data.']);
        }
    } else if ($option == 'normal_timeout') {
        // Normal timeout - subtract session
        $new_sessions = max(0, $student['sessions'] - 1);
        
        $sql = "UPDATE info SET sessions = ? WHERE id_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $new_sessions, $id_number);
        
        // Timeout the student
        $timeout_sql = "UPDATE sitin SET status = 'timeout', logout_time = NOW() WHERE id_number = ? AND status = 'active'";
        $timeout_stmt = $conn->prepare($timeout_sql);
        $timeout_stmt->bind_param("s", $id_number);
        
        if ($stmt->execute() && $timeout_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Student timed out successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating student data.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid timeout option.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
