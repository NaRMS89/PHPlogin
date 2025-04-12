<?php
include("../includes/database.php");

if (isset($_GET['term'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_GET['term']);
    
    // Search by ID number or name
    $sql = "SELECT * FROM info WHERE 
            id_number LIKE '%$searchTerm%' OR 
            first_name LIKE '%$searchTerm%' OR 
            last_name LIKE '%$searchTerm%'";
            
    $result = mysqli_query($conn, $sql);
    
    $students = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = array(
                'id_number' => $row['id_number'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'course' => $row['course']
            );
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($students);
} else {
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'No search term provided'));
}

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?> 