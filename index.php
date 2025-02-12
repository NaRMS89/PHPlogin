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
        $check_user_sql = "SELECT * FROM info WHERE id_number = ?";
        $stmt = $conn->prepare($check_user_sql);
        $stmt->bind_param("s", $idno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $stored_password = $row['password'];

            if ($password == $stored_password) {
                session_start();
                $_SESSION['user_id'] = $row['id_number'];
                $_SESSION['user_name'] = $row['first_name'];
                $_SESSION['user_data'] = $row;

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
                if (isset($_SESSION['user_name'])) {
                    echo "<p>Welcome, " . $_SESSION['user_name'] . "!</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
