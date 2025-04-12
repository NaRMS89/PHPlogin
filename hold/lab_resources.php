<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Handle schedule operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $sql = "INSERT INTO lab_schedule (lab_name, day, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $_POST['lab_name'], $_POST['day'], $_POST['start_time'], $_POST['end_time'], $_POST['purpose']);
                mysqli_stmt_execute($stmt);
                break;
                
            case 'edit':
                $sql = "UPDATE lab_schedule SET lab_name = ?, day = ?, start_time = ?, end_time = ?, purpose = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $_POST['lab_name'], $_POST['day'], $_POST['start_time'], $_POST['end_time'], $_POST['purpose'], $_POST['schedule_id']);
                mysqli_stmt_execute($stmt);
                break;
                
            case 'delete':
                $sql = "DELETE FROM lab_schedule WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_POST['schedule_id']);
                mysqli_stmt_execute($stmt);
                break;
        }
        
        header("Location: lab_resources.php");
        exit;
    }
}

// Get all lab schedules
$schedules_query = "SELECT * FROM lab_schedule ORDER BY lab_name, day, start_time";
$schedules_result = mysqli_query($conn, $schedules_query);
$schedules = [];
while ($row = mysqli_fetch_assoc($schedules_result)) {
    $schedules[] = $row;
}

// Get unique lab names
$labs_query = "SELECT DISTINCT lab_name FROM lab_schedule ORDER BY lab_name";
$labs_result = mysqli_query($conn, $labs_query);
$labs = [];
while ($row = mysqli_fetch_assoc($labs_result)) {
    $labs[] = $row['lab_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Resources - Admin Dashboard</title>
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
        .schedule-table th {
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Lab Resources</h4>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addScheduleModal">
                            <i class="fas fa-plus"></i> Add Schedule
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="scheduleTable" class="table table-striped table-bordered schedule-table">
                                <thead>
                                    <tr>
                                        <th>Lab</th>
                                        <th>Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Purpose</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['lab_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['start_time']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['end_time']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['purpose']); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Lab Name</label>
                            <input type="text" name="lab_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Day</label>
                            <select name="day" class="form-control" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <input type="text" name="purpose" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="schedule_id" id="edit_schedule_id">
                        <div class="form-group">
                            <label>Lab Name</label>
                            <input type="text" name="lab_name" id="edit_lab_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Day</label>
                            <select name="day" id="edit_day" class="form-control" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <input type="text" name="purpose" id="edit_purpose" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Schedule Modal -->
    <div class="modal fade" id="deleteScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="schedule_id" id="delete_schedule_id">
                        <p>Are you sure you want to delete this schedule?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
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
            $('#scheduleTable').DataTable({
                order: [[0, 'asc'], [1, 'asc'], [2, 'asc']],
                pageLength: 25
            });
        });

        function editSchedule(schedule) {
            $('#edit_schedule_id').val(schedule.id);
            $('#edit_lab_name').val(schedule.lab_name);
            $('#edit_day').val(schedule.day);
            $('#edit_start_time').val(schedule.start_time);
            $('#edit_end_time').val(schedule.end_time);
            $('#edit_purpose').val(schedule.purpose);
            $('#editScheduleModal').modal('show');
        }

        function deleteSchedule(scheduleId) {
            $('#delete_schedule_id').val(scheduleId);
            $('#deleteScheduleModal').modal('show');
        }
    </script>
</body>
</html> 