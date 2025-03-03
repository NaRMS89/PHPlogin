<?php
include("database.php");

$student_id = $_GET['id'];

// Remove from current sit-ins
$sql = "DELETE FROM current_sit_ins WHERE id_number = '$student_id'";
if (mysqli_query($conn, $sql)) {
    // Decrement session count
    $sql = "UPDATE info SET sessions = sessions - 1 WHERE id_number = '$student_id'";
    mysqli_query($conn, $sql);

    // Add to sit-in records
    $sql = "INSERT INTO sit_in_records (id_number) VALUES ('$student_id')";
    mysqli_query($conn, $sql);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
