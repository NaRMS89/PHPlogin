<?php
session_start();
include("database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $announcement = filter_input(INPUT_POST, "announcement", FILTER_SANITIZE_SPECIAL_CHARS);

    $sql = "UPDATE announcements SET content = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $announcement);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error updating announcement: " . $stmt->error;
    }

    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
