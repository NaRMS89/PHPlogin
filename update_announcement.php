<?php
session_start();
include("includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $announcement = mysqli_real_escape_string($conn, $_POST['announcement']);
    $sql = "INSERT INTO announcements (content) VALUES ('$announcement')";
    if (mysqli_query($conn, $sql)) {
        $announcement_success = "Announcement posted successfully!";
        header("Location: ADMIN/admin_dashboard.php?announcement_success=" . urlencode($announcement_success));
        exit();
    } else {
        $announcement_error = "Error posting announcement: " . mysqli_error($conn);
        header("Location: ADMIN/admin_dashboard.php?announcement_error=" . urlencode($announcement_error));
        exit();
    }
} else {
    // Redirect back to the admin dashboard if accessed directly without posting
    header("Location: ADMIN/admin_dashboard.php");
    exit();
}
?>
