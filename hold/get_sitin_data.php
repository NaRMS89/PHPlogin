<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch sit-in data with feedback
$query = "SELECT sr.*, f.feedback_text, f.feedback_date 
         FROM sitin_report sr 
         LEFT JOIN feedback f ON sr.id = f.sitin_id 
         ORDER BY sr.logout_time DESC";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
    echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
    echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
    echo "<td>" . htmlspecialchars($row['logout_time']) . "</td>";
    echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>";
    if (!empty($row['feedback_text'])) {
        echo "<button class='btn btn-info' onclick='viewFeedback(" . $row['id'] . ")'>View Feedback</button>";
    } else {
        echo "No Feedback";
    }
    echo "</td>";
    echo "<td>" . htmlspecialchars($row['feedback_date']) . "</td>";
    echo "</tr>";
}
?> 