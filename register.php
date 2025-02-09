<?php
include("database.php");

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
        $check_idno_user_email_sql = "SELECT * FROM info WHERE id_number = '$idno' OR username = '$username' OR email = '$email'";
        $result = mysqli_query($conn, $check_idno_user_email_sql);

        if (mysqli_num_rows($result) > 0) {
            $error_message = "ID Number, Username, or email already exists. Please choose different ones.";
        } else {
            // Removed password hashing
            $sql = "INSERT INTO info (id_number, last_name, first_name, middle_name, course, year_level, email, username, password) 
                    VALUES ('$idno', '$lastname', '$firstname', '$midname', '$course', '$yearlvl', '$email', '$username', '$password')"; // Password stored in plain text

            if (mysqli_query($conn, $sql)) {
                $success_message = "Registration successful. You can now log in.";
            } else {
                $error_message = "Error during registration: " . mysqli_error($conn);
            }
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
        .container input[type="password"],
        .container input[type="number"],
        .container input[type="email"] {
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

        .back-to-login {
            margin-top: 20px;
        }

        .back-to-login button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .back-to-login button:hover {
            background-color: #0056b3;
        }

        .connection-error {
            position: absolute;
            top: 0;
            left: 0;
            background-color: red;
            color: white;
            padding: 10px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
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
            <input type="text" id="course" name="course" required><br>

            Year Level: <br>
            <input type="number" id="yearlvl" name="yearlvl" required><br>

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
            echo "<button onclick=\"window.location.href='index.php'\">Back to Login</button>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>