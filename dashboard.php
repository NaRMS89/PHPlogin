<?php
session_start();

if (!isset($_SESSION['user_data'])) {
    header("Location: index.php");
    exit();
}

$user_data = $_SESSION['user_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Welcome, <?php echo $user_data['last_name'] . ' ' . $user_data['first_name'] . ' ' . $user_data['middle_name']; ?>!</h2>
            <p>Sessions Remaining: <?php echo $user_data['sessions']; ?></p>
            <button id="userInfoBtn" class="sidebar-button">User Info</button>
            <button type="button" id="announcementBtn" class="sidebar-button">Announcement</button>
            <button type="button" id="remainingSessionsBtn" class="sidebar-button">Remaining Sessions</button>
            <button type="button" id="sitInRulesBtn" class="sidebar-button">Sit-in Rules</button>
            <button type="button" id="labRulesBtn" class="sidebar-button">Lab Rules & Regulations</button>
            <button type="button" id="sitInHistoryBtn" class="sidebar-button">Sit-in History</button>
            <button type="button" id="reservationBtn" class="sidebar-button">Reservation</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <button type="submit" name="logout" class="sidebar-button">Logout</button>
            </form>
        </div>
        <div class="main-content">
            <!-- The Modal -->
            <div id="userInfoModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>User Information</h2>
                    <form id="userInfoForm" action="update_user.php" method="post" enctype="multipart/form-data">
                        <table>
                            <tr>
                                <th>ID Number</th>
                                <td><input type="text" name="id_number" value="<?php echo $user_data['id_number']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Last Name</th>
                                <td><input type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>First Name</th>
                                <td><input type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Middle Name</th>
                                <td><input type="text" name="middle_name" value="<?php echo $user_data['middle_name']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Course</th>
                                <td><input type="text" name="course" value="<?php echo $user_data['course']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Year Level</th>
                                <td><input type="text" name="year_level" value="<?php echo $user_data['year_level']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><input type="text" name="email" value="<?php echo $user_data['email']; ?>" class="readonly-input" readonly></td>
                            </tr>
                            <tr>
                                <th>Profile Picture</th>
                                <td>
                                    <img src="uploads/<?php echo $user_data['profile_picture'] ?? 'default.png'; ?>" alt="Profile Picture" class="profile-picture">
                                    <input type="file" name="profile_picture" accept="image/*">
                                </td>
                            </tr>
                        </table>
                        <div class="button-wrapper">
                            <button type="button" id="editBtn" class="logout-button">Edit</button>
                            <button type="submit" id="saveBtn" class="logout-button" style="display:none;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modals for other buttons -->
            <div id="announcementModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Announcement content goes here...</p>
                </div>
            </div>

            <div id="remainingSessionsModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Remaining sessions content goes here...</p>
                </div>
            </div>

            <div id="sitInRulesModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Sit-in rules content goes here...</p>
                </div>
            </div>

            <div id="labRulesModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Lab rules and regulations content goes here...</p>
                </div>
            </div>

            <div id="sitInHistoryModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Sit-in history content goes here...</p>
                </div>
            </div>

            <div id="reservationModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>Reservation content goes here...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        var editBtn = document.getElementById("editBtn");
        var saveBtn = document.getElementById("saveBtn");
        var inputs = document.querySelectorAll("#userInfoForm input[type='text'], #userInfoForm input[type='file']");

        editBtn.onclick = function() {
            inputs.forEach(function(input) {
                input.classList.remove("readonly-input");
                input.removeAttribute("readonly");
            });
            editBtn.style.display = "none";
            saveBtn.style.display = "inline-block";
        }

        var modals = document.querySelectorAll('.modal');
        var closeButtons = document.querySelectorAll('.close');

        closeButtons.forEach(function(closeButton) {
            closeButton.onclick = function() {
                closeButton.parentElement.parentElement.style.display = 'none';
            }
        });

        window.onclick = function(event) {
            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        }

        document.getElementById("userInfoBtn").onclick = function() {
            document.getElementById("userInfoModal").style.display = "block";
        }

        document.getElementById("announcementBtn").onclick = function() {
            document.getElementById("announcementModal").style.display = "block";
        }

        document.getElementById("remainingSessionsBtn").onclick = function() {
            document.getElementById("remainingSessionsModal").style.display = "block";
        }

        document.getElementById("sitInRulesBtn").onclick = function() {
            document.getElementById("sitInRulesModal").style.display = "block";
        }

        document.getElementById("labRulesBtn").onclick = function() {
            document.getElementById("labRulesModal").style.display = "block";
        }

        document.getElementById("sitInHistoryBtn").onclick = function() {
            document.getElementById("sitInHistoryModal").style.display = "block";
        }

        document.getElementById("reservationBtn").onclick = function() {
            document.getElementById("reservationModal").style.display = "block";
        }
    </script>
</body>
</html>