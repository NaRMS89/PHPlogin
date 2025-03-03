<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_data'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM info WHERE id_number = '$user_id'";
$result = mysqli_query($conn, $sql);
$user_data = mysqli_fetch_assoc($result);
$_SESSION['user_data'] = $user_data;

// Decrement sessions remaining on login
if (!isset($_SESSION['session_decremented'])) {
    $user_data['sessions']--;
    $_SESSION['user_data']['sessions'] = $user_data['sessions'];
    $_SESSION['session_decremented'] = true;

    $sql = "UPDATE info SET sessions = sessions - 1 WHERE id_number = '$user_id'";
    mysqli_query($conn, $sql);
}

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

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $upload_file = $upload_dir . basename($_FILES['profile_picture']['name']);
        $imageFileType = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_file)) {
                $profile_picture_path = basename($_FILES['profile_picture']['name']);
                $updates[] = "profile_picture = '$profile_picture_path'";
                $_SESSION['user_data']['profile_picture'] = $profile_picture_path;
            }
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Display profile picture at the top -->

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-header">
                <img src="uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                <h2>Welcome, <?php echo $user_data['last_name'] . ' ' . $user_data['first_name'] . ' ' . $user_data['middle_name']; ?>!</h2>
            </div>
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
                case 'userInfoContent':
                    content = `
                        <h2>User Information</h2>
                        <form id="userInfoForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <table>
                                <tr>
                                    <td colspan="2">
                                        <img src="uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                                        <input type="file" name="profile_picture" accept="image/*" class="readonly-input" style="display:none;">
                                    </td>
                                </tr>
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
                                    <th>Sessions Remaining</th>
                                    <td><input type="text" name="sessions" value="<?php echo $user_data['sessions']; ?>" class="readonly-input" readonly style="background-color: #f0f0f0;"></td>
                                </tr>
                            </table>
                            <div class="button-wrapper">
                                <button type="button" id="editBtn" class="logout-button">Edit</button>
                                <button type="submit" name="save_changes" id="saveBtn" class="logout-button" style="display:none;">Save Changes</button>
                            </div>
                        </form>
                    `;
                    break;
                case 'announcementContent':
                    content = '<p>Announcement content goes here...</p>';
                    break;
                case 'remainingSessionsContent':
                    content = '<p>Remaining sessions content goes here...</p>';
                    break;
                case 'sitInRulesContent':
                    content = '<p>Sit-in rules content goes here...</p>';
                    break;
                case 'labRulesContent':
                    content = '<p>Lab rules and regulations content goes here...</p>';
                    break;
                case 'sitInHistoryContent':
                    content = '<p>Sit-in history content goes here...</p>';
                    break;
                case 'reservationContent':
                    content = '<p>Reservation content goes here...</p>';
                    break;
                default:
                    content = '<p>Content not found.</p>';
            }
            document.getElementById('dynamicContent').innerHTML = content;

            // Re-attach event listener for edit button
            var editBtn = document.getElementById("editBtn");
            var saveBtn = document.getElementById("saveBtn");
            var inputs = document.querySelectorAll("#userInfoForm input[type='text'], #userInfoForm input[type='file']");

            editBtn.onclick = function() {
                inputs.forEach(function(input) {
                    if (input.name !== 'sessions') {
                        input.classList.remove("readonly-input");
                        input.removeAttribute("readonly");
                        input.removeAttribute("disabled");
                        if (input.type === 'file') {
                            input.style.display = "block";
                        }
                    }
                });
                editBtn.style.display = "none";
                saveBtn.style.display = "inline-block";
            }
        }
    </script>
</body>
</html>

<?php
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
?>