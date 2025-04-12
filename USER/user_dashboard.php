<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header("Location: index.php");
    exit();
}


include("../includes/database.php");

// Function to retrieve announcements from the database
function getAnnouncements($conn) {
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    $announcements = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    return $announcements;
}

$announcements = getAnnouncements($conn);

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
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="topnav">
            <button onclick="location.href='user_dashboard.php'">Home</button>
            <button id="announcementsBtn">Announcements</button>
            <button id="reservationsBtn">Reservations</button>
            <button id="feedbackBtn">Feedback</button>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
        <main class="main-content">
            <div id="dynamicContent">
                <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
                <p>This is your dashboard. You can view announcements, make reservations, and submit feedback.</p>
            </div>
        </main>
    </div>

    <div id="announcementsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('announcementsModal')">&times;</span>
            <h2>Announcements</h2>
            <div class="announcement-list">
                <?php if (empty($announcements)): ?>
                    <p>No announcements available.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item">
                            <p><?php echo date('Y-m-d H:i:s', strtotime($announcement['created_at'])); ?></p>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('announcementsBtn').addEventListener('click', function() {
            openModal('announcementsModal');
        });

        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            if (event.target.className == 'modal') {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>
