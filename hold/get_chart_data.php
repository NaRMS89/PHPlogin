<?php
include("../includes/database.php");

// Get lab distribution data
$lab_sql = "SELECT lab, COUNT(*) as count FROM sitin_report GROUP BY lab";
$lab_result = mysqli_query($conn, $lab_sql);
$lab_data = [
    'labels' => [],
    'values' => []
];
while ($row = mysqli_fetch_assoc($lab_result)) {
    $lab_data['labels'][] = $row['lab'];
    $lab_data['values'][] = (int)$row['count'];
}

// Get purpose distribution data
$purpose_sql = "SELECT purpose, COUNT(*) as count FROM sitin_report GROUP BY purpose";
$purpose_result = mysqli_query($conn, $purpose_sql);
$purpose_data = [
    'labels' => [],
    'values' => []
];
while ($row = mysqli_fetch_assoc($purpose_result)) {
    $purpose_data['labels'][] = str_replace(" Programming", "", $row['purpose']);
    $purpose_data['values'][] = (int)$row['count'];
}

// Combine both datasets
$response = [
    'lab' => $lab_data,
    'purpose' => $purpose_data
];

header('Content-Type: application/json');
echo json_encode($response);

mysqli_close($conn);
?> 