<?php
session_start();
include("../includes/database.php");

// Check if user is logged in and database connection exists
if (!isset($_SESSION['user_id']) || !$conn) {
    header("Location: login.php");
    exit();
}

$id_number = $_SESSION['user_id'];

// Get user's sit-in history using prepared statement
$sql = "SELECT s.*, i.first_name, i.last_name 
        FROM sitin_report s 
        JOIN info i ON s.id_number = i.id_number 
        WHERE s.id_number = ? 
        ORDER BY s.login_time DESC";

if (!$conn) {
    die("Database connection failed");
}

$stmt = mysqli_prepare($conn);
if (!$stmt) {
    die("Failed to prepare statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $id_number);
if (!mysqli_stmt_execute($stmt)) {
    die("Failed to execute statement: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .history-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 4rem; /* Add margin-top to account for fixed header */
        }

        .history-table {
            width: 100%;
            margin-top: 2rem;
            background: var(--background);
            border-radius: 0.8rem;
            overflow: hidden;
            box-shadow: 0 0.4rem 1rem var(--shadow-1);
        }

        .history-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            background: var(--primary);
            color: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--light);
            font-size: 0.95rem;
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .no-records {
            text-align: center;
            padding: 2rem;
            color: var(--light);
            font-style: italic;
        }

        /* Error message styling */
        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 0.4rem;
            margin: 1rem 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include("../includes/user_header.php"); ?>

    <div class="history-content">
        <h2>My Sit-in History</h2>
        <div class="history-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Duration</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $login_time = new DateTime($row['login_time']);
                            $logout_time = $row['logout_time'] ? new DateTime($row['logout_time']) : null;
                            
                            $duration = '';
                            if ($logout_time) {
                                $interval = $logout_time->diff($login_time);
                                $duration = $interval->format('%H hours %i minutes');
                            }
                            
                            echo "<tr>";
                            echo "<td>" . $login_time->format('Y-m-d') . "</td>";
                            echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['lab_login']) . "</td>";
                            echo "<td>" . $login_time->format('H:i:s') . "</td>";
                            echo "<td>" . ($logout_time ? $logout_time->format('H:i:s') : '-') . "</td>";
                            echo "<td>" . ($duration ?: 'Ongoing') . "</td>";
                            echo "<td>" . htmlspecialchars($row['feedback'] ?: '-') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="7" class="no-records">No sit-in history records found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Clean up
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    if ($conn) {
        mysqli_close($conn);
    }
    ?>
</body>
</html> 