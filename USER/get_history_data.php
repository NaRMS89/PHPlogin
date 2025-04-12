<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['user_id'])) {
    echo '<p>Please log in to view your history.</p>';
    exit();
}

$id_number = $_SESSION['user_id'];

// Get user's sit-in history
$sql = "SELECT s.*, i.first_name, i.last_name, s.feedback
        FROM sitin_report s 
        JOIN info i ON s.id_number = i.id_number 
        WHERE s.id_number = ? 
        ORDER BY s.login_time DESC";
        
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "s", $id_number);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>
<div class="history-content">
    <h2>My Sit-in History</h2>
    <div class="history-table">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Duration</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $login_time = new DateTime($row['login_time']);
                        $logout_time = new DateTime($row['logout_time']);
                        $duration = $logout_time->diff($login_time);
                        
                        echo "<tr>";
                        echo "<td>" . $login_time->format('Y-m-d') . "</td>";
                        echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                        echo "<td>" . $login_time->format('H:i:s') . "</td>";
                        echo "<td>" . $logout_time->format('H:i:s') . "</td>";
                        echo "<td>" . $duration->format('%h hours %i minutes') . "</td>";
                        echo "<td>";
                        if ($row['feedback']) {
                            echo "<button class='view-feedback-btn' onclick='viewFeedback(\"" . $row['id'] . "\")'>View Feedback</button>";
                        } else {
                            echo "<button class='add-feedback-btn' onclick='openFeedbackModal(\"" . $row['id'] . "\")'>Add Feedback</button>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No sit-in history found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal-container">
    <div class="modal">
        <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
        <h2 class="modal-title">Add Feedback</h2>
        <form id="feedbackForm" onsubmit="submitFeedback(event)">
            <input type="hidden" id="sitinId" name="sitin_id">
            <div class="form-group">
                <label for="feedbackText">Your Feedback:</label>
                <textarea id="feedbackText" name="feedback" required></textarea>
            </div>
            <div class="button-group">
                <button type="submit" class="modal-button primary">Submit</button>
                <button type="button" class="modal-button secondary" onclick="closeModal('feedbackModal')">Cancel</button>
            </div>
        </form>
    </div>
</div> 