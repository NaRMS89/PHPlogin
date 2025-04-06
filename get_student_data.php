<?php
// filepath: c:\xampp\htdocs\WEBSITE\get_student_data.php
include("../includes/database.php");

if (isset($_GET['id'])) {
    $idNo = mysqli_real_escape_string($conn, $_GET['id']);
    
    $sql = "SELECT * FROM info WHERE id_number = '$idNo'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $student = array(
            'id_number' => $row['id_number'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'course' => $row['course'],
            'sessions' => $row['sessions']
        );
        
        header('Content-Type: application/json');
        echo json_encode($student);
    } else {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'Student not found'));
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'No ID provided'));
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>