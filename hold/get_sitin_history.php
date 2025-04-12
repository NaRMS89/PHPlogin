<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate = isset($_GET['end']) ? $_GET['end'] : '';
$lab = isset($_GET['lab']) ? $_GET['lab'] : '';

$sql = "SELECT s.*, i.first_name, i.last_name,
        TIMESTAMPDIFF(MINUTE, s.login_time, s.logout_time) as duration
        FROM sitin_report s
        JOIN info i ON s.id_number = i.id_number
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($startDate)) {
    $sql .= " AND DATE(s.login_time) >= ?";
    $params[] = $startDate;
    $types .= 's';
}

if (!empty($endDate)) {
    $sql .= " AND DATE(s.login_time) <= ?";
    $params[] = $endDate;
    $types .= 's';
}

if (!empty($lab)) {
    $sql .= " AND s.lab = ?";
    $params[] = $lab;
    $types .= 's';
}

$sql .= " ORDER BY s.login_time DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$historyData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $historyData[] = [
        'date' => date('Y-m-d H:i:s', strtotime($row['login_time'])),
        'id_number' => $row['id_number'],
        'student_name' => $row['first_name'] . ' ' . $row['last_name'],
        'purpose' => $row['purpose'],
        'lab' => $row['lab'],
        'duration' => $row['duration'] . ' minutes',
        'status' => $row['status']
    ];
}

header('Content-Type: application/json');
echo json_encode($historyData);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 