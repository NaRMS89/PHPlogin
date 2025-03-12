<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $announcement = filter_input(INPUT_POST, "announcement", FILTER_SANITIZE_SPECIAL_CHARS);

    $sql = "INSERT INTO announcements (announcement_text, date_posted) VALUES (?, CURDATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $announcement);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Announcement posted successfully.";
    } else {
        $response['success'] = false;
        $response['message'] = "Error posting announcement: " . $stmt->error;
    }
} else {
    $response['success'] = false;
    $response['message'] = "Invalid request method.";
}

header('Content-Type: application/json');
echo json_encode($response);

if ($conn instanceof mysqli) {
    $conn->close();
}
?>
