<?php
session_start();
include("database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

    if ($idno === "99999999" && $password === "123") {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    } elseif (empty($idno)) {
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
    <style>
        .register-link {
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: auto;">
        <div class="login-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>Login</h2>
                <?php
                if (isset($error_message)) {
                    echo "<div class='error'>" . $error_message . "</div>";
                }
                ?>
                ID Number: <br>
                <input type="text" id="idno" name="idno" required><br>
                Password: <br>
                <input type="password" id="password" name="password" required><br>
                <input type="submit" value="Login">
                <div class="register-link">
                    Need an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>