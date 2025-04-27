<?php
// Reservation-related functions

/**
 * Get all reservations
 * @param mysqli $conn Database connection
 * @return array Array of reservation data
 */
function getAllReservations($conn) {
    $sql = "SELECT r.*, i.first_name, i.last_name 
            FROM reservations r
            JOIN info i ON r.id_number = i.id_number
            ORDER BY r.reservation_date DESC, r.start_time DESC";
    
    $result = mysqli_query($conn, $sql);
    $reservations = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Filter reservations by criteria
 * @param mysqli $conn Database connection
 * @param string $status Status to filter by (pending, approved, denied, all)
 * @param string $lab Lab to filter by
 * @param string $date Date to filter by
 * @return array Filtered reservations
 */
function filterReservations($conn, $status = 'all', $lab = 'all', $date = null) {
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($status != 'all') {
        $conditions[] = "r.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($lab != 'all') {
        $conditions[] = "r.lab_id = ?";
        $params[] = $lab;
        $types .= 's';
    }
    
    if ($date) {
        $conditions[] = "r.reservation_date = ?";
        $params[] = $date;
        $types .= 's';
    }
    
    $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
    
    $sql = "SELECT r.*, i.first_name, i.last_name 
            FROM reservations r
            JOIN info i ON r.id_number = i.id_number
            $whereClause
            ORDER BY r.reservation_date DESC, r.start_time DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservations = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Update reservation status
 * @param mysqli $conn Database connection
 * @param int $reservationId Reservation ID
 * @param string $status New status (approved, denied)
 * @return bool Success status
 */
function updateReservationStatus($conn, $reservationId, $status) {
    // If approving, check for conflicts
    if ($status === 'approved') {
        $sql = "SELECT lab_id, computer_number, reservation_date, start_time, end_time 
                FROM reservations 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $reservationId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Check for conflicting approved reservations
            $checkSql = "SELECT id FROM reservations 
                         WHERE lab_id = ? AND computer_number = ? AND reservation_date = ? 
                         AND status = 'approved' 
                         AND ((start_time <= ? AND end_time >= ?) OR 
                              (start_time <= ? AND end_time >= ?) OR 
                              (start_time >= ? AND end_time <= ?))
                         AND id != ?";
            
            $stmt = mysqli_prepare($conn, $checkSql);
            mysqli_stmt_bind_param($stmt, 'sissssssi', 
                                  $row['lab_id'], 
                                  $row['computer_number'], 
                                  $row['reservation_date'], 
                                  $row['end_time'], 
                                  $row['start_time'], 
                                  $row['start_time'], 
                                  $row['end_time'], 
                                  $row['start_time'], 
                                  $row['end_time'], 
                                  $reservationId);
            mysqli_stmt_execute($stmt);
            $conflictResult = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($conflictResult) > 0) {
                // Conflict found, cannot approve
                return false;
            }
            
            // Update computer status
            $updateComputerSql = "UPDATE lab_computers 
                                  SET status = 'reserved' 
                                  WHERE lab_id = ? AND computer_number = ?";
            
            $stmt = mysqli_prepare($conn, $updateComputerSql);
            mysqli_stmt_bind_param($stmt, 'si', $row['lab_id'], $row['computer_number']);
            mysqli_stmt_execute($stmt);
        }
    }
    
    // Update reservation status
    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $status, $reservationId);
    $result = mysqli_stmt_execute($stmt);
    
    return $result;
}

/**
 * Get available computers in a lab
 * @param mysqli $conn Database connection
 * @param string $labId Lab ID
 * @param string $date Reservation date
 * @param string $startTime Reservation start time
 * @param string $endTime Reservation end time
 * @return array Available computers
 */
function getAvailableComputers($conn, $labId, $date, $startTime, $endTime) {
    // Get all computers in the lab
    $sql = "SELECT * FROM lab_computers WHERE lab_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $labId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $computers = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $computers[$row['computer_number']] = [
                'id' => $row['id'],
                'status' => $row['status']
            ];
        }
    } else {
        // If no computers found in the database for this lab, generate 50 computers
        for ($i = 1; $i <= 50; $i++) {
            $computers[$i] = [
                'id' => 0, // Placeholder ID
                'status' => 'available'
            ];
        }
    }
    
    // Get reserved computers for the specified time
    $sql = "SELECT computer_number FROM reservations 
            WHERE lab_id = ? AND reservation_date = ? AND status = 'approved' 
            AND ((start_time <= ? AND end_time >= ?) OR 
                 (start_time <= ? AND end_time >= ?) OR 
                 (start_time >= ? AND end_time <= ?))";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssssss', 
                          $labId, 
                          $date, 
                          $endTime, 
                          $startTime, 
                          $startTime, 
                          $endTime, 
                          $startTime, 
                          $endTime);
    mysqli_stmt_execute($stmt);
    $reservedResult = mysqli_stmt_get_result($stmt);
    
    // Mark reserved computers
    while ($row = mysqli_fetch_assoc($reservedResult)) {
        if (isset($computers[$row['computer_number']])) {
            $computers[$row['computer_number']]['status'] = 'reserved';
        }
    }
    
    return $computers;
}

/**
 * Create a new reservation
 * @param mysqli $conn Database connection
 * @param string $idNumber Student ID
 * @param string $labId Lab ID
 * @param int $computerNumber Computer number
 * @param string $purpose Purpose
 * @param string $date Reservation date
 * @param string $startTime Start time
 * @param string $endTime End time
 * @return bool|string Success status or error message
 */
function createReservation($conn, $idNumber, $labId, $computerNumber, $purpose, $date, $startTime, $endTime) {
    // Check if the student has remaining sessions
    $sql = "SELECT sessions FROM info WHERE id_number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $idNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['sessions'] <= 0) {
            return "You have no remaining sessions.";
        }
    } else {
        return "Student not found.";
    }
    
    // Check if computer is available
    $computers = getAvailableComputers($conn, $labId, $date, $startTime, $endTime);
    
    if (!isset($computers[$computerNumber]) || $computers[$computerNumber]['status'] !== 'available') {
        return "The selected computer is not available for the specified time.";
    }
    
    // Create the reservation
    $sql = "INSERT INTO reservations (id_number, lab_id, computer_number, purpose, reservation_date, start_time, end_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssissss', 
                          $idNumber, 
                          $labId, 
                          $computerNumber, 
                          $purpose, 
                          $date, 
                          $startTime, 
                          $endTime);
    
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        return true;
    } else {
        return "Error creating reservation: " . mysqli_error($conn);
    }
}
