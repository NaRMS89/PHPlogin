<?php
// Include database connection
include 'includes/database.php';

// Check if room parameter is provided
if (!isset($_GET['room'])) {
    echo '<div class="alert alert-danger">Lab room not specified.</div>';
    exit;
}

$room = $_GET['room'];

// Get today's date
$today = date('Y-m-d');

// Get schedule for the next 7 days
$schedule_html = '<div class="table-responsive">';
$schedule_html .= '<table class="table table-bordered">';
$schedule_html .= '<thead><tr><th>Date</th><th>Time</th><th>Student</th><th>Purpose</th><th>Status</th></tr></thead>';
$schedule_html .= '<tbody>';

for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    
    // Query to get reservations for this date and room
    $sql = "SELECT r.*, i.first_name, i.last_name 
            FROM reservations r 
            JOIN info i ON r.id_number = i.id_number 
            WHERE r.lab = '$room' AND r.date = '$date' 
            ORDER BY r.start_time";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $first_row = true;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $schedule_html .= '<tr>';
            
            // Only show date in first row for each day
            if ($first_row) {
                $schedule_html .= '<td rowspan="' . mysqli_num_rows($result) . '">' . date('M d, Y', strtotime($date)) . '</td>';
                $first_row = false;
            }
            
            $schedule_html .= '<td>' . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . '</td>';
            $schedule_html .= '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
            $schedule_html .= '<td>' . htmlspecialchars($row['purpose']) . '</td>';
            $schedule_html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
            $schedule_html .= '</tr>';
        }
    } else {
        $schedule_html .= '<tr>';
        $schedule_html .= '<td>' . date('M d, Y', strtotime($date)) . '</td>';
        $schedule_html .= '<td colspan="4" class="text-center">No reservations</td>';
        $schedule_html .= '</tr>';
    }
}

$schedule_html .= '</tbody></table></div>';

echo $schedule_html;
?> 