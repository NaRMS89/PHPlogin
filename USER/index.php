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
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-cover bg-center" style="background-image: url('../uploads/background.jpg');">
    <div class="flex items-center justify-center min-h-screen">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-black bg-opacity-70 p-8 rounded-lg shadow-md" style="backdrop-filter: blur(10px);">

            <h2 class="text-center text-2xl font-bold text-white">Login</h2>
            <?php if (isset($error_message)) { echo "<div class='error' style='color: red;'>" . $error_message . "</div>"; } ?>
            
            <input type="text" id="idno" name="idno" required placeholder="ID Number" autocomplete="username" class="border border-gray-300 p-2 rounded mb-4 w-full">
            <input type="password" id="password" name="password" required placeholder="Password" autocomplete="current-password" class="border border-gray-300 p-2 rounded mb-4 w-full">
            <input type="submit" value="Login" class="bg-green-500 text-white p-2 rounded w-full hover:bg-green-600">
            <div class="text-center mt-4">
                <span class="text-white">Need an account? </span><a href="../admin/register.php" class="text-green-500">Register here</a>
            </div>
        </form>
    </div>
</body>
</html>
