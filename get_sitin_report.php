<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$startDate = isset($_GET['start']) ? $_GET['start'] : '';
$endDate = isset($_GET['end']) ? $_GET['end'] : '';
$reportType = isset($_GET['type']) ? $_GET['type'] : 'daily';

$sql = "SELECT 
            DATE(login_time) as date,
            COUNT(*) as total_sitins,
            COUNT(DISTINCT id_number) as active_users,
            MAX(CASE WHEN lab_count = max_lab_count THEN lab ELSE NULL END) as most_used_lab,
            MAX(CASE WHEN purpose_count = max_purpose_count THEN purpose ELSE NULL END) as most_used_purpose,
            AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as duration
        FROM (
            SELECT 
                s.*,
                COUNT(*) OVER (PARTITION BY DATE(login_time), lab) as lab_count,
                MAX(COUNT(*)) OVER (PARTITION BY DATE(login_time)) as max_lab_count,
                COUNT(*) OVER (PARTITION BY DATE(login_time), purpose) as purpose_count,
                MAX(COUNT(*)) OVER (PARTITION BY DATE(login_time)) as max_purpose_count
            FROM sitin_report s
            WHERE 1=1";

$params = [];
$types = '';

if (!empty($startDate)) {
    $sql .= " AND DATE(login_time) >= ?";
    $params[] = $startDate;
    $types .= 's';
}

if (!empty($endDate)) {
    $sql .= " AND DATE(login_time) <= ?";
    $params[] = $endDate;
    $types .= 's';
}

$sql .= ") as subquery GROUP BY DATE(login_time)";

switch ($reportType) {
    case 'weekly':
        $sql = "SELECT 
                    DATE(DATE_SUB(login_time, INTERVAL WEEKDAY(login_time) DAY)) as date,
                    COUNT(*) as total_sitins,
                    COUNT(DISTINCT id_number) as active_users,
                    MAX(CASE WHEN lab_count = max_lab_count THEN lab ELSE NULL END) as most_used_lab,
                    MAX(CASE WHEN purpose_count = max_purpose_count THEN purpose ELSE NULL END) as most_used_purpose,
                    AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as duration
                FROM (
                    SELECT 
                        s.*,
                        COUNT(*) OVER (PARTITION BY YEARWEEK(login_time), lab) as lab_count,
                        MAX(COUNT(*)) OVER (PARTITION BY YEARWEEK(login_time)) as max_lab_count,
                        COUNT(*) OVER (PARTITION BY YEARWEEK(login_time), purpose) as purpose_count,
                        MAX(COUNT(*)) OVER (PARTITION BY YEARWEEK(login_time)) as max_purpose_count
                    FROM sitin_report s
                    WHERE 1=1";
        if (!empty($startDate)) {
            $sql .= " AND DATE(login_time) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(login_time) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }
        $sql .= ") as subquery GROUP BY YEARWEEK(login_time)";
        break;
        
    case 'monthly':
        $sql = "SELECT 
                    DATE_FORMAT(login_time, '%Y-%m-01') as date,
                    COUNT(*) as total_sitins,
                    COUNT(DISTINCT id_number) as active_users,
                    MAX(CASE WHEN lab_count = max_lab_count THEN lab ELSE NULL END) as most_used_lab,
                    MAX(CASE WHEN purpose_count = max_purpose_count THEN purpose ELSE NULL END) as most_used_purpose,
                    AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as duration
                FROM (
                    SELECT 
                        s.*,
                        COUNT(*) OVER (PARTITION BY DATE_FORMAT(login_time, '%Y-%m'), lab) as lab_count,
                        MAX(COUNT(*)) OVER (PARTITION BY DATE_FORMAT(login_time, '%Y-%m')) as max_lab_count,
                        COUNT(*) OVER (PARTITION BY DATE_FORMAT(login_time, '%Y-%m'), purpose) as purpose_count,
                        MAX(COUNT(*)) OVER (PARTITION BY DATE_FORMAT(login_time, '%Y-%m')) as max_purpose_count
                    FROM sitin_report s
                    WHERE 1=1";
        if (!empty($startDate)) {
            $sql .= " AND DATE(login_time) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(login_time) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }
        $sql .= ") as subquery GROUP BY DATE_FORMAT(login_time, '%Y-%m')";
        break;
}

$sql .= " ORDER BY date DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$reportData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reportData[] = [
        'date' => $row['date'],
        'total_sitins' => (int)$row['total_sitins'],
        'active_users' => (int)$row['active_users'],
        'most_used_lab' => $row['most_used_lab'],
        'most_used_purpose' => $row['most_used_purpose'],
        'duration' => round($row['duration'], 2)
    ];
}

header('Content-Type: application/json');
echo json_encode($reportData);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 