<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $query = "SELECT 
                sr.id,
                sr.student_id,
                CONCAT(s.first_name, ' ', s.last_name) as student_name,
                sr.date,
                sr.time,
                sr.lab,
                sr.pc_number,
                sr.purpose,
                sr.status
              FROM sitin_requests sr
              JOIN students s ON sr.student_id = s.student_id
              ORDER BY sr.date DESC, sr.time DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($requests);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 