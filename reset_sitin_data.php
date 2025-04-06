<?php
require_once '../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $pdo->beginTransaction();

    // Delete all records from sitin_report table
    $stmt = $pdo->prepare("DELETE FROM sitin_report");
    $stmt->execute();

    // Reset auto-increment counter
    $stmt = $pdo->prepare("ALTER TABLE sitin_report AUTO_INCREMENT = 1");
    $stmt->execute();

    // Commit transaction
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Sit-in data has been reset successfully']);

} catch(PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 