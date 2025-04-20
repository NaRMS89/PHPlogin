<?php
session_start();
include("../includes/database.php");

// Check if user is logged in
if (!isset($_SESSION['user_data'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_data']['id_number'];

// Test query to check sitin_report table
$sql = "SELECT COUNT(*) as total FROM sitin_report WHERE id_number = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

echo "Total records for user $user_id: " . $row['total'] . "<br>";

// Show all records for this user
$sql = "SELECT * FROM sitin_report WHERE id_number = ? ORDER BY login_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Login Time</th><th>Logout Time</th><th>Lab</th><th>Purpose</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['login_time'] . "</td>";
    echo "<td>" . $row['logout_time'] . "</td>";
    echo "<td>" . $row['lab'] . "</td>";
    echo "<td>" . $row['purpose'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show all records in the table
echo "<h2>All Records in sitin_report</h2>";
$sql = "SELECT * FROM sitin_report ORDER BY login_time DESC";
$result = mysqli_query($conn, $sql);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>ID Number</th><th>Login Time</th><th>Logout Time</th><th>Lab</th><th>Purpose</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['id_number'] . "</td>";
    echo "<td>" . $row['login_time'] . "</td>";
    echo "<td>" . $row['logout_time'] . "</td>";
    echo "<td>" . $row['lab'] . "</td>";
    echo "<td>" . $row['purpose'] . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?> 