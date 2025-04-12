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

// Lab rooms
$lab_rooms = ['524', '526', '528', '530', '542', 'Mac Lab'];
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
            font-size: 16px;
        }

        .content-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .dynamic-content {
            display: none;
            width: 100%;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .dynamic-content.active {
            display: block;
        }

        /* Home Content Styles */
        .section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-info {
            margin-bottom: 1.5rem;
        }

        .profile-info h2 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .profile-info p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .announcement-list {
            margin-top: 1rem;
        }

        .announcement-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .rules-list {
            margin-top: 1rem;
        }

        .rules-list ol {
            padding-left: 2rem;
        }

        .rules-list li {
            margin-bottom: 0.5rem;
        }

        /* Profile Content Styles */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .change-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--light);
        }

        /* Reservation Content Styles */
        .reservation-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .lab-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .lab-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .lab-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .reservation-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* History Content Styles */
        .history-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            color: var(--primary);
            font-weight: 500;
        }

        /* Navigation Buttons */
        .nav-btn {
            background: transparent;
            color: var(--light);
            border: 1px solid var(--border-color);
            padding: 0.8rem 1.5rem;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin: 0 0.5rem;
        }

        .nav-btn:hover,
        .nav-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .lab-grid {
                grid-template-columns: 1fr;
            }

            .content-container {
                padding: 1rem;
            }

            .dynamic-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="button-container">
            <button class="nav-btn active" onclick="switchContent('homeContent', this)">Home</button>
            <button class="nav-btn" onclick="switchContent('profileContent', this)">Profile</button>
            <button class="nav-btn" onclick="switchContent('reservationContent', this)">Reservation</button>
            <button class="nav-btn" onclick="switchContent('historyContent', this)">History</button>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                <button type="submit" name="logout" class="nav-btn">Logout</button>
            </form>
        </div>
    </header>

    <div class="content-container">
        <!-- Home Content -->
        <div id="homeContent" class="dynamic-content active">
            <div class="section">
                <div class="profile-info">
                    <h2>My Profile</h2>
                    <p>Name: <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                    <p>Student ID: <?php echo htmlspecialchars($user_data['id_number']); ?></p>
                    <p>Course: <?php echo htmlspecialchars($user_data['course']); ?></p>
                    <p>Year Level: <?php echo htmlspecialchars($user_data['year_level']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p>Sessions Remaining: <?php echo $user_data['sessions']; ?></p>
                    <a href="#editProfileModal" class="link-1">Edit Profile</a>
                </div>
            </div>
            <div class="section">
                <h2>Announcements</h2>
                <div id="announcementList" class="announcement-list">
                    <!-- Announcements will be loaded here -->
                </div>
            </div>
            <div class="section">
                <h2>Rules</h2>
                <div class="rules-list">
                    <ol>
                        <li>Students must present their ID upon entry.</li>
                        <li>Food and drinks are not allowed inside the laboratory.</li>
                        <li>Keep noise to a minimum.</li>
                        <li>Do not install unauthorized software.</li>
                        <li>Report any technical issues to the laboratory staff.</li>
                        <li>Clean your workspace before leaving.</li>
                        <li>Save your work before leaving.</li>
                        <li>Follow the laboratory schedule.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div id="profileContent" class="dynamic-content">
            <div class="profile-card">
                <div class="profile-header">
                    <h2>Edit Profile</h2>
                    <div class="profile-picture-container" onclick="document.getElementById('imageInput').click()">
                        <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                        <div class="profile-picture-overlay">
                            <span>Change Photo</span>
                        </div>
                    </div>
                    <input type="file" id="imageInput" name="profile_picture" accept="image/*" style="display: none;">
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
                    <button type="submit" class="nav-btn">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Reservation Content -->
        <div id="reservationContent" class="dynamic-content">
            <div class="reservation-container">
                <h2>Lab Reservation</h2>
                <div class="lab-grid">
                    <?php foreach ($lab_rooms as $room): 
                        $occupancy_sql = "SELECT COUNT(*) as count FROM sitin WHERE lab = '$room' AND status = 'active'";
                        $occupancy_result = mysqli_query($conn, $occupancy_sql);
                        $occupancy_row = mysqli_fetch_assoc($occupancy_result);
                        $current_occupancy = $occupancy_row['count'];
                        $status = ($current_occupancy >= 50) ? 'Full' : 'Available';
                    ?>
                    <div class="lab-card">
                        <h3>Lab <?php echo htmlspecialchars($room); ?></h3>
                        <p class="lab-status">Current Occupancy: <?php echo $current_occupancy; ?>/50</p>
                        <p class="lab-status <?php echo strtolower($status); ?>">Status: <?php echo $status; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <form id="reservationForm" class="reservation-form" action="make_reservation.php" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="labRoom">Lab Room</label>
                            <select class="form-control" id="labRoom" name="lab" required>
                                <option value="">Select Lab Room</option>
                                <?php foreach ($lab_rooms as $room): ?>
                                <option value="<?php echo $room; ?>">Lab <?php echo $room; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reservationDate">Date</label>
                            <input type="date" class="form-control" id="reservationDate" name="date" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startTime">Start Time</label>
                            <input type="time" class="form-control" id="startTime" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="endTime">End Time</label>
                            <input type="time" class="form-control" id="endTime" name="end_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="purpose">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="nav-btn">Submit Reservation</button>
                </form>
            </div>
        </div>

        <!-- History Content -->
        <div id="historyContent" class="dynamic-content">
            <div class="history-card">
                <h2>Reservation History</h2>
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
                        <!-- History data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-container">
        <div class="modal">
            <h2 class="modal__title">Edit Profile</h2>
            <a href="#" class="link-2"></a>
            <form id="userInfoForm" class="modal__content">
                <div class="profile-picture-container" onclick="document.getElementById('imageInput').click()">
                    <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                    <div class="profile-picture-overlay">
                        <span>Change Photo</span>
                    </div>
                </div>
                <input type="file" id="imageInput" name="profile_picture" accept="image/*" style="display: none;">
                
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
                <div class="modal__actions">
                    <button type="submit" class="modal__btn">Save Changes</button>
                    <a href="#" class="modal__btn">Cancel</a>
                </div>
            </form>
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
            announcementList.innerHTML = '';

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

        // Update the view schedule function to use a more modern approach
        function viewLabSchedule(room) {
            // Instead of modal, we'll update the content directly in the reservation section
            const scheduleContainer = document.createElement('div');
            scheduleContainer.className = 'schedule-container';
            
            fetch('get_lab_schedule.php?room=' + room)
                .then(response => response.text())
                .then(html => {
                    const currentContent = document.querySelector('.lab-status-grid');
                    scheduleContainer.innerHTML = html;
                    currentContent.parentNode.insertBefore(scheduleContainer, currentContent.nextSibling);
                })
                .catch(error => {
                    console.error('Error:', error);
                    scheduleContainer.innerHTML = '<div class="error-message">Error loading schedule data</div>';
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

        // Function to switch between content sections
        function switchContent(contentId, button) {
            // Hide all content sections
            document.querySelectorAll('.dynamic-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected content section
            const selectedContent = document.getElementById(contentId);
            selectedContent.classList.add('active');
            
            // Update active button state
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Load specific content data if needed
            if (contentId === 'historyContent') {
                loadHistoryData();
            } else if (contentId === 'reservationContent') {
                // Reset any previous schedule views
                const existingSchedule = document.querySelector('.schedule-container');
                if (existingSchedule) {
                    existingSchedule.remove();
                }
            }
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set home content and button as active by default
            document.getElementById('homeContent').classList.add('active');
            document.querySelector('button[onclick*="homeContent"]').classList.add('active');
            
            // Load initial data
            loadAnnouncements();
        });

        // Add styles for active button state
        const style = document.createElement('style');
        style.textContent = `
            .nav-btn {
                background: transparent;
                color: var(--light);
                border: 1px solid var(--border-color);
                padding: 0.8rem 1.5rem;
                border-radius: 2rem;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 1.4rem;
                margin: 0 0.5rem;
            }

            .nav-btn:hover {
                background: var(--primary);
                border-color: var(--primary);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px var(--shadow-1);
            }

            .nav-btn.active {
                background: var(--primary);
                border-color: var(--primary);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px var(--shadow-1);
            }

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
                align-items: center;
                gap: 1rem;
                max-width: 1400px;
                margin: 0 auto;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
