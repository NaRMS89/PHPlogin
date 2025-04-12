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

        .top-bar {
            background: var(--background);
            padding: 2rem;
            box-shadow: 0 0.4rem 1rem var(--shadow-1);
            display: flex;
            justify-content: flex-end;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .button-container {
            display: flex;
            gap: 1.5rem;
        }

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

        .section {
            background: var(--background);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 0.4rem 1rem var(--shadow-1);
        }

        .section-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .section-header h2 {
            font-size: 2.4rem;
            font-weight: 600;
            background: linear-gradient(to right, #FF9800, #FFB74D);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .profile-header {
            text-align: center;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 2rem auto;
            border: 3px solid var(--primary);
            box-shadow: 0 0 15px var(--primary);
        }

        .profile-info {
            margin-top: 2rem;
        }

        .profile-info p {
            margin: 1rem 0;
            font-size: 1.6rem;
        }

        .announcement-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }

        .announcement-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .announcement-item p {
            margin: 0.5rem 0;
        }

        #rulesContent {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
        }

        #rulesContent ol {
            padding-left: 2rem;
        }

        #rulesContent li {
            margin: 1rem 0;
        }

        .modal-content {
            background: var(--background);
            border-radius: 1rem;
            padding: 3rem;
            width: 90%;
            max-width: 600px;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            box-shadow: 0 0 30px var(--shadow-2);
        }

        .editable-input {
            width: 100%;
            padding: 1rem;
            margin: 0.5rem 0;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--light);
            transition: all 0.3s ease;
        }

        .editable-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        .modal__btn {
            padding: 1rem 2rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .modal__btn:hover {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-2px);
        }

        .modal__btn:active {
            transform: translateY(0);
        }

        .modal__btn:focus {
            outline: none;
            background: transparent;
        }

        /* Custom scrollbar */
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
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="button-container">
            <button class="modal__btn" onclick="openModal('editProfileModal')">Edit Profile</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                <button type="submit" name="logout" class="modal__btn">Logout</button>
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
                <h2>ANNOUNCEMENT</h2>
            </div>
            <div id="announcementList" class="announcement-list">
                <!-- Announcements will be loaded here -->
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

    <div class="modal-content" id="editProfileModal" style="background: var(--background); color: var(--light); height: 600px; margin: auto; padding: 2rem; border-radius: 0.8rem; overflow-y: auto; display: none; flex-direction: column;">
        <div class="sidebar">
            <button id="homeBtn" class="sidebar-button">Home</button>
            <button id="profileBtn" class="sidebar-button">Profile</button>
            <button id="reservationBtn" class="sidebar-button">Reservation</button>
            <button id="historyBtn" class="sidebar-button">History</button>
            <button id="logoutBtn" class="sidebar-button">Logout</button>
        </div>
        <div class="profile-header" style="text-align: center; margin-left: 20px;">
            
            <form id="userInfoForm" method="post" enctype="multipart/form-data">
                <div style="margin-top: 10px;"></div>
                <div class="profile-picture-container" onclick="document.getElementById('imageInput').click()">
                    <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                    <div class="profile-picture-overlay">
                        <span>Change Photo</span>
                    </div>
                </div>
                <input type="file" id="imageInput" name="profile_picture" accept="image/*" style="display: none;">
                <table style="width: 100%; margin-top: 1px;">
                    <tr>
                        <th style="color: white;">ID Number</th>
                        <td><input type="text" name="id_number" value="<?php echo $user_data['id_number']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; background-color: #001f3f; padding: 10px; border-radius: 5px;" readonly></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Last Name</th>
                        <td><input type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">First Name</th>
                        <td><input type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Middle Name</th>
                        <td><input type="text" name="middle_name" value="<?php echo $user_data['middle_name']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Course</th>
                        <td><input type="text" name="course" value="<?php echo $user_data['course']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Year Level</th>
                        <td><input type="text" name="year_level" value="<?php echo $user_data['year_level']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Email</th>
                        <td><input type="email" name="email" value="<?php echo $user_data['email']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; padding: 10px; border-radius: 5px; width: 100%;"></td>
                    </tr>
                    <tr>
                        <th style="color: white;">Sessions Remaining</th>
                        <td><input type="text" name="sessions" value="<?php echo $user_data['sessions']; ?>" class="editable-input" required style="color: white; border: 1px solid #4CAF50; background-color: #001f3f; padding: 10px; border-radius: 5px; width: 100%;" readonly></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="button-wrapper" style="margin-top: 0;">
                                <button type="button" onclick="closeModal()" class="modal__btn">Cancel</button>
                                <button type="submit" class="modal__btn" id="saveButton">Save Changes</button>
                            </div>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

    <!-- Reservation Content -->
    <div id="reservationContent" style="display: none;">
        <h2>Lab Reservation & Schedules</h2>
        
        <!-- Lab Status Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Lab Room Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
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
                        $status_class = ($status == 'Full') ? 'text-danger' : 'text-success';
                        
                        echo "<div class='col-md-4 mb-3'>";
                        echo "<div class='card'>";
                        echo "<div class='card-body'>";
                        echo "<h5 class='card-title'>Lab " . htmlspecialchars($room) . "</h5>";
                        echo "<p class='card-text'>Current Occupancy: " . $current_occupancy . "/50</p>";
                        echo "<p class='card-text " . $status_class . "'>Status: " . $status . "</p>";
                        echo "<button class='btn btn-primary btn-sm' onclick='viewLabSchedule(\"" . $room . "\")'>View Schedule</button>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Reservation Form -->
        <div class="card">
            <div class="card-header">
                <h5>Make a Reservation</h5>
            </div>
            <div class="card-body">
                <form id="reservationForm" action="make_reservation.php" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="labRoom" class="form-label">Lab Room</label>
                            <select class="form-select" id="labRoom" name="lab" required>
                                <option value="">Select Lab Room</option>
                                <?php
                                foreach ($lab_rooms as $room) {
                                    echo "<option value='" . $room . "'>Lab " . $room . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reservationDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="reservationDate" name="date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startTime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="startTime" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="endTime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="endTime" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Reservation</button>
                </form>
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
    </div>

    <!-- History Content -->
    <div id="historyContent" style="display: none;">
        <h2>Sit-in History</h2>
        <div class="card">
            <div class="card-header">
                <h5>Your Sit-in Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="historyTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Lab Room</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration</th>
                                <th>Purpose</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query to get user's sit-in history
                            $history_sql = "SELECT * FROM sitin_report WHERE id_number = '$user_id' ORDER BY date DESC, start_time DESC";
                            $history_result = mysqli_query($conn, $history_sql);
                            
                            if ($history_result && mysqli_num_rows($history_result) > 0) {
                                while ($history_row = mysqli_fetch_assoc($history_result)) {
                                    // Calculate duration
                                    $start_time = strtotime($history_row['start_time']);
                                    $end_time = strtotime($history_row['end_time']);
                                    $duration = round(($end_time - $start_time) / 3600, 1); // in hours
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($history_row['date']) . "</td>";
                                    echo "<td>Lab " . htmlspecialchars($history_row['lab']) . "</td>";
                                    echo "<td>" . htmlspecialchars($history_row['start_time']) . "</td>";
                                    echo "<td>" . htmlspecialchars($history_row['end_time']) . "</td>";
                                    echo "<td>" . $duration . " hours</td>";
                                    echo "<td>" . htmlspecialchars($history_row['purpose']) . "</td>";
                                    echo "<td>" . htmlspecialchars($history_row['status']) . "</td>";
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
        </div>
    </div>
    <script>
        function loadAnnouncements() {
            var announcementList = document.getElementById('announcementList');
            announcementList.innerHTML = ''; // Clear existing announcements

            fetch('../ADMIN/get_announcements.php')
            .then(response => response.json())
            .then(data => {
                // Limit to 5 announcements
                data.slice(0, 5).forEach(announcement => {
                    announcementList.innerHTML += `
                        <div class="announcement-item">
                            <p>${announcement.admin_name} / ${announcement.date_posted}</p>
                            <p>${announcement.announcement_text.replace('undefined', 'CSS ADMIN')}</p>
                        </div>
                    `;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                announcementList.innerHTML = '<p>Error loading announcements.</p>';
            });
        }

        function showHome() {
            document.getElementById('homeContent').style.display = 'block';
            document.getElementById('editProfileModal').style.display = 'none';
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex'; // Show the modal
            document.getElementById('editButton').style.display = 'none'; // Hide edit button when modal is open
            toggleEdit(); // Enable editing when modal opens
        }

        function closeModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        function toggleEdit() {
            const inputs = document.querySelectorAll('.editable-input');
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');

            // Initially disable editing
            inputs.forEach(input => {
                input.readOnly = true;
            });

            inputs.forEach(input => {
                input.readOnly = !input.readOnly;
            });
            editButton.style.display = 'none'; // Hide edit button
            saveButton.style.display = 'block'; // Show save button

            // Enable editing
            inputs.forEach(input => {
                input.readOnly = false;
            });
        }

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

        // Load announcements on page load
        loadAnnouncements();

        // Add event listener for history button
        document.getElementById('historyBtn').addEventListener('click', function() {
            loadContent('historyContent');
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
