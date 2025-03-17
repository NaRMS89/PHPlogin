<?php
session_start();
include("../includes/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);
    $remember_me = isset($_POST['remember_me']);

    if ($idno === "99999999" && $password === "123") {
        $_SESSION['admin_logged_in'] = true;
        if ($remember_me) {
            setcookie("admin_logged_in", true, time() + (86400 * 30), "/");
        }
        header("Location: ../ADMIN/admin_dashboard.php");
        exit();
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
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div style="max-width: 500px; margin: auto; padding: 20px 20px; background-color: #1e1e1e; border-radius: 8px;">
        <div class="login-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2 style="text-align: center;">Login</h2>
                <?php
                if (isset($error_message)) {
                    echo "<div class='error' style='color: red;'>" . $error_message . "</div>";
                }
                ?>
                <?php
                if (isset($error_message)) {
                    echo "<div class='error' style='color: red;'>" . $error_message . "</div>";
                }
                ?>
                <?php
                if (isset($error_message)) {
                    echo "<div class='error' style='color: red;'>" . $error_message . "</div>";
                }
                ?>
                ID Number: <br>
                <input type="text" id="idno" name="idno" required style="width: 96%; padding: 10px; margin: 5px 0; border-radius: 4px; border: 1px solid #ccc;"><br>
                Password: <br>
                <input type="password" id="password" name="password" required style="width: 96%; padding: 10px; margin: 5px 0; border-radius: 4px; border: 1px solid #ccc;"><br>
                <input type="submit" value="Login" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
                <div class="register-link" style="text-align: center;">
                    Need an account? <a href="../admin/register.php" style="color: #4CAF50;">Register here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
