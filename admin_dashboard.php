<?php
session_start();
include("database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Admin Dashboard</h2>
            <button id="searchBtn" class="sidebar-button">Search</button>
            <button id="listStudentsBtn" class="sidebar-button">List of Students</button>
            <button id="viewCurrentSitInBtn" class="sidebar-button">View Current Sit-in</button>
            <button id="viewSitInRecordsBtn" class="sidebar-button">View Sit-in Records</button>
            <button id="sitInReportsBtn" class="sidebar-button">Sit-in Reports</button>
            <button id="createAnnouncementBtn" class="sidebar-button">Create Announcement</button>
            <button id="viewStatisticsBtn" class="sidebar-button">View Statistics</button>
            <button id="dailyStatusBtn" class="sidebar-button">Daily Status</button>
            <button id="viewFeedbackReportsBtn" class="sidebar-button">View Feedback/Reports</button>
            <button id="viewReservationApprovalBtn" class="sidebar-button">View Reservation/Approval</button>
            <button id="resetSessionBtn" class="sidebar-button">Reset Session</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <button type="submit" name="logout" class="sidebar-button">Logout</button>
            </form>
        </div>
        <div class="main-content">
            <div id="dynamicContent">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        var buttons = document.querySelectorAll('.sidebar-button');

        buttons.forEach(function(button) {
            button.onclick = function() {
                var contentId = button.id.replace('Btn', 'Content');
                loadContent(contentId);
            }
        });

        function loadContent(contentId) {
            var content = '';
            switch(contentId) {
                case 'searchContent':
                    content = '<p>Search functionality goes here...</p>';
                    break;
                case 'listStudentsContent':
                    content = '<p>List of students goes here...</p>';
                    break;
                case 'viewCurrentSitInContent':
                    content = '<p>View current sit-in goes here...</p>';
                    break;
                case 'viewSitInRecordsContent':
                    content = '<p>View sit-in records goes here...</p>';
                    break;
                case 'sitInReportsContent':
                    content = '<p>Sit-in reports go here...</p>';
                    break;
                case 'createAnnouncementContent':
                    content = `
                        <h2>Create Announcement</h2>
                        <form id="announcementForm" action="update_announcement.php" method="post">
                            <textarea name="announcement" rows="4" cols="50" required></textarea>
                            <button type="submit">Save Announcement</button>
                        </form>
                    `;
                    break;
                case 'viewStatisticsContent':
                    content = '<p>View statistics goes here...</p>';
                    break;
                case 'dailyStatusContent':
                    content = '<p>Daily status goes here...</p>';
                    break;
                case 'viewFeedbackReportsContent':
                    content = '<p>View feedback/reports goes here...</p>';
                    break;
                case 'viewReservationApprovalContent':
                    content = '<p>View reservation/approval goes here...</p>';
                    break;
                case 'resetSessionContent':
                    content = '<p>Reset session functionality goes here...</p>';
                    break;
                default:
                    content = '<p>Content not found.</p>';
            }
            document.getElementById('dynamicContent').innerHTML = content;
        }
    </script>
</body>
</html>
