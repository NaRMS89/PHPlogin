<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if (!isset($_POST['sitin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Sit-in ID is required']);
    exit();
}

$sitinId = mysqli_real_escape_string($conn, $_POST['sitin_id']);

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Get student info before timeout
    $query = "SELECT id_number FROM sitin WHERE sitin_id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $sitinId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $idNumber = $row['id_number'];
        
        // Update sitin status and set logout time
        $updateSitin = "UPDATE sitin SET status = 'completed', logout_time = NOW() WHERE sitin_id = ?";
        $stmt = mysqli_prepare($conn, $updateSitin);
        mysqli_stmt_bind_param($stmt, "i", $sitinId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Insert into sitin_report
            $insertReport = "INSERT INTO sitin_report (id_number, purpose, lab, login_time, logout_time)
                           SELECT id_number, purpose, lab, login_time, logout_time
                           FROM sitin WHERE sitin_id = ?";
            $stmt = mysqli_prepare($conn, $insertReport);
            mysqli_stmt_bind_param($stmt, "i", $sitinId);
            
            if (mysqli_stmt_execute($stmt)) {
                // Deduct one session from info table
                $updateSessions = "UPDATE info SET sessions = sessions - 1 WHERE id_number = ? AND sessions > 0";
                $stmt = mysqli_prepare($conn, $updateSessions);
                mysqli_stmt_bind_param($stmt, "s", $idNumber);
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_commit($conn);
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Failed to update sessions");
                }
            } else {
                throw new Exception("Failed to insert into sitin_report");
            }
        } else {
            throw new Exception("Failed to update sitin status");
        }
    } else {
        throw new Exception("No active sit-in found with the provided ID");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 