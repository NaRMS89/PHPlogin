<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$entries = isset($_GET['entriesPerPage']) ? (int)$_GET['entriesPerPage'] : 10;
$search = isset($_GET['term']) ? mysqli_real_escape_string($conn, $_GET['term']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $entries;

// Build the WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(sr.id_number LIKE ? OR CONCAT(i.first_name, ' ', i.last_name) LIKE ? OR sr.purpose LIKE ? OR sr.lab LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($date) {
    $where_conditions[] = "DATE(sr.login_time) = ?";
    $params[] = $date;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM sitin_report sr 
              JOIN info i ON sr.id_number = i.id_number
              $where_clause";

$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $entries);

// Get records for current page
$sql = "SELECT sr.*, i.first_name, i.last_name,
        DATE_FORMAT(sr.login_time, '%Y-%m-%d') as date,
        DATE_FORMAT(sr.login_time, '%H:%i:%s') as login_time,
        DATE_FORMAT(sr.logout_time, '%H:%i:%s') as logout_time
        FROM sitin_report sr 
        JOIN info i ON sr.id_number = i.id_number
        $where_clause
        ORDER BY sr.login_time DESC 
        LIMIT ?, ?";

$params[] = $offset;
$params[] = $entries;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $records[] = $row;
}

header('Content-Type: application/json');
echo json_encode($records);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 