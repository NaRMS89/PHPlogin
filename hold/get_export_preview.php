<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get parameters from the request
$type = $_GET['type'] ?? '';
$filterBy = $_GET['filterBy'] ?? '';
$filterValue = $_GET['filterValue'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

// Build the query
$query = "SELECT sr.*, i.name 
          FROM sitin_report sr 
          JOIN info i ON sr.id_number = i.id_number 
          WHERE 1=1";

$params = [];
$types = "";

if ($startDate && $endDate) {
    $query .= " AND sr.login_time BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

if ($filterBy && $filterValue) {
    $query .= " AND sr.{$filterBy} = ?";
    $params[] = $filterValue;
    $types .= "s";
}

$query .= " ORDER BY sr.login_time DESC LIMIT 10";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate preview HTML
$preview = '<table class="preview-table" style="width: 100%; border-collapse: collapse;">';
$preview .= '<thead><tr>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">ID Number</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Student Name</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Lab</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Purpose</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Login Time</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Logout Time</th>';
$preview .= '<th style="padding: 8px; border: 1px solid #ddd;">Duration</th>';
$preview .= '</tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $preview .= '<tr>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['id_number']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['name']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['lab']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['purpose']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['login_time']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['logout_time']) . '</td>';
    $preview .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['duration']) . '</td>';
    $preview .= '</tr>';
}

$preview .= '</tbody></table>';

// Add summary information
$preview .= '<div style="margin-top: 20px; padding: 10px; background-color: #f5f5f5; border-radius: 5px;">';
$preview .= '<p><strong>Export Type:</strong> ' . strtoupper($type) . '</p>';
$preview .= '<p><strong>Date Range:</strong> ' . $startDate . ' to ' . $endDate . '</p>';
if ($filterBy && $filterValue) {
    $preview .= '<p><strong>Filter:</strong> ' . ucfirst($filterBy) . ' = ' . $filterValue . '</p>';
}
$preview .= '<p><strong>Note:</strong> This preview shows the first 10 records. The actual export will include all matching records.</p>';
$preview .= '</div>';

// Return the preview as JSON
header('Content-Type: application/json');
echo json_encode(['preview' => $preview]);

// Close database connection
$stmt->close();
$conn->close();
?> 