<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Fetch data from sitin_report table
$sql = "SELECT id_number, purpose, lab, login_time, logout_time FROM sitin_report ORDER BY login_time DESC";
$result = mysqli_query($conn, $sql);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id_number' => $row['id_number'],
            'purpose' => $row['purpose'],
            'lab' => $row['lab'],
            'login_time' => $row['login_time'],
            'logout_time' => $row['logout_time']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 