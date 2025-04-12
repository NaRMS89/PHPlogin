<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$entries = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $entries;

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM sit_in_history";
if ($search) {
    $count_sql .= " WHERE id_number LIKE '%$search%' OR purpose LIKE '%$search%' OR lab LIKE '%$search%'";
}
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $entries);

// Get records for current page
$sql = "SELECT sh.*, i.first_name, i.last_name 
        FROM sit_in_history sh 
        JOIN info i ON sh.id_number = i.id_number";
if ($search) {
    $sql .= " WHERE sh.id_number LIKE '%$search%' OR sh.purpose LIKE '%$search%' OR sh.lab LIKE '%$search%'";
}
$sql .= " ORDER BY sh.login_time DESC LIMIT $offset, $entries";

$result = mysqli_query($conn, $sql);
$records = [];

while ($row = mysqli_fetch_assoc($result)) {
    $records[] = [
        'id' => $row['id'],
        'id_number' => $row['id_number'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'purpose' => $row['purpose'],
        'lab' => $row['lab'],
        'login_time' => date('Y-m-d H:i:s', strtotime($row['login_time'])),
        'logout_time' => $row['logout_time'] ? date('Y-m-d H:i:s', strtotime($row['logout_time'])) : null,
        'status' => $row['status']
    ];
}

// Get chart data
$chart_sql = "SELECT purpose, COUNT(*) as count 
              FROM sit_in_history 
              GROUP BY purpose 
              ORDER BY count DESC 
              LIMIT 5";
$chart_result = mysqli_query($conn, $chart_sql);
$chart_data = [
    'labels' => [],
    'values' => []
];

while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_data['labels'][] = $row['purpose'];
    $chart_data['values'][] = $row['count'];
}

$response = [
    'records' => $records,
    'totalPages' => $total_pages,
    'currentPage' => $page,
    'chartData' => $chart_data
];

header('Content-Type: application/json');
echo json_encode($response);
?> 