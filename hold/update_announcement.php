<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the announcement text
    $announcement = filter_input(INPUT_POST, "announcement", FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (empty($announcement)) {
        $response['success'] = false;
        $response['message'] = "Announcement text cannot be empty.";
    } else {
        // Get current date in YYYY-MM-DD format
        $currentDate = date('Y-m-d');
        
        // Prepare and execute the SQL statement
        $sql = "INSERT INTO announcements (announcement_text, date_posted) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $announcement, $currentDate);
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = "Announcement posted successfully.";
                $response['announcement'] = array(
                    'announcement_id' => mysqli_insert_id($conn),
                    'announcement_text' => $announcement,
                    'date_posted' => $currentDate
                );
            } else {
                $response['success'] = false;
                $response['message'] = "Error posting announcement: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['success'] = false;
            $response['message'] = "Error preparing statement: " . mysqli_error($conn);
        }
    }
} else {
    $response['success'] = false;
    $response['message'] = "Invalid request method.";
}

header('Content-Type: application/json');
echo json_encode($response);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
