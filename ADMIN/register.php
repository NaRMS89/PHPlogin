<?php
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $lastname = filter_input(INPUT_POST, "lastname", FILTER_SANITIZE_SPECIAL_CHARS);
    $firstname = filter_input(INPUT_POST, "firstname", FILTER_SANITIZE_SPECIAL_CHARS);
    $midname = filter_input(INPUT_POST, "midname", FILTER_SANITIZE_SPECIAL_CHARS);
    $course = filter_input(INPUT_POST, "course", FILTER_SANITIZE_SPECIAL_CHARS);
    $yearlvl = filter_input(INPUT_POST, "yearlvl", FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($idno) || empty($lastname) || empty($firstname) || empty($username) || empty($password) || empty($email) || empty($course) || empty($yearlvl) || empty($midname)) {
        $error_message = "All fields are required.";
    } else {
        $check_idno_user_email_sql = "SELECT * FROM info WHERE id_number = ? OR username = ? OR email = ?";
        $stmt = $conn->prepare($check_idno_user_email_sql);
        $stmt->bind_param("sss", $idno, $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "ID Number, Username, or email already exists. Please choose different ones.";
        } else {
            // Set default profile picture
            $profile_picture = 'default.png';

            $sql = "INSERT INTO info (id_number, last_name, first_name, middle_name, course, year_level, email, username, password, profile_picture) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $idno, $lastname, $firstname, $midname, $course, $yearlvl, $email, $username, $password, $profile_picture);

            if ($stmt->execute()) {
                $success_message = "Registration successful. You can now log in.";
            } else {
                $error_message = "Error during registration: " . $stmt->error;
            }
        }
    }
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="register-container" style="max-width: 600px; margin: auto;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <h2>Register</h2>

            ID Number: <br>  <input type="text" id="idno" name="idno" required><br>

            Last Name: <br>
            <input type="text" id="lastname" name="lastname" required><br>

            First Name: <br>
            <input type="text" id="firstname" name="firstname" required><br>

            Middle Name: <br>
            <input type="text" id="midname" name="midname" required><br>

            Course: <br>
            <select id="course" name="course" required>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSECE">BSECE</option>
                <option value="BSME">BSME</option>
                <option value="BSCE">BSCE</option>
                <option value="BSBA">BSBA</option>
                <option value="BSHRM">BSHRM</option>
                <option value="BSN">BSN</option>
                <option value="BSA">BSA</option>
                <option value="BSPSY">BSPSY</option>
                <option value="BSBIO">BSBIO</option>
                <option value="BSMATH">BSMATH</option>
            </select><br>

            Year Level: <br>
            <select id="yearlvl" name="yearlvl" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select><br>

            Email: <br>
            <input type="email" id="email" name="email" required><br>

            Username: <br>
            <input type="text" id="username" name="username" required><br>

            Password: <br>
            <input type="password" id="password" name="password" required><br>

            <input type="submit" name="submit" value="Register">
        </form>

        <?php
        if (isset($error_message)) {
            echo "<div class='error'>" . $error_message . "</div>";
        }
        if (isset($success_message)) {
            echo "<div class='success'>" . $success_message . "</div>";
            echo "<div class='back-to-login'>";
            echo "<button onclick=\"window.location.href='../USER/index.php'\">Back to Login</button>";
            echo "</div>";
        } else {
            echo "<div class='register-link'>";
            echo "Already have an account? <a href='../USER/index.php'>Back to Login</a>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
