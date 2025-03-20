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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-cover bg-center bg-no-repeat bg-center" style="background-image: url('../uploads/background.jpg');">
    <div class="flex items-center justify-center min-h-screen">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-black bg-opacity-80 p-6 rounded-lg shadow-lg max-w-md mx-auto">
            <h2 class="text-center text-3xl font-bold text-white mb-6">Register</h2>

            <input type="text" id="idno" name="idno" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="off" aria-label="ID Number" placeholder="Enter your ID number">

            <input type="text" id="lastname" name="lastname" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="family-name" aria-label="Last Name" placeholder="Enter your last name">

            <input type="text" id="firstname" name="firstname" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="given-name" aria-label="First Name" placeholder="Enter your first name">

            <input type="text" id="midname" name="midname" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="additional-name" aria-label="Middle Name" placeholder="Enter your middle name">

            <select id="course" name="course" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="off" aria-label="Course">
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
            </select><br>

            <select id="yearlvl" name="yearlvl" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="off" aria-label="Year Level">
                <option value="" disabled selected>Select your year level</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select><br>

            <input type="email" id="email" name="email" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="email" aria-label="Email" placeholder="Enter your email">

            <input type="text" id="username" name="username" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="username" aria-label="Username" placeholder="Enter your username">

            <input type="password" id="password" name="password" required class="border border-gray-300 p-2 rounded w-full mb-4 transition duration-300 ease-in-out focus:border-blue-500" autocomplete="new-password" aria-label="Password" placeholder="Enter your password">

            <input type="submit" name="submit" value="Register" class="bg-green-500 text-white p-2 rounded w-full hover:bg-green-600 transition duration-300"><br>
            <div class="text-center mt-4">
                <span class="text-white">Already have an account? </span> <a href='../USER/index.php' class='text-green-500'>Back to Login</a>
            </div>
            <?php
        if (isset($error_message)) {
            echo "<div class='error text-white'>" . $error_message . "</div>";
        }
        if (isset($success_message)) {
            echo "<div class='success text-white'>" . $success_message . "</div>";
        }
        ?>
        </form>



</body>
</html>
</body>
</html>
