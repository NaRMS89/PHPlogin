<?php
session_start();
require_once('../database.php');

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get filter parameters
$fromDate = isset($_GET['from']) ? $_GET['from'] : '';
$toDate = isset($_GET['to']) ? $_GET['to'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT 
    s.id_number,
    CONCAT(i.first_name, ' ', i.last_name) as student_name,
    s.purpose,
    s.lab,
    s.login_time,
    s.logout_time,
    DATE(s.login_time) as date,
    CASE 
        WHEN s.logout_time IS NULL THEN 'active'
        ELSE 'completed'
    END as status
FROM sitin_report s
JOIN info i ON s.id_number = i.id_number
WHERE 1=1";

// Add date filters if provided
if ($fromDate) {
    $query .= " AND DATE(s.login_time) >= ?";
    $params[] = $fromDate;
    $types .= "s";
}

if ($toDate) {
    $query .= " AND DATE(s.login_time) <= ?";
    $params[] = $toDate;
    $types .= "s";
}

// Add search filter if provided
if ($searchTerm) {
    $query .= " AND (
        s.id_number LIKE ? OR
        i.first_name LIKE ? OR
        i.last_name LIKE ? OR
        s.purpose LIKE ? OR
        s.lab LIKE ?
    )";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params ?? [], [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "sssss";
}

// Order by login time descending (most recent first)
$query .= " ORDER BY s.login_time DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all records
$records = [];
while ($row = $result->fetch_assoc()) {
    // Format dates for display
    $row['login_time'] = date('Y-m-d H:i:s', strtotime($row['login_time']));
    if ($row['logout_time']) {
        $row['logout_time'] = date('Y-m-d H:i:s', strtotime($row['logout_time']));
    }
    $records[] = $row;
}

// Close statement and connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($records);
?> 