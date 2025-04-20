<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_data']['id_number'];
$response = array();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$entriesPerPage = 10;
$offset = ($page - 1) * $entriesPerPage;

// Get search and date filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

// Build the WHERE clause
$where_conditions = ["r.user_id = '$user_id'"];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(r.lab LIKE ? OR r.purpose LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}

if ($date) {
    $where_conditions[] = "DATE(r.date) = ?";
    $params[] = $date;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM reservations r 
              JOIN labs l ON r.lab = l.room_number 
              $where_clause";

$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $entriesPerPage);

// Get reservation history with pagination
$sql = "SELECT r.*, l.room_number as lab_room
        FROM reservations r
        JOIN labs l ON r.lab = l.room_number
        $where_clause
        ORDER BY r.date DESC, r.start_time DESC
        LIMIT ?, ?";

$params[] = $offset;
$params[] = $entriesPerPage;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $history = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = array(
            'date' => date("F j, Y", strtotime($row['date'])),
            'lab_room' => htmlspecialchars($row['lab_room']),
            'start_time' => date("g:i A", strtotime($row['start_time'])),
            'end_time' => date("g:i A", strtotime($row['end_time'])),
            'status' => htmlspecialchars($row['status']),
            'purpose' => htmlspecialchars($row['purpose'])
        );
    }
    $response['success'] = true;
    $response['data'] = $history;
    $response['currentPage'] = $page;
    $response['totalPages'] = $total_pages;
} else {
    $response['success'] = false;
    $response['error'] = "Error fetching history: " . mysqli_error($conn);
}

header('Content-Type: application/json');
echo json_encode($response);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 