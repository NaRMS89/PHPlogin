<?php
include("../includes/database.php");
header('Content-Type: application/json');

if (isset($_GET['id_number'])) {
    $idNo = $_GET['id_number'];
    // Fetch basic student info
    $stmt = $conn->prepare("SELECT i.id_number, i.first_name, i.last_name, i.sessions FROM info i WHERE i.id_number = ?");
    $stmt->bind_param("s", $idNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $student = $row;
        // Fetch total points
        $stmt2 = $conn->prepare("SELECT SUM(points) as total_points, COUNT(*) as award_count FROM points WHERE id_number = ?");
        $stmt2->bind_param("s", $idNo);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $pointsData = $result2->fetch_assoc();
        $student['total_points'] = $pointsData['total_points'] ?? 0;
        $student['award_count'] = $pointsData['award_count'] ?? 0;
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No student ID provided.']);
}
?>
