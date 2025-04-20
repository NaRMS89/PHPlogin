<?php
session_start();
include("../includes/database.php");

// Debug: Log session data
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access', 'session' => $_SESSION]);
    exit();
}

$user_id = $_SESSION['user_data']['id_number'];
error_log("User ID: " . $user_id);

// Get sit-in history with feedback
$sql = "SELECT sr.*, i.first_name, i.last_name,
        TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time) as duration
        FROM sitin_report sr
        JOIN info i ON sr.id_number = i.id_number
        WHERE sr.id_number = ?
        ORDER BY sr.login_time DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($conn));
    echo json_encode(['error' => 'Database prepare failed', 'details' => mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $user_id);
if (!mysqli_stmt_execute($stmt)) {
    error_log("Execute failed: " . mysqli_stmt_error($stmt));
    echo json_encode(['error' => 'Database execute failed', 'details' => mysqli_stmt_error($stmt)]);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$history = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = [
            'id' => $row['id'],
            'date' => date("F j, Y", strtotime($row['login_time'])),
            'time' => date("g:i A", strtotime($row['login_time'])) . " - " . 
                     ($row['logout_time'] ? date("g:i A", strtotime($row['logout_time'])) : "Present"),
            'lab' => $row['lab'],
            'purpose' => $row['purpose'],
            'duration' => $row['duration'] ? $row['duration'] . " minutes" : "Ongoing",
            'feedback' => $row['feedback'],
            'feedback_date' => $row['feedback_date']
        ];
    }
    error_log("Found " . count($history) . " history records");
    $response['success'] = true;
    $response['data'] = $history;
} else {
    error_log("Query failed: " . mysqli_error($conn));
    $response['success'] = false;
    $response['error'] = "Error fetching history: " . mysqli_error($conn);
}

header('Content-Type: application/json');
echo json_encode($response);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 