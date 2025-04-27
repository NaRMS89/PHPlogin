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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_reservation'])) {
    $lab = filter_input(INPUT_POST, 'lab', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_SPECIAL_CHARS);

    $user_id = $_SESSION['user_data']['id_number'];
    $sql = "INSERT INTO reservations (user_id, lab, date, start_time, end_time, purpose, status) 
            VALUES ('$user_id', '$lab', '$date', '$start_time', '$end_time', '$purpose', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Reservation submitted successfully!');</script>";
    } else {
        echo "<script>alert('Error submitting reservation: " . mysqli_error($conn) . "');</script>";
    }
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
            width: 100%;
        }

        .three-column-layout {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
        }

        .profile-column {
            flex: 0 0 25%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            min-width: 300px;
        }

        .announcement-column {
            flex: 0 0 40%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            overflow-y: auto;
            max-height: 80vh;
            min-width: 400px;
        }

        .rules-column {
            flex: 0 0 25%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 2rem;
            overflow-y: auto;
            max-height: 80vh;
            min-width: 300px;
        }

        .profile-info {
            margin-bottom: 1.5rem;
        }

        .profile-picture-wrapper {
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-picture-main {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 1400px) {
            .three-column-layout {
                flex-wrap: wrap;
            }

            .profile-column,
            .announcement-column,
            .rules-column {
                flex: 1 1 300px;
            }
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }

            .three-column-layout {
                flex-direction: column;
            }

            .profile-column,
            .announcement-column,
            .rules-column {
                flex: 1 1 100%;
                min-width: 100%;
                max-height: none;
            }
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
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }

        .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .announcement-text {
            color: var(--light);
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .announcement-date {
            color: var(--border-color);
            font-size: 0.9rem;
        }

        .no-announcements {
            color: var(--border-color);
            text-align: center;
            padding: 1rem;
        }

        #rulesContent ol {
            padding-left: 2rem;
        }

        #rulesContent li {
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

        .form-control:focus {
            outline: none;
            border-color: var(--focus);
            box-shadow: 0 0 0 2px var(--shadow-1);
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--focus);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-1);
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
            margin-bottom: 2rem;
        }

        .history-card h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .history-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-box, .date-filter {
            flex: 1;
            min-width: 200px;
        }

        .history-table-container {
            overflow-x: auto;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--light);
        }

        .history-table th {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 500;
            color: var(--primary);
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #pageInfo {
            color: var(--light);
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-badge.pending {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-badge.approved {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-badge.rejected {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .status-badge.cancelled {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .text-center {
            text-align: center;
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

            .history-table {
                display: block;
                overflow-x: auto;
            }

            .history-table th,
            .history-table td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .status-badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        .feedback-text {
            color: var(--light);
            font-style: italic;
        }

        .btn-feedback {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-feedback:hover {
            background: var(--focus);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .modal-content {
            background: var(--global-background);
            margin: 15% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light);
        }

        #feedbackForm textarea {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: var(--light);
            margin-bottom: 1rem;
        }

        #feedbackForm textarea:focus {
            outline: none;
            border-color: var(--focus);
            box-shadow: 0 0 0 2px var(--shadow-1);
        }
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="button-container">
            <button class="nav-btn active" onclick="switchContent('homeContent', this)">Home</button>
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
            <div class="three-column-layout">
                <!-- Left Column - Profile -->
                <aside class="profile-column">
                    <section class="profile-info">
                        <h2>My Profile</h2>
                        <div class="profile-picture-wrapper">
                            <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture-main">
                        </div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($user_data['id_number']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($user_data['course']); ?></p>
                        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($user_data['year_level']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                        <p><strong>Sessions Remaining:</strong> <?php echo $user_data['sessions']; ?></p>
                        <a href="javascript:void(0);" class="link-1" onclick="openEditProfileModal()">Edit Profile</a>
                    </section>
                </aside>

                <!-- Center Column - Announcements -->
                <main class="announcement-column">
                    <h2>Announcements</h2>
                    <div id="announcementList" class="announcement-list">
                        <?php
                        $sql = "SELECT * FROM announcements ORDER BY date_posted DESC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<div class="announcement-item">';
                                echo '<div class="announcement-text">' . htmlspecialchars($row['announcement_text']) . '</div>';
                                echo '<div class="announcement-date">Posted on ' . date("F j, Y", strtotime($row['date_posted'])) . '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-announcements">No announcements available at the moment.</p>';
                        }
                        ?>
                    </div>
                </main>

                <!-- Right Column - Rules -->
                <aside class="rules-column">
                    <section id="rulesContent">
                        <h2>Rules</h2>
                        <h3>Laboratory Rules and Regulations</h3>
                        <ol>
                            <li>Maintain silence and discipline. Switch off personal devices.</li>
                            <li>No games allowed.</li>
                            <li>Internet use only with permission. No downloads/installations.</li>
                            <li>No access to unrelated or inappropriate sites.</li>
                            <li>Altering system settings or deleting files is prohibited.</li>
                            <li>Fifteen-minute grace period per use; then seat is forfeited.</li>
                            <li>
                                Proper decorum:
                                <ul>
                                    <li>Enter only with instructor present.</li>
                                    <li>Store bags at the counter.</li>
                                    <li>Follow seating assignments.</li>
                                    <li>Close all software and return chairs after use.</li>
                                </ul>
                            </li>
                            <li>No eating, drinking, gum, smoking, or vandalism.</li>
                            <li>Disturbances and offensive acts will not be tolerated.</li>
                            <li>Hostile behavior will lead to removal or CSU involvement.</li>
                            <li>Report issues to lab staff immediately.</li>
                        </ol>

                        <h3>DISCIPLINARY ACTION</h3>
                        <p><strong>First Offense:</strong> May lead to class suspension via Guidance Center.</p>
                        <p><strong>Further Offenses:</strong> Harsher sanctions to follow.</p>
                    </section>
                </aside>
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

                <div class="user-info">
                    <p><strong>ID Number:</strong> <span id="studentId"><?php echo htmlspecialchars($_SESSION['id_number'] ?? 'N/A'); ?></span></p>
                    <p><strong>Name:</strong> <span id="studentName"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></span></p>
                </div>

                <form id="reservationForm" class="reservation-form" action="process_reservation.php" method="post">
                    <input type="hidden" name="make_reservation" value="1">

                    <div class="form-group">
                        <label for="labRoom">Lab Room</label>
                        <select class="form-control" id="labRoom" name="lab" required>
                            <option value="">Select Lab Room</option>
                            <option value="524">524</option>
                            <option value="526">526</option>
                            <option value="528">528</option>
                            <option value="530">530</option>
                            <option value="542">542</option>
                            <option value="544">544</option>
                            <option value="517">517</option>
                        </select>
                    </div>

                    <div id="computerSelection" style="display: none;">
                        <h3>Select a Computer in <span id="selectedLab"></span></h3>
                        <div class="form-group"> 
                            <label for="computerSelect">Available Computers</label>
                            <select class="form-control" id="computerSelect" name="computer_select" required disabled> 
                                <option value="">-- Select Available PC --</option>
                                <!-- Options will be loaded here by JS -->
                            </select>
                        </div>
                        <input type="hidden" id="selectedComputer" name="computer" required>
                        <p>Selected: <span id="computerDisplay">None</span></p>
                    </div>

                    <div class="form-group">
                        <label for="purpose">Purpose</label>
                        <select class="form-control" id="purpose" name="purpose" required>
                            <option value="">Select Purpose</option>
                            <option value="C Programming">C Programming</option>
                            <option value="Java Programming">Java Programming</option>
                            <option value="Python">Python</option>
                            <option value="C#">C#</option>
                            <option value="Database">Database</option>
                            <option value="Digital Logic & Design">Digital Logic & Design</option>
                            <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                            <option value="System Integration and Architecture">System Integration and Architecture</option>
                            <option value="Computer Application">Computer Application</option>
                            <option value="Project Management">Project Management</option>
                            <option value="IT Trends">IT Trends</option>
                            <option value="Technopreneurship">Technopreneurship</option>
                            <option value="Capstone">Capstone</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="timeIn">Time In</label>
                            <input type="text" class="form-control" id="timeIn" name="time_in" readonly>
                        </div>
                        <div class="form-group">
                            <label for="reservationDate">Date</label>
                            <input type="date" class="form-control" id="reservationDate" name="date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remainingSessions">Remaining Sessions</label>
                        <input type="text" class="form-control" id="remainingSessions" name="remaining_sessions" readonly value="<?php echo htmlspecialchars($user_data['remaining_sessions'] ?? 'N/A'); ?>">
                    </div>

                    <button type="submit" class="nav-btn">Reserve</button>
                </form>
            </div>

            <div id="pendingReservations">
                <h3>Pending Reservations</h3>
                <!-- Pending reservations will be loaded here -->
            </div>
        </div>

        <!-- History Content -->
        <div id="historyContent" class="dynamic-content">
            <div class="history-card">
                <h2>Sit-in History</h2>
                <div class="history-table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Lab</th>
                                <th>Purpose</th>
                                <th>Duration</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="text-center">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal-container" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; overflow-y: auto;">
        <div class="modal" style="background: var(--global-background); padding: 2rem; border-radius: 1rem; width: 90%; max-width: 600px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center;">
            <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Edit Profile</h2>
            
            <div class="profile-picture-container" style="position: relative; width: 150px; height: 150px; margin: 0 auto 2rem; cursor: pointer;" onclick="document.getElementById('profilePictureInput').click()">
                <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <div class="change-photo-btn" style="position: absolute; bottom: 0; right: 0; background: var(--primary); border: none; color: white; padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;">

            <form id="editProfileForm" method="post" style="width: 100%;">
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="first_name" style="display: block; margin-bottom: 0.5rem; color: var(--light);">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="last_name" style="display: block; margin-bottom: 0.5rem; color: var(--light);">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="middle_name" style="display: block; margin-bottom: 0.5rem; color: var(--light);">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($user_data['middle_name']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="course" style="display: block; margin-bottom: 0.5rem; color: var(--light);">Course</label>
                    <input type="text" id="course" name="course" class="form-control" value="<?php echo htmlspecialchars($user_data['course']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="year_level" style="display: block; margin-bottom: 0.5rem; color: var(--light);">Year Level</label>
                    <input type="text" id="year_level" name="year_level" class="form-control" value="<?php echo htmlspecialchars($user_data['year_level']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem; width: 100%;">
                    <label for="email" style="display: block; margin-bottom: 0.5rem; color: var(--light);">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--light);">
                </div>
                <div style="display: flex; gap: 1rem; width: 100%;">
                    <button type="submit" name="save_changes" class="nav-btn" style="flex: 1;">Save Changes</button>
                    <button type="button" class="nav-btn" onclick="closeEditProfileModal()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Submit Feedback</h2>
            <form id="feedbackForm">
                <input type="hidden" id="sitInId" name="sit_in_id">
                <div class="form-group">
                    <label for="feedbackText">Your Feedback:</label>
                    <textarea id="feedbackText" name="feedback" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Feedback</button>
            </form>
        </div>
    </div>

    <script>
        function loadContent(contentId) {
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
            } else if (contentId === 'homeContent') {
                loadAnnouncements();
            }
        }

        function loadHistoryData() {
            const historyTableBody = document.getElementById('historyTableBody');
            historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading history...</td></tr>';

            fetch('get_sitin_history.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        historyTableBody.innerHTML = '';
                        data.data.forEach(record => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${record.date}</td>
                                <td>${record.time}</td>
                                <td>Lab ${record.lab}</td>
                                <td>${record.purpose}</td>
                                <td>${record.duration}</td>
                                <td>
                                    ${record.feedback ? 
                                        `<span class="feedback-text">${record.feedback}</span>` : 
                                        `<button class="btn-feedback" onclick="openFeedbackModal(${record.id})">Give Feedback</button>`
                                    }
                                </td>
                            `;
                            historyTableBody.appendChild(row);
                        });
                    } else {
                        historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No history records found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading history data</td></tr>';
                });
        }

        function loadAnnouncements() {
            const announcementList = document.getElementById('announcementList');
            announcementList.innerHTML = '<div class="announcement-item"><p>Loading announcements...</p></div>';
            
            fetch('../hold/get_announcements.php')
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        announcementList.innerHTML = '';
                        data.forEach(announcement => {
                            const announcementItem = document.createElement('div');
                            announcementItem.className = 'announcement-item';
                            announcementItem.innerHTML = `
                                <div class="announcement-text">${announcement.announcement_text}</div>
                                <div class="announcement-date">Posted on ${new Date(announcement.date_posted).toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'long', 
                                    day: 'numeric' 
                                })}</div>
                            `;
                            announcementList.appendChild(announcementItem);
                        });
                    } else {
                        announcementList.innerHTML = '<p class="no-announcements">No announcements available at the moment.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    announcementList.innerHTML = '<p class="no-announcements">Error loading announcements. Please try again later.</p>';
                });
        }

        function openFeedbackModal(sitInId) {
            const modal = document.getElementById('feedbackModal');
            document.getElementById('sitInId').value = sitInId;
            document.getElementById('feedbackText').value = '';
            modal.style.display = 'block';
        }

        function closeFeedbackModal() {
            const modal = document.getElementById('feedbackModal');
            modal.style.display = 'none';
        }

        // Close modal when clicking the X button
        document.querySelector('.close').addEventListener('click', closeFeedbackModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target === modal) {
                closeFeedbackModal();
            }
        });

        // Handle feedback form submission
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Feedback submitted successfully!');
                    closeFeedbackModal();
                    loadHistoryData(); // Refresh the history table
                } else {
                    alert(data.error || 'Error submitting feedback');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback');
            });
        });

        function openEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'block';
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target === modal) {
                closeEditProfileModal();
            }
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

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set home content and button as active by default
            document.getElementById('homeContent').classList.add('active');
            document.querySelector('button[onclick*="homeContent"]').classList.add('active');
            
            // Load initial data
            loadAnnouncements();
            loadHistoryData();
            
            // Set up auto-refresh for announcements every 5 minutes
            setInterval(loadAnnouncements, 300000);
        });
    </script>
    <script src="reservation.js"></script>
</body>
</html>
<?php
if ($conn instanceof mysqli) {
    mysqli_close($conn);
}
