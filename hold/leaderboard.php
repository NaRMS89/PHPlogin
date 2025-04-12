<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Get student rankings
$query = "SELECT id_number, first_name, last_name, course, points 
          FROM info 
          WHERE points > 0 
          ORDER BY points DESC, last_name ASC, first_name ASC";
$result = mysqli_query($conn, $query);
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .leaderboard-table th {
            background-color: #f8f9fa;
        }
        .rank-1 {
            background-color: #ffd700 !important;
        }
        .rank-2 {
            background-color: #c0c0c0 !important;
        }
        .rank-3 {
            background-color: #cd7f32 !important;
        }
    </style>
</head>
<body>
    <?php include("../includes/admin_header.php"); ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Leaderboard</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="leaderboardTable" class="table table-striped table-bordered leaderboard-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Lab</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch student points
                                    $query = "SELECT 
                                             s.id_number,
                                             CONCAT(i.first_name, ' ', i.last_name) as student_name,
                                             s.lab,
                                             COALESCE(SUM(sr.points), 0) as points
                                             FROM students s
                                             JOIN info i ON s.id_number = i.id_number
                                             LEFT JOIN sitin_report sr ON s.id_number = sr.student_id
                                             GROUP BY s.id_number, i.first_name, i.last_name, s.lab
                                             ORDER BY points DESC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    $rank = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . $rank++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['points']) . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#leaderboardTable').DataTable({
                order: [[0, 'asc']], // Sort by rank by default
                pageLength: 25,
                searching: true,
                info: true,
                paging: true
            });
        });
    </script>
</body>
</html> 