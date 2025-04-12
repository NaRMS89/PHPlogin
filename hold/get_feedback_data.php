<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate = isset($_GET['end']) ? $_GET['end'] : '';
$lab = isset($_GET['lab']) ? $_GET['lab'] : '';

$sql = "SELECT f.*, i.first_name, i.last_name 
        FROM feedback f 
        JOIN info i ON f.id_number = i.id_number 
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (f.id_number LIKE ? OR i.first_name LIKE ? OR i.last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if (!empty($startDate)) {
    $sql .= " AND f.date >= ?";
    $params[] = $startDate;
    $types .= 's';
}

if (!empty($endDate)) {
    $sql .= " AND f.date <= ?";
    $params[] = $endDate;
    $types .= 's';
}

if (!empty($lab)) {
    $sql .= " AND f.lab = ?";
    $params[] = $lab;
    $types .= 's';
}

$sql .= " ORDER BY f.date DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$feedbackData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $feedbackData[] = [
        'date' => $row['date'],
        'id_number' => $row['id_number'],
        'student_name' => $row['first_name'] . ' ' . $row['last_name'],
        'lab' => $row['lab'],
        'feedback_text' => $row['feedback_text'],
        'rating' => $row['rating']
    ];
}

header('Content-Type: application/json');
echo json_encode($feedbackData);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 