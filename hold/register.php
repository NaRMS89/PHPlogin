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
    <style>
        /*
        ================================
            Best Viewed In Full Page
        ================================
        */

        /* defaults */
        *,
        *::after,
        *::before {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 62.5%;
        }

        body {
            --light: hsl(220, 50%, 90%);
            --primary: hsl(255, 30%, 55%);
            --focus: hsl(210, 90%, 50%);
            --border-color: hsla(0, 0%, 100%, .2);
            --global-background: hsl(220, 25%, 10%);
            --background: linear-gradient(to right, hsl(210, 30%, 20%), hsl(255, 30%, 25%));
            --shadow-1: hsla(236, 50%, 50%, .3);
            --shadow-2: hsla(236, 50%, 50%, .4);

            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Open Sans', sans-serif;
            color: var(--light);
            background: var(--global-background);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('../uploads/background.jpg');
            background-size: cover;
            background-position: center;
        }

        .container {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--background);
            border-radius: 0.8rem;
            box-shadow: 0.4rem 0.4rem 10.2rem 0.2rem var(--shadow-1);
            width: 90%;
            max-width: 60rem;
        }

        .box__title {
            font-size: 4.8rem;
            font-weight: normal;
            letter-spacing: .8rem;
            margin-bottom: 2.6rem;
            color: var(--light);
        }

        .box__title::first-letter {
            color: var(--primary);
        }

        .box__p {
            font-size: 1.6rem;
            margin-bottom: 3rem;
            color: var(--light);
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1.4rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
        }

        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .modal__btn {
            margin-top: 2rem;
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: inherit;
            background: transparent;
            font-size: 1.4rem;
            font-family: inherit;
            letter-spacing: .2rem;
            transition: .2s;
            cursor: pointer;
            width: 100%;
        }

        .modal__btn:hover,
        .modal__btn:focus {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-.2rem);
        }

        .link-1 {
            font-size: 1.8rem;
            color: var(--light);
            background: var(--background);
            box-shadow: .4rem .4rem 2.4rem .2rem var(--shadow-1);
            border-radius: 100rem;
            padding: 1.4rem 3.2rem;
            transition: .2s;
            text-decoration: none;
            display: inline-block;
            margin-top: 2rem;
        }

        .link-1:hover,
        .link-1:focus {
            transform: translateY(-.2rem);
            box-shadow: 0 0 4.4rem .2rem var(--shadow-2);
        }

        .error {
            color: #ff4444;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            color: #4CAF50;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="box__title">Register</h1>
        <p class="box__p">Create your account to access the Computer Laboratory Management System</p>
        
        <?php if (isset($error_message)) { echo "<div class='error'>" . $error_message . "</div>"; } ?>
        <?php if (isset($success_message)) { echo "<div class='success'>" . $success_message . "</div>"; } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="text" id="idno" name="idno" required placeholder="ID Number">
            </div>
            <div class="form-group">
                <input type="text" id="lastname" name="lastname" required placeholder="Last Name">
            </div>
            <div class="form-group">
                <input type="text" id="firstname" name="firstname" required placeholder="First Name">
            </div>
            <div class="form-group">
                <input type="text" id="midname" name="midname" required placeholder="Middle Name">
            </div>
            <div class="form-group">
                <select id="course" name="course" required>
                    <option value="" disabled selected>Select your course</option>
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
                </select>
            </div>
            <div class="form-group">
                <select id="yearlvl" name="yearlvl" required>
                    <option value="" disabled selected>Select your year level</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" required placeholder="Email">
            </div>
            <div class="form-group">
                <input type="text" id="username" name="username" required placeholder="Username">
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" required placeholder="Password">
            </div>
            <button type="submit" class="modal__btn">Register</button>
        </form>
        <a href='../USER/index.php' class='link-1'>Back to Login</a>
    </div>
</body>
</html>
