<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No ID provided']);
    exit();
}

$idNo = $_GET['id'];

// Prepare statement to prevent SQL injection
$sql = "SELECT COUNT(*) as count FROM sitin WHERE id_number = ? AND status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $idNo);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode(['exists' => $row['count'] > 0]);

mysqli_close($conn);
?> 