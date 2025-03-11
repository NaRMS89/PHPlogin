<?php
// filepath: c:\xampp\htdocs\WEBSITE\get_announcements.php
<?php
include("database.php");

$announcements = array();

$sql = "SELECT announcement_text, date_posted FROM announcements ORDER BY date_posted DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
} else {
    echo "Error fetching announcements: " . mysqli_error($conn);
}

header('Content-Type: application/json');
echo json_encode($announcements);

if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>