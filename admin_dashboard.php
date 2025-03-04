<?php
session_start();
include("includes/database.php");

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
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Admin Dashboard</h2>
            <button id="homeBtn" class="sidebar-button">Home</button>
            <button id="searchBtn" class="sidebar-button">Search</button>
            <button id="studentBtn" class="sidebar-button">Student</button>
            <button id="sitinBtn" class="sidebar-button">Sit-in</button>
            <button id="viewSitInBtn" class="sidebar-button">View Sit-in</button>
            <button id="sitInReportBtn" class="sidebar-button">Sit-in Report</button>
            <button id="feedbackReservationBtn" class="sidebar-button">Feedback Reservation</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <button type="submit" name="logout" class="sidebar-button">Logout</button>
            </form>
        </div>
        <div class="main-content">
            <div id="dynamicContent">
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
                case 'homeContent':
                    content = `
                        <div class="home-left">
                            <h3>Student Registered: <span id="totalUsers"></span></h3>
                            <h3>Current Sit-in: <span id="currentSitIn"></span></h3>
                            <h3>Total Sit-in: <span id="totalSitIn"></span></h3>
                        </div>
                        <div class="home-right">
                            <h3>Announcement</h3>
                            <form id="announcementForm" action="update_announcement.php" method="post">
                                <textarea name="announcement" rows="4" cols="50" required></textarea>
                                <button type="submit">Submit</button>
                            </form>
                            <h3>Posted Announcements</h3>
                            <div class="announcement-list" id="announcementList">
                                </div>
                        </div>
                    `;
                    break;
                case 'searchContent':
                    content = '<p>Search functionality goes here...</p>';
                    break;
                case 'studentContent':
                    content = '<p>List of students goes here...</p>';
                    break;
                case 'sitinContent':
                    content = '<p>Sit-in management goes here...</p>';
                    break;
                case 'viewSitInContent':
                    content = '<p>View current sit-in goes here...</p>';
                    break;
                case 'sitInReportContent':
                    content = '<p>Sit-in reports go here...</p>';
                    break;
                case 'feedbackReservationContent':
                    content = '<p>View feedback/reports and reservations goes here...</p>';
                    break;
                default:
                    content = '<p>Content not found.</p>';
            }
            document.getElementById('dynamicContent').innerHTML = content;
            if (contentId === 'homeContent') {
                loadHomeData();
            }
        }

        function loadHomeData() {
            // Fetch and display the total users, current sit-in, and total sit-in
            document.getElementById('totalUsers').innerText = '100'; // Replace with actual data
            document.getElementById('currentSitIn').innerText = '5'; // Replace with actual data
            document.getElementById('totalSitIn').innerText = '50'; // Replace with actual data

            // Fetch and display the announcements
            var announcementList = document.getElementById('announcementList');
            announcementList.innerHTML = `
                <div class="announcement-item">
                    <p>CCS Admin / 2025 Feb 25</p>
                    <p>Sample announcement text...</p>
                </div>
                `;
        }
    </script>
</body>
</html>