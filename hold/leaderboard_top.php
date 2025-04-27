<?php
// Get top students for leaderboard
function getTopStudents($conn, $limit = 3) {
    // Update total points first
    updateTotalPoints($conn);
    
    // Get all students
    $sql = "SELECT id_number, first_name, last_name, course, total_points FROM info 
            WHERE total_points > 0 
            ORDER BY total_points DESC 
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $topStudents = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $topStudents[] = $row;
        }
    }
    
    return $topStudents;
}

// Display top students leaderboard
function displayTopStudentsLeaderboard($conn) {
    $topStudents = getTopStudents($conn, 3);
    
    echo '<div class="leaderboard-container">';
    echo '<h3>Top Students Leaderboard</h3>';
    echo '<div class="leaderboard-table">';
    echo '<table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody>';
    
    if (count($topStudents) > 0) {
        foreach ($topStudents as $index => $student) {
            $rankClass = '';
            $medal = '';
            
            if ($index === 0) {
                $rankClass = 'rank-first';
                $medal = '<span class="medal">🥇</span>';
            } else if ($index === 1) {
                $rankClass = 'rank-second';
                $medal = '<span class="medal">🥈</span>';
            } else if ($index === 2) {
                $rankClass = 'rank-third';
                $medal = '<span class="medal">🥉</span>';
            }
            
            echo '<tr class="' . $rankClass . '">
                    <td>' . ($index + 1) . ' ' . $medal . '</td>
                    <td>' . $student['id_number'] . '</td>
                    <td>' . $student['last_name'] . ', ' . $student['first_name'] . '</td>
                    <td>' . $student['course'] . '</td>
                    <td class="point-value">' . $student['total_points'] . '</td>
                  </tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="no-data">No students with points yet</td></tr>';
    }
    
    echo '</tbody></table></div></div>';
}
?>
