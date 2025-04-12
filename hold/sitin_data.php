<?php
session_start();
include("../includes/database.php");

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

// Get unique labs and purposes for filters
$labs_query = "SELECT DISTINCT lab FROM sitin_report ORDER BY lab";
$labs_result = mysqli_query($conn, $labs_query);
$labs = [];
while ($row = mysqli_fetch_assoc($labs_result)) {
    $labs[] = $row['lab'];
}

$purposes_query = "SELECT DISTINCT purpose FROM sitin_report ORDER BY purpose";
$purposes_result = mysqli_query($conn, $purposes_query);
$purposes = [];
while ($row = mysqli_fetch_assoc($purposes_result)) {
    $purposes[] = $row['purpose'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitin Data - Admin Dashboard</title>
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
        .export-buttons {
            margin-bottom: 15px;
        }
        .export-buttons .btn {
            margin-right: 5px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .date-filter {
            margin-bottom: 15px;
        }
        .date-filter input {
            width: 200px;
            margin-right: 10px;
        }
        .modal-header {
            background-color: #f8f9fa;
        }
        .filter-section {
            margin-bottom: 15px;
        }
        .filter-section label {
            font-weight: bold;
            margin-bottom: 5px;
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
                        <h4 class="mb-0">Sitin Data</h4>
                    </div>
                    <div class="card-body">
                        <!-- Search Input - Moved above the table -->
                        <div class="search-container mb-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                        </div>

                        <!-- Export Buttons -->
                        <div class="export-buttons mb-3">
                            <button class="btn btn-primary" onclick="openExportModal('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                            <button class="btn btn-success" onclick="openExportModal('excel')">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button class="btn btn-info" onclick="openExportModal('csv')">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button class="btn btn-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>

                        <!-- Date Filter -->
                        <div class="date-filter">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Date Range:</label>
                                    <input type="date" id="fromDate" class="form-control" style="display: inline-block; width: auto;">
                                    <span>to</span>
                                    <input type="date" id="toDate" class="form-control" style="display: inline-block; width: auto;">
                                    <button class="btn btn-primary" onclick="applyDateFilter()">Apply</button>
                                    <button class="btn btn-secondary" onclick="resetDateFilter()">Reset</button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table id="sitinTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Purpose</th>
                                        <th>Lab</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT 
                                        s.id_number,
                                        CONCAT(i.first_name, ' ', i.last_name) as student_name,
                                        s.purpose,
                                        s.lab,
                                        s.login_time,
                                        s.logout_time,
                                        CASE 
                                            WHEN s.logout_time IS NULL THEN 'active'
                                            ELSE 'completed'
                                        END as status
                                    FROM sitin_report s
                                    JOIN info i ON s.id_number = i.id_number
                                    ORDER BY s.login_time DESC";
                                    
                                    $result = mysqli_query($conn, $query);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Calculate duration
                                        if ($row['logout_time']) {
                                            $login = new DateTime($row['login_time']);
                                            $logout = new DateTime($row['logout_time']);
                                            $interval = $login->diff($logout);
                                            $duration = $interval->format('%H:%I');
                                        } else {
                                            $duration = 'Active';
                                        }
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
                                        echo "<td>" . ($row['logout_time'] ? htmlspecialchars($row['logout_time']) : '-') . "</td>";
                                        echo "<td>" . $duration . "</td>";
                                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
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

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Options</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="filter-section">
                        <label>Lab:</label>
                        <select id="exportLab" class="form-control">
                            <option value="">All Labs</option>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo htmlspecialchars($lab); ?>">
                                    <?php echo htmlspecialchars($lab); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-section">
                        <label>Purpose:</label>
                        <select id="exportPurpose" class="form-control">
                            <option value="">All Purposes</option>
                            <?php foreach ($purposes as $purpose): ?>
                                <option value="<?php echo htmlspecialchars($purpose); ?>">
                                    <?php echo htmlspecialchars($purpose); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-section">
                        <label>Date Range:</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" id="exportFromDate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="date" id="exportToDate" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmExport">Export</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap.min.js"></script>
    <script>
        let sitinTable;
        let exportType = '';

        $(document).ready(function() {
            // Initialize DataTable
            sitinTable = $('#sitinTable').DataTable({
                order: [[4, 'desc']], // Sort by login time by default
                pageLength: 25,
                dom: '<"top"f>rt<"bottom"lip><"clear">'
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                sitinTable.search(this.value).draw();
            });
        });

        function openExportModal(type) {
            exportType = type;
            $('#exportModal').modal('show');
        }

        $('#confirmExport').click(function() {
            const lab = $('#exportLab').val();
            const purpose = $('#exportPurpose').val();
            const fromDate = $('#exportFromDate').val();
            const toDate = $('#exportToDate').val();

            // Create the export URL with parameters
            let exportUrl = `export_sitin_data.php?type=${exportType}`;
            if (lab) exportUrl += `&lab=${encodeURIComponent(lab)}`;
            if (purpose) exportUrl += `&purpose=${encodeURIComponent(purpose)}`;
            if (fromDate) exportUrl += `&fromDate=${encodeURIComponent(fromDate)}`;
            if (toDate) exportUrl += `&toDate=${encodeURIComponent(toDate)}`;

            // Open in new window/tab
            window.open(exportUrl, '_blank');
            $('#exportModal').modal('hide');
        });

        function applyDateFilter() {
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();
            
            if (fromDate && toDate) {
                sitinTable.draw();
            }
        }

        function resetDateFilter() {
            $('#fromDate').val('');
            $('#toDate').val('');
            sitinTable.draw();
        }
    </script>
</body>
</html> 