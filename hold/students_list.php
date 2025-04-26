<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Get all students with their points and award count
$query = "SELECT i.id_number, i.first_name, i.last_name, i.course, 
                 COALESCE(SUM(p.points), 0) as total_points, 
                 COUNT(p.id) as award_count
          FROM info i
          LEFT JOIN points p ON i.id_number = p.id_number
          GROUP BY i.id_number, i.first_name, i.last_name, i.course
          ORDER BY total_points DESC, i.last_name, i.first_name";
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
    <title>Students List - Admin Dashboard</title>
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
        .students-table th {
            background-color: #f8f9fa;
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
                        <h4 class="mb-0">Students List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped table-bordered students-table">
                                <thead>
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Total Points</th>
                                        <th>Award Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo htmlspecialchars($student['total_points']); ?></td>
                                        <td><?php echo htmlspecialchars($student['award_count']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
            $('#studentsTable').DataTable({
                order: [[3, 'desc']], // Sort by points by default
                pageLength: 25
            });
        });
    </script>
</body>
</html>