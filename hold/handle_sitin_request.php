<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$id = $_POST['id'];
$action = $_POST['action'];

if (!in_array($action, ['approve', 'deny'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get the request details
    $query = "SELECT * FROM sitin_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Request not found');
    }

    if ($request['status'] !== 'pending') {
        throw new Exception('Request is not pending');
    }

    // Update the request status
    $status = $action === 'approve' ? 'approved' : 'denied';
    $query = "UPDATE sitin_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$status, $id]);

    // If approved, update computer status
    if ($action === 'approve') {
        $query = "UPDATE computers 
                 SET status = 'in_use', 
                     student_id = ? 
                 WHERE lab = ? AND pc_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$request['student_id'], $request['lab'], $request['pc_number']]);
    }

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Request ' . $status . ' successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 