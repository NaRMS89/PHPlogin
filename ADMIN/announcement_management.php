<?php
session_start();
include("../includes/database.php");

// Validate database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to get announcements
if (isset($_GET["get_announcements"])) {
    $sql = "SELECT announcement_text, date_posted FROM announcements ORDER BY date_posted DESC";
    if ($conn) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $announcements = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $announcements[] = $row;
            }
            echo json_encode($announcements);
        } else {
            echo json_encode(["error" => "Failed to fetch announcements"]);
        }
    } else {
        echo json_encode(["error" => "Database connection failed"]);
    }
}

// Function to update an announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_announcement"])) {
    $announcementText = mysqli_real_escape_string($conn, $_POST['announcement_text']);
    $sql = "INSERT INTO announcements (announcement_text) VALUES ('$announcementText')";
    if ($conn && mysqli_query($conn, $sql)) {
        echo "Announcement updated successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>
