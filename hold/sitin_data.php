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

// Fetch all sit-in report data
$query = "SELECT * FROM sitin_report ORDER BY logout_time DESC";
$result = mysqli_query($conn, $query);
$sitinData = [];
$purposeCounts = [];
$labCounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sitinData[] = $row;
    // Count purposes
    $purpose = $row['purpose'];
    if (!isset($purposeCounts[$purpose])) $purposeCounts[$purpose] = 0;
    $purposeCounts[$purpose]++;
    // Count labs
    $lab = $row['lab'];
    if (!isset($labCounts[$lab])) $labCounts[$lab] = 0;
    $labCounts[$lab]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Data</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-row { display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
        .chart-box { flex: 1 1 300px; background: #23233a; border-radius: 12px; padding: 20px; min-width: 280px; }
        .chart-box h3 { text-align: center; color: #fff; margin-bottom: 10px; }
        .table-container { background: #23233a; border-radius: 12px; padding: 20px; }
        table { color: #fff; }
        th, td { vertical-align: middle !important; }
        th { background: #3a3a5a; }
        .feedback-cell { max-width: 200px; white-space: pre-wrap; word-break: break-word; }
        body { background: #181828; color: #fff; }
    </style>
</head>
<body>
    <?php include("../includes/admin_header.php"); ?>

    <div class="container mt-4">
        <h2 class="mb-4">Sit-in Data</h2>
        <div class="charts-row">
            <div class="chart-box">
                <h3>Purpose Distribution</h3>
                <canvas id="purposePieChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Lab Usage Distribution</h3>
                <canvas id="labPieChart"></canvas>
            </div>
        </div>
        <div class="table-container mt-4">
            <h4 class="mb-3">Sit-in Records</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sitinData as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id_number']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td><?= htmlspecialchars($row['lab']) ?></td>
                            <td><?= htmlspecialchars($row['login_time']) ?></td>
                            <td><?= htmlspecialchars($row['logout_time']) ?></td>
                            <td><?= htmlspecialchars($row['duration']) ?></td>
                            <td class="feedback-cell"><?= $row['feedback'] ? htmlspecialchars($row['feedback']) : '<span class="text-muted">No Feedback</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Pie chart data from PHP
    const purposeLabels = <?= json_encode(array_keys($purposeCounts)) ?>;
    const purposeData = <?= json_encode(array_values($purposeCounts)) ?>;
    const labLabels = <?= json_encode(array_keys($labCounts)) ?>;
    const labData = <?= json_encode(array_values($labCounts)) ?>;

    const pieColors = [
        '#6a89cc', '#38ada9', '#e55039', '#f6b93b', '#60a3bc', '#78e08f', '#fa983a', '#e58e26', '#b71540', '#079992', '#b8e994', '#f8c291', '#fad390', '#f6b93b', '#e17055'
    ];

    new Chart(document.getElementById('purposePieChart'), {
        type: 'pie',
        data: {
            labels: purposeLabels,
            datasets: [{
                data: purposeData,
                backgroundColor: pieColors,
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#fff' } } }
        }
    });

    new Chart(document.getElementById('labPieChart'), {
        type: 'pie',
        data: {
            labels: labLabels,
            datasets: [{
                data: labData,
                backgroundColor: pieColors,
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#fff' } } }
        }
    });
    </script>
</body>
</html> 