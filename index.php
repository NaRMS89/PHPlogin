<?php
include("database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($idno)) {
        $error_message = "Please enter your ID number";
    } elseif (empty($password)) {
        $error_message = "Please enter your password";
    } else {
        $check_user_sql = "SELECT * FROM info WHERE id_number = '$idno'";
        $result = mysqli_query($conn, $check_user_sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $stored_password = $row['password']; // Get stored password (plain text for now)

            if ($password == $stored_password) { // Direct comparison (INSECURE - for testing only)
                // Start a session (important for logged-in users)
                session_start();
                $_SESSION['user_id'] = $row['id_number']; // Store user ID in session
                $_SESSION['user_name'] = $row['first_name']; // Store user name in session (optional)
                $_SESSION['user_data'] = $row; // Store all user data in session

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password";
            }
        } else {
            $error_message = "User not found, Please register";
        }
    }
    if ($conn instanceof mysqli) {
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <h2>LOGIN</h2>
            ID Number: <br>
            <input type="text" id="idno" name="idno"><br>
            Password: <br>
            <input type="password" id="password" name="password"><br>
            <input type="submit" name="submit" value="LOGIN">
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>

        <div id="loginMessage">
            <?php
            if (isset($error_message)) {
                echo "<div class='error'>" . $error_message . "</div>";
            }
            if (isset($success_message)) {
                echo "<div class='success'>" . $success_message . "</div>";
                // Optionally, you can add a welcome message or other content here
                if (isset($_SESSION['user_name'])) {
                    echo "<p>Welcome, " . $_SESSION['user_name'] . "!</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>