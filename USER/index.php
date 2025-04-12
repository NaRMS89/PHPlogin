<?php
session_start();
include("../includes/database.php");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

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
    <title>Student Portal</title>
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

        .button-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
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
        }

        .link-1:hover,
        .link-1:focus {
            transform: translateY(-.2rem);
            box-shadow: 0 0 4.4rem .2rem var(--shadow-2);
        }

        /* Modal Styles */
        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            display: none;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal-container:target {
            display: flex;
        }

        .modal {
            width: 60rem;
            padding: 4rem 2rem;
            border-radius: .8rem;
            color: var(--light);
            background: var(--background);
            box-shadow: 0.4rem 0.4rem 10.2rem 0.2rem var(--shadow-1);
            position: relative;
        }

        .modal__title {
            font-size: 3.2rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.6rem;
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1.2rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
            height: 4.5rem;
            line-height: 1.5;
        }

        .form-group select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em;
        }

        .form-group select option {
            background: var(--global-background);
            color: var(--light);
            padding: 1.2rem;
        }

        .form-group input::placeholder {
            color: var(--light);
            opacity: 0.7;
        }

        .form-group select:invalid {
            color: var(--light);
            opacity: 0.7;
        }

        .modal__btn {
            margin-top: 3rem;
            padding: 1.2rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: var(--light);
            background: transparent;
            font-size: 1.4rem;
            font-family: inherit;
            letter-spacing: .2rem;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .modal__btn:hover,
        .modal__btn:focus {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-.2rem);
        }

        .modal__btn:active {
            transform: translateY(0);
        }

        .link-2 {
            width: 4rem;
            height: 4rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: inherit;
            font-size: 2.2rem;
            position: absolute;
            top: 2rem;
            right: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: .2s;
            text-decoration: none;
        }

        .link-2::before {
            content: '×';
            transform: translateY(-.1rem);
        }

        .link-2:hover,
        .link-2:focus {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-.2rem);
        }

        .error {
            color: #ff4444;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .login-link {
            display: block;
            margin-top: 2.4rem;
            text-align: center;
            color: var(--light);
            font-size: 1.4rem;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .login-link:hover {
            opacity: 1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="box__title">Student Portal</h1>
        <p class="box__p">Welcome to the Computer Laboratory Management System</p>
        <div class="button-container">
            <a href="#loginModal" class="link-1">Login</a>
            <a href="#registerModal" class="link-1">Register</a>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal-container">
        <div class="modal">
            <a href="#" class="link-2"></a>
            <h2 class="modal__title">Login</h2>
            <?php if (isset($error_message)) { echo "<div class='error'>" . $error_message . "</div>"; } ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <input type="text" id="idno" name="idno" required placeholder="ID Number" autocomplete="username">
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" required placeholder="Password" autocomplete="current-password">
                </div>
                <button type="submit" class="modal__btn">Login</button>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal-container">
        <div class="modal">
            <a href="#" class="link-2"></a>
            <h2 class="modal__title">Register</h2>
            <form action="../admin/register.php" method="post">
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
            <a href="#loginModal" class="login-link">Already have an account? Login here</a>
        </div>
    </div>
</body>
</html>
<?php if ($conn instanceof mysqli) { mysqli_close($conn); } ?>
