<?php
session_start();
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
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
    $profile_picture = $_FILES['profile_picture'];
    if ($profile_picture['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $upload_file = $upload_dir . basename($profile_picture['name']);
        if (move_uploaded_file($profile_picture['tmp_name'], $upload_file)) {
            $profile_picture_path = $profile_picture['name'];
        } else {
            $profile_picture_path = $_SESSION['user_data']['profile_picture'];
        }
    } else {
        $profile_picture_path = $_SESSION['user_data']['profile_picture'];
    }
    $updates[] = "profile_picture = '$profile_picture_path'";
    $_SESSION['user_data']['profile_picture'] = $profile_picture_path;

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
} else {
    $user_data = $_SESSION['user_data'];
}
header("Location: dashboard.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ...existing code... -->
</head>
<body>
    <form action="edit_user.php" method="post" enctype="multipart/form-data">
        <!-- Display profile picture at the top -->
        <div>
            <img src="uploads/<?php echo $user_data['profile_picture']; ?>" alt="Profile Picture" />
        </div>
        <!-- Editable user information fields -->
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>" />
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>" />
        <!-- ...existing code for other fields... -->
        <label for="profile_picture">Profile Picture:</label>
        <input type="file" name="profile_picture" />
        <button type="submit">Save</button>
    </form>
</body>
</html>
