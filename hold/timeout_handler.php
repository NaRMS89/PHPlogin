<?php
session_start();
include("../includes/database.php");

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Function to get student data
function getStudentData($idNo, $conn) {
    $sql = "SELECT id_number, first_name, last_name, points, total_points, sessions FROM info WHERE id_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Handle giving a point and timing out
if (isset($_POST['give_point_and_timeout'])) {
    $idNo = $_POST['id_number'];
    $success = false;
    $message = '';
    
    if ($conn instanceof mysqli) {
        // Add 1 point and update total points
        $update = mysqli_query($conn, "UPDATE info SET points = points + 1, total_points = total_points + 1 WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "'");
        
        // Log the point award
        $log = mysqli_query($conn, "INSERT INTO points_log (id_number, points_added, awarded_at) VALUES ('" . mysqli_real_escape_string($conn, $idNo) . "', 1, NOW())");
        
        // Timeout (set sitin status to inactive)
        $timeout = mysqli_query($conn, "UPDATE sitin SET status = 'inactive' WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "' AND status = 'active'");
        
        if ($update && $log && $timeout) {
            $success = true;
            $message = 'Student awarded 1 point and timed out successfully.';
            
            // Get updated student data
            $student = getStudentData($idNo, $conn);
        } else {
            $message = 'Error updating records.';
        }
    } else {
        $message = 'Database connection error.';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success, 
        'message' => $message,
        'student' => $student ?? null
    ]);
    exit();
}

// Handle timing out without giving points
if (isset($_POST['timeout_only'])) {
    $idNo = $_POST['id_number'];
    $success = false;
    $message = '';
    $additionalSession = false;
    
    if ($conn instanceof mysqli) {
        // First, get current student info
        $getInfo = mysqli_query($conn, "SELECT points, total_points, sessions FROM info WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "'");
        $studentInfo = mysqli_fetch_assoc($getInfo);
        
        // Decrease sessions by 1
        $decreaseSession = mysqli_query($conn, "UPDATE info SET sessions = GREATEST(0, sessions - 1) WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "'");
        
        // Check if their points are at 3 or more, if so, give +1 session and reset points
        if ($studentInfo && $studentInfo['points'] >= 3) {
            // Reset points and add a session
            $updatePoints = mysqli_query($conn, "UPDATE info SET points = 0, sessions = sessions + 1 WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "'");
            $message = 'Student reached 3 points. Points reset and session added.';
            $additionalSession = true;
        } else {
            $message = 'Student timed out successfully. Sessions decreased by 1.';
        }
        
        // Timeout (set sitin status to inactive)
        $timeout = mysqli_query($conn, "UPDATE sitin SET status = 'inactive' WHERE id_number = '" . mysqli_real_escape_string($conn, $idNo) . "' AND status = 'active'");
        
        if ($decreaseSession && $timeout) {
            $success = true;
            
            // Get updated student data
            $student = getStudentData($idNo, $conn);
        } else {
            $message = 'Error updating records.';
        }
    } else {
        $message = 'Database connection error.';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success, 
        'message' => $message,
        'additionalSession' => $additionalSession,
        'student' => $student ?? null
    ]);
    exit();
}

// Get student data for the timeout modal
if (isset($_GET['get_student_data'])) {
    $idNo = $_GET['id'];
    $student = null;
    
    if ($conn instanceof mysqli) {
        $student = getStudentData($idNo, $conn);
    }
    
    header('Content-Type: application/json');
    echo json_encode($student);
    exit();
}

// Return error if no valid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>
