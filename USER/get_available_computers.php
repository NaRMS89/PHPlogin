<?php
session_start();
include("../includes/database.php");
include("../includes/reservation_functions.php");

// Check if the user is logged in
if (!isset($_SESSION['user_data'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get parameters
$labId = isset($_GET['lab_id']) ? $_GET['lab_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$startTime = isset($_GET['start_time']) ? $_GET['start_time'] : null;
$endTime = isset($_GET['end_time']) ? $_GET['end_time'] : null;

// Validate parameters
if (!$labId || !$date || !$startTime || !$endTime) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// Connect to DB and query lab_computers table
$response = ['available' => [], 'unavailable' => []];

try {
    // Use PDO connection from database.php (assuming it's stored in $pdo)
    if (!isset($pdo)) {
        throw new Exception("Database connection not established.");
    }

    // Prepare SQL statement to get computers for the selected lab
    $sql = "SELECT computer_number, status FROM lab_computers WHERE lab_id = :lab_id ORDER BY computer_number ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':lab_id', $labId, PDO::PARAM_INT); // Assuming lab_id is numeric
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results
    $all_computers = range(1, 50); // Assume 50 computers per lab
    $db_computers = [];

    foreach ($results as $row) {
        $num = (int)$row['computer_number'];
        $db_computers[] = $num; // Keep track of computers listed in DB for this lab
        if (strtolower($row['status']) === 'available') {
            $response['available'][] = $num;
        } else {
            $response['unavailable'][] = $num;
        }
    }
    
    // Add any computers from 1-50 not explicitly listed in the DB for this lab 
    // as 'unavailable' by default (or adjust logic as needed)
    $missing_computers = array_diff($all_computers, $db_computers);
    $response['unavailable'] = array_merge($response['unavailable'], array_values($missing_computers));
    sort($response['unavailable']); // Keep the list sorted

} catch (PDOException $e) {
    // Log error - don't expose details to the client in production
    error_log("Database Error: " . $e->getMessage());
    // Return empty response or a generic error structure on failure
    // For simplicity here, we return the default empty response
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    // Return empty response
}

// Return JSON response directly
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
if ($conn instanceof mysqli) { 
    mysqli_close($conn); 
}
?>
