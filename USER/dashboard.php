<?php
session_start();
error_reporting(E_ALL); // Enable error reporting
ini_set('display_errors', 1); // Display errors on the page
include("../includes/database.php");

if (!isset($_SESSION['user_data']) || $conn === null) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM info WHERE id_number = '$user_id'";
$result = mysqli_query($conn, $sql);
$user_data = mysqli_fetch_assoc($result);
$_SESSION['user_data'] = $user_data;

// Check if sessions remaining is 0
if ($user_data['sessions'] <= 0) {
    session_unset();
    session_destroy();
    die("Your sessions have expired. Please contact support.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes'])) {
    $user_id = $_SESSION['user_data']['id_number'];
    $fields = ['last_name', 'first_name', 'middle_name', 'course', 'year_level', 'email'];
    $updates = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = filter_input(INPUT_POST, $field, FILTER_SANITIZE_SPECIAL_CHARS);
            $updates[] = "$field = '$value'";
            $_SESSION['user_data'][$field] = $value;
        }
    }

    if (!empty($updates)) {
        $sql = "UPDATE info SET " . implode(", ", $updates) . " WHERE id_number = '$user_id'";
        $response = array();
        
        if (mysqli_query($conn, $sql)) {
            $response['success'] = true;
            $response['message'] = "Profile updated successfully";
        } else {
            $response['success'] = false;
            $response['message'] = "Error updating profile: " . mysqli_error($conn);
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $response = array();
    $target_dir = "../uploads/";
    $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is a actual image or fake image
    if(getimagesize($_FILES["profile_picture"]["tmp_name"]) !== false) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Update database with new image filename
            $sql = "UPDATE info SET profile_picture = '$new_filename' WHERE id_number = '$user_id'";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['user_data']['profile_picture'] = $new_filename;
                $response['success'] = true;
                $response['message'] = "Profile picture updated successfully";
                $response['new_image'] = $new_filename;
            } else {
                $response['success'] = false;
                $response['message'] = "Error updating database";
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Error uploading file";
        }
    } else {
        $response['success'] = false;
        $response['message'] = "File is not an image";
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$user_data = $_SESSION['user_data'];
$profile_picture = !empty($user_data['profile_picture']) ? $user_data['profile_picture'] : 'default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        :root {
            --light: hsl(220, 50%, 90%);
            --primary: hsl(255, 30%, 55%);
            --focus: hsl(210, 90%, 50%);
            --border-color: hsla(0, 0%, 100%, .2);
            --global-background: hsl(220, 25%, 10%);
            --background: linear-gradient(to right, hsl(210, 30%, 20%), hsl(255, 30%, 25%));
            --shadow-1: hsla(236, 50%, 50%, .3);
            --shadow-2: hsla(236, 50%, 50%, .4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Open Sans', sans-serif;
            color: var(--light);
            background: var(--global-background);
            min-height: 100vh;
            font-size: 1.6rem;
        }

        /* Top Navigation Bar */
        .top-bar {
            background: var(--background);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px var(--shadow-1);
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-btn {
            background: transparent;
            color: var(--light);
            border: 1px solid var(--border-color);
            padding: 0.8rem 1.5rem;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.4rem;
        }

        .nav-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-1);
        }

        /* Main Content */
        .content-container {
            margin-top: 8rem;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Section Styling */
        .section {
            background: var(--background);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 0.4rem 1rem var(--shadow-1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.8rem 2rem var(--shadow-2);
        }

        /* Announcement Section */
        .announcement-list {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
        }

        .announcement-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }

        .announcement-item:hover {
            transform: translateX(5px);
            border-color: var(--primary);
        }

        .announcement-item .date {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .announcement-item .content {
            font-size: 1.4rem;
            line-height: 1.6;
        }

        /* Edit Profile Modal */
        .modal-content {
            background: var(--background);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 90%;
            max-width: 600px;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            box-shadow: 0 0 30px var(--shadow-2);
            display: none;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .modal-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: transparent;
            border: none;
            color: var(--light);
            font-size: 2rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
        }

        /* Form Controls */
        .form-control {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 0.8rem;
            color: var(--light);
            padding: 1rem;
            width: 100%;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
            outline: none;
        }

        /* Reservation and History Sections */
        .reservation-section,
        .history-section {
            background: var(--background);
            border-radius: 1.5rem;
            padding: 2.5rem;
            margin-top: 2rem;
        }

        .reservation-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        .history-table th,
        .history-table td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            background: rgba(0, 0, 0, 0.2);
            font-weight: 600;
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--focus);
        }

        .profile-picture-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 2rem auto;
            cursor: pointer;
        }

        .profile-picture-container:hover .profile-picture-overlay {
            opacity: 1;
        }

        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-picture-overlay span {
            color: var(--light);
            font-size: 1.4rem;
        }

        #imageInput {
            display: none;
        }

        /* Lab Status Grid */
        .lab-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .lab-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .lab-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px var(--shadow-1);
        }

        .lab-card h3 {
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .occupancy-info {
            margin-bottom: 1.5rem;
        }

        .occupancy-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .occupancy-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--occupancy);
            background: var(--primary);
            border-radius: 4px;
        }

        .status {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .status-available {
            color: #4CAF50;
        }

        .status-full {
            color: #f44336;
        }

        .view-schedule-btn {
            width: 100%;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        /* History Table */
        .history-table-container {
            overflow-x: auto;
            margin-top: 2rem;
        }

        .history-table {
            min-width: 800px;
        }

        /* Additional styles for the reservation form */
        .reservation-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="button-container">
            <button onclick="location.href='dashboard.php'" class="nav-btn">Home</button>
            <button onclick="location.href='profile.php'" class="nav-btn">Profile</button>
            <button onclick="location.href='reservation.php'" class="nav-btn">Reservation</button>
            <button onclick="location.href='history.php'" class="nav-btn">History</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                <button type="submit" name="logout" class="nav-btn">Logout</button>
            </form>
        </div>
    </header>

    <div class="content-container">
        <div class="section">
            <div class="section-header">
                <h2>STUDENT INFORMATION</h2>
            </div>
            <div class="profile-header">
                <div class="profile-picture-container" onclick="document.getElementById('imageInput').click()">
                    <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                    <div class="profile-picture-overlay">
                        <span>Change Photo</span>
                    </div>
                </div>
                <input type="file" id="imageInput" name="profile_picture" accept="image/*" style="display: none;">
                <div class="profile-info">
                    <h2><?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></h2>
                    <p>Course: <?php echo $user_data['course']; ?></p>
                    <p>Year: <?php echo $user_data['year_level']; ?></p>
                    <p>Email: <?php echo $user_data['email']; ?></p>
                    <p>Sessions Remaining: <?php echo $user_data['sessions']; ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>ANNOUNCEMENTS</h2>
            </div>
            <div id="announcementList" class="announcement-list">
                <!-- Announcements will be loaded here dynamically -->
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>RULES</h2>
            </div>
            <div id="rulesContent">
                <h3>Laboratory Rules and Regulations</h3>
                <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                <ol>
                    <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                    <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                    <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                    <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                    <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                    <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                    <li>Observe proper decorum while inside the laboratory.
                        <ul>
                            <li>Do not get inside the lab unless the instructor is present.</li>
                            <li>All bags, knapsacks, and the likes must be deposited at the counter.</li>
                            <li>Follow the seating arrangement of your instructor.</li>
                            <li>At the end of class, all software programs must be closed.</li>
                            <li>Return all chairs to their proper places after using.</li>
                        </ul>
                    </li>
                    <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                    <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                    <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                    <li>For serious offenses, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                    <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant, or instructor immediately.</li>
                </ol>
                <h3>DISCIPLINARY ACTION</h3>
                <p>First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</p>
                <p>Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
            </div>
        </div>
    </div>

    <div id="mainContent">
        <!-- Your main dashboard content here -->
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-content">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="userInfoForm" class="edit-profile-form">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name']); ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name']); ?>">
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($user_data['middle_name']); ?>">
            </div>
            <div class="form-group">
                <label for="course">Course</label>
                <input type="text" id="course" name="course" class="form-control" value="<?php echo htmlspecialchars($user_data['course']); ?>">
            </div>
            <div class="form-group">
                <label for="year_level">Year Level</label>
                <input type="text" id="year_level" name="year_level" class="form-control" value="<?php echo htmlspecialchars($user_data['year_level']); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="nav-btn">Save Changes</button>
                <button type="button" class="nav-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Reservation Section -->
    <div class="reservation-section">
        <div class="section-header">
            <h2>Lab Reservation</h2>
        </div>
        <div class="lab-status-grid">
            <?php
            // Lab rooms
            $lab_rooms = ['524', '526', '528', '530', '542', 'Mac Lab'];
            
            foreach ($lab_rooms as $room) {
                // Get current occupancy
                $occupancy_sql = "SELECT COUNT(*) as count FROM sitin WHERE lab = '$room' AND status = 'active'";
                $occupancy_result = mysqli_query($conn, $occupancy_sql);
                $occupancy_row = mysqli_fetch_assoc($occupancy_result);
                $current_occupancy = $occupancy_row['count'];
                
                // Determine status
                $status = ($current_occupancy >= 50) ? 'Full' : 'Available';
                $status_class = ($status == 'Full') ? 'status-full' : 'status-available';
                
                echo "<div class='lab-card'>";
                echo "<h3>Lab " . htmlspecialchars($room) . "</h3>";
                echo "<div class='occupancy-info'>";
                echo "<div class='occupancy-bar' style='--occupancy: " . ($current_occupancy * 2) . "%'></div>";
                echo "<p>Current Occupancy: " . $current_occupancy . "/50</p>";
                echo "</div>";
                echo "<p class='status " . $status_class . "'>Status: " . $status . "</p>";
                echo "<button class='nav-btn view-schedule-btn' onclick='viewLabSchedule(\"" . $room . "\")'>View Schedule</button>";
                echo "</div>";
            }
            ?>
        </div>
        
        <form id="reservationForm" class="reservation-form" action="make_reservation.php" method="post">
            <div class="form-group">
                <label for="labRoom">Lab Room</label>
                <select class="form-control" id="labRoom" name="lab" required>
                    <option value="">Select Lab Room</option>
                    <?php
                    foreach ($lab_rooms as $room) {
                        echo "<option value='" . $room . "'>Lab " . $room . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reservationDate">Date</label>
                <input type="date" class="form-control" id="reservationDate" name="date" required>
            </div>
            <div class="form-group">
                <label for="startTime">Start Time</label>
                <input type="time" class="form-control" id="startTime" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="endTime">End Time</label>
                <input type="time" class="form-control" id="endTime" name="end_time" required>
            </div>
            <div class="form-group full-width">
                <label for="purpose">Purpose</label>
                <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
            </div>
            <div class="form-actions full-width">
                <button type="submit" class="nav-btn">Submit Reservation</button>
            </div>
        </form>
    </div>

    <!-- History Section -->
    <div class="history-section">
        <div class="section-header">
            <h2>Reservation History</h2>
        </div>
        <div class="history-table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lab Room</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <!-- History data will be loaded here dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lab Schedule Modal -->
    <div class="modal fade" id="labScheduleModal" tabindex="-1" aria-labelledby="labScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="labScheduleModalLabel">Lab Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="labScheduleContent">
                        <!-- Schedule content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadContent(contentId) {
            // Hide all content sections first
            const contentSections = ['homeContent', 'historyContent', 'reservationContent'];
            contentSections.forEach(section => {
                const element = document.getElementById(section);
                if (element) {
                    element.style.display = 'none';
                }
            });

            // Show the selected content
            const selectedContent = document.getElementById(contentId);
            if (selectedContent) {
                selectedContent.style.display = 'block';
                if (contentId === 'historyContent') {
                    loadHistoryData();
                }
            }
        }

        function loadHistoryData() {
            const historyTableBody = document.getElementById('historyTableBody');
            
            fetch('get_history_data.php')
            .then(response => response.json())
            .then(data => {
                historyTableBody.innerHTML = '';
                data.forEach(record => {
                    const row = `
                        <tr>
                            <td>${record.date}</td>
                            <td>Lab ${record.lab_room}</td>
                            <td>${record.start_time} - ${record.end_time}</td>
                            <td><span class="status-badge ${record.status.toLowerCase()}">${record.status}</span></td>
                            <td>${record.purpose}</td>
                        </tr>
                    `;
                    historyTableBody.innerHTML += row;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                historyTableBody.innerHTML = '<tr><td colspan="5">Error loading history data</td></tr>';
            });
        }

        function loadAnnouncements() {
            var announcementList = document.getElementById('announcementList');
            announcementList.innerHTML = ''; // Clear existing announcements

            fetch('../ADMIN/get_announcements.php')
            .then(response => response.json())
            .then(data => {
                data.slice(0, 5).forEach(announcement => {
                    announcementList.innerHTML += `
                        <div class="announcement-item">
                            <div class="date">${announcement.admin_name} - ${announcement.date_posted}</div>
                            <div class="content">${announcement.announcement_text.replace('undefined', 'CSS ADMIN')}</div>
                        </div>
                    `;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                announcementList.innerHTML = '<div class="announcement-item">Error loading announcements.</div>';
            });
        }

        function showHome() {
            document.getElementById('homeContent').style.display = 'block';
            document.getElementById('editProfileModal').style.display = 'none';
        }

        function openModal() {
            document.getElementById('editProfileModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Load announcements when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAnnouncements();
        });

        document.getElementById('userInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('save_changes', '1');

            fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    // Refresh the displayed information without reloading
                    document.querySelector('.profile-info h2').textContent = 
                        formData.get('first_name') + ' ' + formData.get('last_name');
                    document.querySelector('.profile-info p:nth-child(2)').textContent = 
                        'Course: ' + formData.get('course');
                    document.querySelector('.profile-info p:nth-child(3)').textContent = 
                        'Year: ' + formData.get('year_level');
                    document.querySelector('.profile-info p:nth-child(4)').textContent = 
                        'Email: ' + formData.get('email');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving changes');
            });
        });

        document.getElementById('imageInput').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', this.files[0]);

                fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update all instances of the profile picture
                        const profilePics = document.querySelectorAll('.profile-picture');
                        profilePics.forEach(pic => {
                            pic.src = '../uploads/' + data.new_image + '?v=' + new Date().getTime();
                        });
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating profile picture');
                });
            }
        });

        // View lab schedule
        function viewLabSchedule(room) {
            const modal = document.getElementById('labScheduleModal');
            const modalTitle = document.getElementById('labScheduleModalLabel');
            const scheduleContent = document.getElementById('labScheduleContent');
            
            modalTitle.textContent = 'Lab ' + room + ' Schedule';
            
            // Load schedule content via AJAX
            fetch('get_lab_schedule.php?room=' + room)
                .then(response => response.text())
                .then(html => {
                    scheduleContent.innerHTML = html;
                    new bootstrap.Modal(modal).show();
                })
                .catch(error => {
                    console.error('Error loading schedule:', error);
                    scheduleContent.innerHTML = '<div class="alert alert-danger">Error loading schedule data.</div>';
                    new bootstrap.Modal(modal).show();
                });
        }

        // Set min date for reservation date to today
        document.getElementById('reservationDate').min = new Date().toISOString().split('T')[0];

        // Validate reservation form
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
            }
        });
    </script>
</body>
</html>
<?php
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
