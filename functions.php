<?php
function getStudentData($idNo, $conn) {
    $sql = "SELECT * FROM info WHERE id_number = '$idNo'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

function getAllStudents($conn) {
    $sql = "SELECT * FROM info";
    $result = mysqli_query($conn, $sql);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function addStudentToSitIn($idNo, $purpose, $lab, $conn) {
    $sql = "INSERT INTO sitin (id_number, purpose, lab, status) VALUES ('$idNo', '$purpose', '$lab', 'active')";
    return mysqli_query($conn, $sql);
}

function getCurrentSitInStudents($conn) {
    $sql = "SELECT s.*, i.first_name, i.last_name, i.sessions FROM sitin s JOIN info i ON s.id_number = i.id_number WHERE s.status = 'active'";
    $result = mysqli_query($conn, $sql);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function removeStudentFromSitIn($idNo, $conn) {
    $sql = "UPDATE sitin SET status = 'inactive' WHERE id_number = '$idNo' AND status = 'active'";
    mysqli_query($conn, $sql);

    $sql = "INSERT INTO sitin_report (id_number, purpose, lab, logout_time) SELECT id_number, purpose, lab, NOW() FROM sitin WHERE id_number = '$idNo' AND status = 'inactive'";
    return mysqli_query($conn, $sql);
}

function getAnnouncements($conn) {
    $sql = "SELECT * FROM announcements ORDER BY date_posted DESC";
    $result = mysqli_query($conn, $sql);
    $announcements = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    return $announcements;
}
?>
