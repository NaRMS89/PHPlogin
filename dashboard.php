<?php
session_start();

if (!isset($_SESSION['user_id'])) {
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
        width: 400px; 
    }

    .container h2 {
        margin-bottom: 20px;
    }

    .container table {
        width: 100%;
        border-collapse: collapse;
    }

    .container table, .container th, .container td {
        border: 1px solid #ccc;
    }

    .container th, .container td {
        padding: 10px;
        text-align: left;
    }

    .logout-button {
        margin-top: 20px;
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .logout-button:hover {
        background-color: #0056b3;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $user_data['last_name'] . ' ' . $user_data['first_name'] . ' ' . $user_data['middle_name']; ?>!</h2>
        <table>
            <tr>
                <th>ID Number</th>
                <td><?php echo $user_data['id_number']; ?></td>
            </tr>
            <tr>
                <th>Last Name</th>
                <td><?php echo $user_data['last_name']; ?></td>
            </tr>
            <tr>
                <th>First Name</th>
                <td><?php echo $user_data['first_name']; ?></td>
            </tr>
            <tr>
                <th>Middle Name</th>
                <td><?php echo $user_data['middle_name']; ?></td>
            </tr>
            <tr>
                <th>Course</th>
                <td><?php echo $user_data['course']; ?></td>
            </tr>
            <tr>
                <th>Year Level</th>
                <td><?php echo $user_data['year_level']; ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo $user_data['email']; ?></td>
            </tr>
        </table>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
    </div>
</body>
</html>