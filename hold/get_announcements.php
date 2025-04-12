<?php
include("../includes/database.php"); // Adjust the path if necessary

$announcements = array();

$sql = "SELECT * FROM announcements ORDER BY date_posted DESC, announcement_id DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
} else {
    error_log("Error fetching announcements: " . mysqli_error($conn));
    echo json_encode(array("error" => "Error fetching announcements from the database."));
    exit;
}

header('Content-Type: application/json');
echo json_encode($announcements);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>