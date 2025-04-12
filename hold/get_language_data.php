<?php
include("../includes/database.php");

// Get programming language usage data from sitin table
$sql = "SELECT purpose, COUNT(*) as count FROM sitin GROUP BY purpose ORDER BY count DESC";
$result = mysqli_query($conn, $sql);

$labels = [];
$values = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Remove "Programming" from the purpose name for cleaner labels
        $label = str_replace(" Programming", "", $row['purpose']);
        $labels[] = $label;
        $values[] = (int)$row['count'];
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'values' => $values
]);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
