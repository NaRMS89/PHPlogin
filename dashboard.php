<?php
session_start();

if (!isset($_SESSION['user_data'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_data = $_SESSION['user_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($user_data['last_name'] . ' ' . $user_data['first_name'] . ' ' . $user_data['middle_name']); ?>!</h2>
        <button id="userInfoBtn" class="logout-button">User Info</button>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- The Modal -->
    <div id="userInfoModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>User Information</h2>
            <form id="userInfoForm" action="update_user.php" method="post">
                <table>

                    <!-- dili poidi ma edit ID NUMBER
                    <tr>
                        <th>ID Number</th>
                        <td><input type="text" name="id_number" value="<?php echo htmlspecialchars($user_data['id_number']); ?>" class="readonly-input" readonly></td>
                    <tr>-->

                        <th>Last Name</th>
                        <td><input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" class="readonly-input" readonly></td>
                    </tr>
                    <tr>
                        <th>First Name</th>
                        <td><input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" class="readonly-input" readonly></td>
                    </tr>
                    <tr>
                        <th>Middle Name</th>
                        <td><input type="text" name="middle_name" value="<?php echo htmlspecialchars($user_data['middle_name']); ?>" class="readonly-input" readonly></td>
                    </tr>
                    <tr>
                        <th>Course</th>
                        <td><input type="text" name="course" value="<?php echo htmlspecialchars($user_data['course']); ?>" class="readonly-input" readonly></td>
                    </tr>
                    <tr>
                        <th>Year Level</th>
                        <td><input type="text" name="year_level" value="<?php echo htmlspecialchars($user_data['year_level']); ?>" class="readonly-input" readonly></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><input type="text" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="readonly-input" readonly></td>
                    </tr>
                </table>
                <button type="button" id="editBtn" class="logout-button">Edit</button>
                <button type="submit" id="saveBtn" class="logout-button" style="display:none;">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        var modal = document.getElementById("userInfoModal");
        var btn = document.getElementById("userInfoBtn");
        var span = document.getElementsByClassName("close")[0];
        var editBtn = document.getElementById("editBtn");
        var saveBtn = document.getElementById("saveBtn");
        var inputs = document.querySelectorAll("#userInfoForm input[type='text']");

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        editBtn.onclick = function() {
            inputs.forEach(function(input) {
                input.classList.remove("readonly-input");
                input.removeAttribute("readonly");
            });
            editBtn.style.display = "none";
            saveBtn.style.display = "inline-block";
        }
    </script>
</body>
</html>
