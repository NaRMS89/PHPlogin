<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if all required fields are present
if (!isset($_POST['student_id']) || !isset($_POST['points']) || !isset($_POST['reason'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$studentId = $_POST['student_id'];
$points = intval($_POST['points']);
$reason = $_POST['reason'];

// Validate points
if ($points <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Points must be greater than 0']);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get current points
    $getPointsQuery = "SELECT points, sessions FROM info WHERE id_number = ?";
    $stmt = mysqli_prepare($conn, $getPointsQuery);
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error getting current points');
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $currentPoints = $row['points'];
    $currentSessions = $row['sessions'];
    
    // Calculate new points
    $newPoints = $currentPoints + $points;
    
    // Check if points reach or exceed 3
    $autoBonus = 0;
    $addSession = false;
    if ($newPoints >= 3) {
        $autoBonus = 1; // Add 1 bonus point
        $newPoints = 0; // Reset points to 0
        $addSession = true; // Flag to add a session
    }
    
    // Update points and sessions in info table
    $updateQuery = "UPDATE info SET points = ?, sessions = sessions + ? WHERE id_number = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    $sessionIncrement = $addSession ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "iis", $newPoints, $sessionIncrement, $studentId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error updating points and sessions');
    }

    // Add record to points_log table
    $logQuery = "INSERT INTO points_log (id_number, points_added, reason, added_by, date_added) 
                 VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $logQuery);
    $addedBy = $_SESSION['admin_logged_in'];
    mysqli_stmt_bind_param($stmt, "siss", $studentId, $points, $reason, $addedBy);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error logging points addition');
    }
    
    // If auto bonus was added, log it separately
    if ($autoBonus > 0) {
        $bonusReason = "Auto bonus: Reached 3 points (+1 session added)";
        $logQuery = "INSERT INTO points_log (id_number, points_added, reason, added_by, date_added) 
                     VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $logQuery);
        mysqli_stmt_bind_param($stmt, "siss", $studentId, $autoBonus, $bonusReason, $addedBy);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error logging bonus points');
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    
    $message = 'Points added successfully';
    if ($autoBonus > 0) {
        $message .= '. Bonus point and +1 session added, points reset to 0';
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?> 