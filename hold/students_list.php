<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Handle point addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_points'])) {
    $student_id = $_POST['student_id'];
    $points = $_POST['points'];
    $reason = $_POST['reason'];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Update student points
        $sql = "UPDATE info SET points = points + ? WHERE id_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $points, $student_id);
        mysqli_stmt_execute($stmt);
        
        // Add point history
        $sql = "INSERT INTO point_history (id_number, points, reason, added_by) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $added_by = $_SESSION['admin_username'];
        mysqli_stmt_bind_param($stmt, "siss", $student_id, $points, $reason, $added_by);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $success_message = "Points added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error adding points: " . $e->getMessage();
    }
}

// Get all students with their points
$query = "SELECT id_number, first_name, last_name, course, points FROM info ORDER BY points DESC";
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
        .action-buttons .btn {
            margin-right: 5px;
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
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped table-bordered students-table">
                                <thead>
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Total Points</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo htmlspecialchars($student['points']); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="addPoints('<?php echo $student['id_number']; ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                <i class="fas fa-plus"></i> Give Points
                                            </button>
                                        </td>
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

    <!-- Add Points Modal -->
    <div class="modal fade" id="addPointsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Points</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="student_id">
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" id="student_name" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Points to Add</label>
                            <input type="number" name="points" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_points" class="btn btn-primary">Add Points</button>
                    </div>
                </form>
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

        function addPoints(studentId, studentName) {
            $('#student_id').val(studentId);
            $('#student_name').val(studentName);
            $('#addPointsModal').modal('show');
        }
    </script>
</body>
</html> 