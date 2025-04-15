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
                                        <th onclick="sortTable('student_id', 'number')" style="cursor: pointer;" data-sort="desc">Student ID ↓</th>
                                        <th>Purpose</th>
                                        <th onclick="sortTable('lab', 'number')" style="cursor: pointer;" data-sort="desc">Lab ↓</th>
                                        <th onclick="sortTable('login_time', 'date')" style="cursor: pointer;" data-sort="desc">Login Time ↓</th>
                                        <th onclick="sortTable('logout_time', 'date')" style="cursor: pointer;" data-sort="desc">Logout Time ↓</th>
                                        <th onclick="sortTable('duration', 'number')" style="cursor: pointer;" data-sort="desc">Duration ↓</th>
                                        <th>Feedback</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch sit-in data with feedback
                                    $query = "SELECT sr.*, f.feedback_text 
                                             FROM sitin_report sr 
                                             LEFT JOIN feedback f ON sr.id = f.sitin_id 
                                             ORDER BY sr.logout_time DESC";
                                    $result = mysqli_query($conn, $query);
                                    
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['login_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['logout_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                                        echo "<td>";
                                        if (!empty($row['feedback_text'])) {
                                            echo "<button class='btn btn-info' onclick='viewFeedback(" . $row['id'] . ")'>View Feedback</button>";
                                        } else {
                                            echo "No Feedback";
                                        }
                                        echo "</td>";
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
                            <option value="C Programming">C Programming</option>
                            <option value="Java Programming">Java Programming</option>
                            <option value="Python">Python</option>
                            <option value="C# Database">C# Database</option>
                            <option value="Digital Logic & Design">Digital Logic & Design</option>
                            <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                            <option value="System Integration and Architecture">System Integration and Architecture</option>
                            <option value="Computer Application">Computer Application</option>
                            <option value="Project Management">Project Management</option>
                            <option value="IT Trend">IT Trend</option>
                            <option value="Technopreneurship">Technopreneurship</option>
                            <option value="Capstone">Capstone</option>
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

        function viewFeedback(sitinId) {
            // Fetch and display feedback
            fetch('get_feedback_data.php?id=' + sitinId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('feedbackContent').innerHTML = data.feedback_text;
                    document.getElementById('feedbackModal').style.display = 'block';
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function sortTable(column, type) {
            const table = document.getElementById('sitinTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            const header = table.querySelector(`th[onclick*="${column}"]`);
            const currentSort = header.getAttribute('data-sort') || 'desc';
            const newSort = currentSort === 'asc' ? 'desc' : 'asc';
            
            // Reset all headers
            table.querySelectorAll('th[onclick]').forEach(th => {
                const text = th.textContent;
                th.textContent = text.replace(' ↑', ' ↓').replace(' ↓', ' ↓');
            });
            
            // Update current header
            header.textContent = header.textContent.replace(' ↓', newSort === 'asc' ? ' ↑' : ' ↓');
            header.setAttribute('data-sort', newSort);

            rows.sort((a, b) => {
                let aValue = a.cells[Array.from(a.parentNode.parentNode.getElementsByTagName('th')).findIndex(th => th.getAttribute('onclick')?.includes(column))].textContent.trim();
                let bValue = b.cells[Array.from(b.parentNode.parentNode.getElementsByTagName('th')).findIndex(th => th.getAttribute('onclick')?.includes(column))].textContent.trim();

                if (type === 'number') {
                    aValue = parseFloat(aValue) || 0;
                    bValue = parseFloat(bValue) || 0;
                } else if (type === 'date') {
                    aValue = new Date(aValue).getTime();
                    bValue = new Date(bValue).getTime();
                }

                if (newSort === 'asc') {
                    return aValue > bValue ? 1 : -1;
                } else {
                    return aValue < bValue ? 1 : -1;
                }
            });

            rows.forEach(row => tbody.appendChild(row));
        }
    </script>
</body>
</html> 