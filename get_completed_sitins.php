<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Database connection failed"));
    exit();
}

try {
    $sql = "SELECT 
                sr.id,
                sr.id_number,
                CONCAT(i.first_name, ' ', i.last_name) as student_name,
                sr.purpose,
                sr.lab,
                sr.login_time,
                sr.logout_time,
                TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time) as duration_minutes
            FROM sitin_report sr
            JOIN info i ON sr.id_number = i.id_number
            ORDER BY sr.logout_time DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error executing query: " . mysqli_error($conn));
    }

    $reports = array();
    while ($row = mysqli_fetch_assoc($result)) {
        // Format timestamps
        $login = new DateTime($row['login_time']);
        $logout = new DateTime($row['logout_time']);
        
        $row['login_time'] = $login->format('Y-m-d h:i:s A');
        $row['logout_time'] = $logout->format('Y-m-d h:i:s A');
        
        // Calculate duration in hours and minutes
        $hours = floor($row['duration_minutes'] / 60);
        $minutes = $row['duration_minutes'] % 60;
        $row['duration'] = sprintf("%d hours %d minutes", $hours, $minutes);
        
        unset($row['duration_minutes']); // Remove raw duration
        
        $reports[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($reports);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => $e->getMessage()));
} finally {
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    }
}
?> 