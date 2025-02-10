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
        $check_user_sql = "SELECT * FROM registration WHERE idno = '$idno'";
        $result = mysqli_query($conn, $check_user_sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $stored_password = $row['password']; // Get stored password (plain text for now)

            if ($password == $stored_password) { // Direct comparison (INSECURE - for testing only)
                $success_message = "You are now logged in";
                // Start a session (important for logged-in users)
                session_start();
                $_SESSION['user_id'] = $row['idno']; // Store user ID in session
                $_SESSION['user_name'] = $row['firstname']; // Store user name in session (optional)

                // Removed redirect - just display the success message
            } else {
                $error_message = "Invalid password";
            }
        } else {
            $error_message = "User not found, Please register";
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        font-family: sans-serif;
        background-color: #f4f4f4;
    }

    .container {
        background-color: #fff;
        padding: 30px; 
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        width: 300px; 
    }

    .container h2 {
        margin-bottom: 20px;
    }

    .container input[type="text"],
    .container input[type="password"] {
        width: calc(100% - 20px); 
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .container input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: calc(100% - 20px); 
        box-sizing: border-box;
    }

    .container input[type="submit"]:hover {
        background-color: #45a049;
    }

    .error {
        color: red;
        margin-top: 5px;
        text-align: left; 
        padding-left: 10px; 
        width: calc(100% - 20px); 
        box-sizing: border-box;
    }

    .success {
        color: green;
        margin-top: 5px;
        text-align: left; 
        padding-left: 10px; 
        width: calc(100% - 20px); 
        box-sizing: border-box;
    }
    </style>
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
            <a href="register.php">Register</a>
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

    <?php
    ?>
</body>
</html>