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
        .export-buttons .btn-group {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .export-buttons .dropdown-menu {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .export-buttons .dropdown-item {
            padding: 0.5rem 1.5rem;
        }
        .export-buttons .dropdown-item i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        .table thead th {
            position: relative;
            cursor: pointer;
            white-space: nowrap;
        }
        .table thead th.sortable:hover {
            background-color: #f8f9fa;
        }
        .table thead th i {
            margin-left: 5px;
            color: #6c757d;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .table-responsive {
                border: 0;
            }
            .table thead {
                display: none;
            }
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            .table tbody td {
                display: block;
                text-align: right;
                padding-left: 50%;
                position: relative;
                border-bottom: 1px solid #dee2e6;
            }
            .table tbody td:before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 50%;
                padding-left: 1rem;
                font-weight: bold;
                text-align: left;
            }
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
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="openExportModal('pdf')">
                                        <i class="fas fa-file-pdf text-danger"></i> Export as PDF
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="openExportModal('excel')">
                                        <i class="fas fa-file-excel text-success"></i> Export as Excel
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="openExportModal('csv')">
                                        <i class="fas fa-file-csv text-info"></i> Export as CSV
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" onclick="window.print()">
                                        <i class="fas fa-print text-secondary"></i> Print
                                    </a>
                                </div>
                            </div>
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
                            <table id="sitinTable" class="table table-hover table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th onclick="sortSitInTable(0, 'string')" style="cursor: pointer;">ID Number <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(1, 'string')" style="cursor: pointer;">Purpose <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(2, 'string')" style="cursor: pointer;">Lab <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(3, 'date')" style="cursor: pointer;">Login Time <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(4, 'date')" style="cursor: pointer;">Logout Time <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(5, 'string')" style="cursor: pointer;">Duration <i class="fas fa-sort"></i></th>
                                        <th onclick="sortSitInTable(6, 'string')" style="cursor: pointer;">Status <i class="fas fa-sort"></i></th>
                                        <th>Feedback</th>
                                        <th onclick="sortSitInTable(8, 'date')" style="cursor: pointer;">Feedback Date <i class="fas fa-sort"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch sit-in data with feedback
                                    $query = "SELECT sr.*, f.feedback_text, f.feedback_date 
                                             FROM sitin_report sr 
                                             LEFT JOIN feedback f ON sr.id = f.sitin_id 
                                             ORDER BY sr.logout_time DESC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['logout_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                        echo "<td>";
                                        if (!empty($row['feedback_text'])) {
                                            echo "<button class='btn btn-info' onclick='viewFeedback(" . $row['id'] . ")'>View Feedback</button>";
                                        } else {
                                            echo "No Feedback";
                                        }
                                        echo "</td>";
                                        echo "<td>" . htmlspecialchars($row['feedback_date']) . "</td>";
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
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="form-group">
                            <label for="exportLab">Lab:</label>
                            <select id="exportLab" class="form-control">
                                <option value="">All Labs</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo htmlspecialchars($lab); ?>">
                                        <?php echo htmlspecialchars($lab); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exportPurpose">Purpose:</label>
                            <select id="exportPurpose" class="form-control">
                                <option value="">All Purposes</option>
                                <option value="C Programming">C Programming</option>
                                <option value="Java Programming">Java Programming</option>
                                <option value="Python">Python</option>
                                <option value="C# Database">C# Database</option>
                                <option value="Digital Logic & Design">Digital Logic & Design</option>
                                <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                                <option value="System Integration and Architecture">System Integration and Architecture</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exportFromDate">From Date:</label>
                            <input type="date" id="exportFromDate" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="exportToDate">To Date:</label>
                            <input type="date" id="exportToDate" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmExport">Export</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
            <h2>View Feedback</h2>
            <div id="feedbackContent"></div>
        </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#sitinTable').DataTable({
                "order": [[3, "desc"]], // Default sort by login time descending
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                }
            });

            // Custom search functionality
            $('#searchInput').on('keyup', function() {
                $('#sitinTable').DataTable().search(this.value).draw();
            });

            // Date filter functionality
            function applyDateFilter() {
                const fromDate = $('#fromDate').val();
                const toDate = $('#toDate').val();
                
                if (fromDate && toDate) {
                    const table = $('#sitinTable').DataTable();
                    table.column(3).search(fromDate + '|' + toDate, true, false).draw();
                }
            }

            function resetDateFilter() {
                $('#fromDate').val('');
                $('#toDate').val('');
                $('#sitinTable').DataTable().search('').draw();
            }

            // Export modal functionality
            function openExportModal(type) {
                // Implementation for export modal
                // This can be expanded based on your export requirements
                alert('Export functionality will be implemented here');
            }

            // View feedback functionality
            function viewFeedback(sitinId) {
                // Implementation for viewing feedback
                // This can be expanded based on your feedback viewing requirements
                alert('Feedback viewing functionality will be implemented here');
            }

            // Sorting functionality
            function sortSitInTable(column, type) {
                const table = $('#sitinTable').DataTable();
                const currentOrder = table.order();
                
                // If clicking the same column, reverse the order
                if (currentOrder[0][0] === column) {
                    table.order([column, currentOrder[0][1] === 'asc' ? 'desc' : 'asc']).draw();
                } else {
                    // Otherwise, sort by the new column in ascending order
                    table.order([column, 'asc']).draw();
                }
            }

            // Make functions available globally
            window.applyDateFilter = applyDateFilter;
            window.resetDateFilter = resetDateFilter;
            window.openExportModal = openExportModal;
            window.viewFeedback = viewFeedback;
            window.sortSitInTable = sortSitInTable;
        });
    </script>
</body>
</html> 