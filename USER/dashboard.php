<?php
session_start();
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
        if (mysqli_query($conn, $sql)) {
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    }

    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    }
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
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            display: flex;
            background-color: #121212; /* Dark background */
            color: #ffffff; /* Light text */
        }
        .sidebar {
            width: 200px;
            padding: 10px;
            background-color: #1e1e1e; /* Dark sidebar */
            position: fixed;
            top: 50px; /* Below the top bar */
            bottom: 0;
        }
        .top-bar {
            width: 100%;
            padding: 10px;
            background-color: #1e1e1e; /* Dark top bar */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        main {
            margin-left: 220px; /* Space for sidebar */
            margin-top: 60px; /* Space for top bar */
            padding: 20px;
            flex-grow: 1;
        }
        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: hidden; /* Disable scroll */
            background-color: rgba(0,0,0,0.8); /* Darker background for modal */
        }
        .modal-content {
            background-color: #1e1e1e; /* Dark modal background */
            margin: 10% auto; /* 10% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 20%; /* Make modal smaller */
            max-width: 600px; /* Max width for larger screens */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header class="top-bar">
    <div class="button-container" style="float: right;">
        <button class="dashboard-button" onclick="loadContent('homeContent')">Home</button>
        <button class="dashboard-button" onclick="openModal()">Edit Profile</button>
        <button class="dashboard-button" onclick="loadContent('historyContent')">History</button>
        <button class="dashboard-button" onclick="loadContent('reservationContent')">Reservation</button>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
            <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
    </div>
</header>

<div class="sidebar">
    <div class="profile-header" style="text-align: center;">
        <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture" style="display: block; margin: 0 auto;">
        <h2><?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></h2>
        <p>Course: <?php echo $user_data['course']; ?></p>
        <p>Year: <?php echo $user_data['year_level']; ?></p>
        <p>Email: <span style="word-wrap: break-word;"><?php echo $user_data['email']; ?></span></p>
        <p>Sessions Remaining: <span style="color: #f0f0f0;"><?php echo $user_data['sessions']; ?></span></p>
    </div>
</div>

<main>

    <div id="announcementSection">
        <h3>Announcements</h3>
        <div class="announcement-list" id="announcementList">
            <!-- Announcements will be loaded here -->
        </div>
    </div>
    <div id="rulesSection" style="float: right;"> <!-- Move rules back to the right -->
        <h3>Laboratory Rules and Regulations</h3>
        <div id="rulesContent">
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
</main>

<!-- Modal for Edit Profile -->
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>User Information</h2>
        <form id="userInfoForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <table>
                <tr>
                    <td colspan="2">
                        <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                        <input type="file" name="profile_picture" accept="image/*" class="readonly-input" style="display:block;">
                    </td>
                </tr>
                <tr>
                    <th>ID Number</th>
                    <td><input type="text" name="id_number" value="<?php echo $user_data['id_number']; ?>" class="readonly-input" readonly></td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td><input type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>First Name</th>
                    <td><input type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>Middle Name</th>
                    <td><input type="text" name="middle_name" value="<?php echo $user_data['middle_name']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>Course</th>
                    <td><input type="text" name="course" value="<?php echo $user_data['course']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>Year Level</th>
                    <td><input type="text" name="year_level" value="<?php echo $user_data['year_level']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><input type="text" name="email" value="<?php echo $user_data['email']; ?>" class="editable-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
                <tr>
                    <th>Sessions Remaining</th>
                    <td><input type="text" name="sessions" value="<?php echo $user_data['sessions']; ?>" class="readonly-input" readonly style="background-color: #333333; color: #ffffff;"></td>
                </tr>
            </table>
            <div class="button-wrapper">
                <button type="button" onclick="toggleEdit()" class="edit-button" id="editButton">Edit</button>
                <button type="submit" name="save_changes" class="save-button" style="display: none;">Save</button>
            </div>
        </form>
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
                        <p>CCS ADMIN</p>
                        <p>${announcement.date_posted}</p>
                        <p>${announcement.announcement_text ? announcement.announcement_text : "CCS ADMIN"}</p>
                    </div>
                `;
            });
        })
        .catch(error => {
            console.error('Error:', error);
            announcementList.innerHTML = '<p>Error loading announcements.</p>';
        });
    }

    function loadCurrentSitInStudents() {
        fetch('../ADMIN/get_current_sitin_data.php')
        .then(response => {
            console.log('Response Status:', response.status); // Log response status
            return response.json();
        })
        .then(data => {
            console.log('Fetched Data:', data); // Log fetched data
            const sitInList = document.getElementById('currentSitInList');
            sitInList.innerHTML = ''; // Clear existing data

            data.forEach(student => {
                sitInList.innerHTML += `
                    <tr>
                        <td>${student.id_number}</td>
                        <td>${student.first_name}</td>
                        <td>${student.last_name}</td>
                        <td>
                            <form action="../ADMIN/logout_sitin.php" method="post" style="display:inline;">
                                <input type="hidden" name="id_number" value="${student.id_number}">
                                <button type="submit" name="logout" class="logout-button">Logout</button>
                            </form>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('currentSitInList').innerHTML = '<tr><td colspan="4">Error loading sit-in students.</td></tr>';
        });
    }

    function openModal() {
        document.getElementById('editProfileModal').style.display = "block";
    }

    function closeModal() {
        document.getElementById('editProfileModal').style.display = "none";
    }

    function toggleEdit() {
        const inputs = document.querySelectorAll('.editable-input');
        const editButton = document.getElementById('editButton');
        const saveButton = document.querySelector('.save-button');
        const isEditing = editButton.innerText === "Edit";
        
        inputs.forEach(input => {
            input.readOnly = !isEditing;
            input.style.backgroundColor = isEditing ? "#ffffff" : "#333333"; // Change background color based on edit state
            input.style.color = isEditing ? "#000000" : "#ffffff"; // Change text color based on edit state
        });
        
        editButton.style.display = isEditing ? "none" : "inline-block"; // Hide edit button when editing
        saveButton.style.display = isEditing ? "inline-block" : "none"; // Show save button when editing
        editButton.innerText = isEditing ? "Save" : "Edit"; // Toggle button text
    }

    // Load current sit-in students on page load
    loadCurrentSitInStudents();
    loadAnnouncements();
</script>
</body>
</html>
<?php
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>
