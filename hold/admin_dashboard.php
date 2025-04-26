<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../user/index.php");
    exit();
}

function getStudentData($idNo, $conn) {
    $sql = "SELECT * FROM info WHERE id_number = '$idNo'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

function getAllStudents($conn) {
    $sql = "SELECT * FROM info";
    $result = mysqli_query($conn, $sql);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function getCurrentSitInStudents($conn) {
    $sql = "SELECT s.id as sitin_id, s.id_number, s.purpose, s.lab, s.status, 
            i.first_name, i.last_name, i.sessions, f.feedback_text, f.feedback_date
            FROM sitin s 
            JOIN info i ON s.id_number = i.id_number 
            LEFT JOIN feedback f ON s.id = f.sit_in_id
            WHERE s.status = 'active' 
            ORDER BY s.id DESC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Error in getCurrentSitInStudents: " . mysqli_error($conn));
        return [];
    }
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function checkExistingSitIn($idNo, $conn) {
    $sql = "SELECT COUNT(*) as count FROM sitin WHERE id_number = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $idNo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
}

function addStudentToSitIn($idNo, $purpose, $lab, $conn) {
    // Check if student is already in sit-in
    if (checkExistingSitIn($idNo, $conn)) {
        return ['success' => false, 'message' => 'Student is already in sit-in'];
    }

    // Check remaining sessions
    $sql = "SELECT sessions FROM info WHERE id_number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $idNo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['sessions'] <= 0) {
        return ['success' => false, 'message' => 'No remaining sessions available'];
    }

    // Begin transaction
    mysqli_begin_transaction($conn);
    try {
        // Insert sit-in record
        $sql = "INSERT INTO sitin (id_number, purpose, lab, status) VALUES (?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $idNo, $purpose, $lab);
        $success = mysqli_stmt_execute($stmt);

        if (!$success) {
            throw new Exception("Failed to add sit-in record");
        }

        mysqli_commit($conn);
        return ['success' => true, 'message' => 'Successfully added to sit-in'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error in addStudentToSitIn: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error adding student to sit-in'];
    }
}

function removeStudentFromSitIn($idNo, $conn) {
    mysqli_begin_transaction($conn);
    try {
        // Update sit-in status to inactive
        $sql = "UPDATE sitin SET status = 'inactive' WHERE id_number = ? AND status = 'active'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $idNo);
        $success = mysqli_stmt_execute($stmt);

        if (!$success) {
            throw new Exception("Failed to update sit-in status");
        }

        // Decrease sessions count
        $sql = "UPDATE info SET sessions = sessions - 1 WHERE id_number = ? AND sessions > 0";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $idNo);
        $success = mysqli_stmt_execute($stmt);

        if (!$success) {
            throw new Exception("Failed to update sessions");
        }

        // Add to sit-in report
        $sql = "INSERT INTO sitin_report (id_number, purpose, lab, logout_time) 
                SELECT id_number, purpose, lab, NOW() 
                FROM sitin 
                WHERE id_number = ? AND status = 'inactive'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $idNo);
        $success = mysqli_stmt_execute($stmt);

        if (!$success) {
            throw new Exception("Failed to add to sit-in report");
        }

        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error in removeStudentFromSitIn: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout_sitin'])) {
    $success = removeStudentFromSitIn($_POST['id_number'], $conn);
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Student successfully timed out']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error timing out student']);
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sitin'])) {
    $result = addStudentToSitIn($_POST['id_number'], $_POST['purpose'], $_POST['lab'], $conn);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    // Implement adding a new student
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_sessions'])) {
    if ($conn instanceof mysqli) {
        // Update sessions based on course
        $sql = "UPDATE info SET sessions = 
                CASE 
                    WHEN course IN ('BSIT', 'BSCS') THEN 30 
                    ELSE 15 
                END";
        
        if (!mysqli_query($conn, $sql)) {
            error_log("Failed to reset sessions: " . mysqli_error($conn));
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to reset sessions. Please try again later.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Successfully reset sessions (30 for BSIT/BSCS, 15 for others).']);
        }
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection not available.']);
        exit();
    }
}

if ($conn instanceof mysqli) {
    $currentSitInStudents = getCurrentSitInStudents($conn);
    $allStudents = getAllStudents($conn);
} else {
    error_log("Database connection failed.");
    die("Could not connect to the database.");
}

if (isset($_POST['export_excel'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="sit_in_report.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array('ID Number', 'Student Name', 'Lab Room', 'Purpose', 'Login Time', 'Logout Time', 'Duration', 'Feedback'));
    
    // Fetch data from database
    $query = "SELECT sr.*, i.name 
              FROM sitin_report sr 
              JOIN info i ON sr.id_number = i.id_number 
              WHERE sr.login_time BETWEEN ? AND ? 
              ORDER BY sr.login_time DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add data rows
    while ($data = $result->fetch_assoc()) {
        $row = array(
            $data['id_number'],
            $data['name'],
            $data['lab'],
            $data['purpose'],
            $data['login_time'],
            $data['logout_time'],
            $data['duration'],
            $data['feedback']
        );
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function addFeedback($sitInId, $feedbackText, $conn) {
    $sql = "INSERT INTO feedback (sit_in_id, feedback_text, feedback_date) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $sitInId, $feedbackText);
    return mysqli_stmt_execute($stmt);
}

function getFeedback($sitInId, $conn) {
    $sql = "SELECT * FROM feedback WHERE sit_in_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $sitInId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Add this function for exporting Sit-in Data to PDF
function exportSitInDataToPDF($data) {
    require_once __DIR__ . '/vendor/autoload.php'; // Adjust the path if necessary
    $pdf = new \TCPDF();

    // Set document information
    $pdf->SetCreator('Admin Dashboard');
    $pdf->SetAuthor('System Admin');
    $pdf->SetTitle('Sit-in Data Report');
    $pdf->SetSubject('Sit-in Data');
    $pdf->SetKeywords('Sit-in, Report, PDF');

    // Add a page
    $pdf->AddPage();

    // Set header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sit-in Data Report', 0, 1, 'C');

    // Add table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, 'ID Number', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Purpose', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Lab', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Login Time', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Logout Time', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Duration', 1, 1, 'C', 1);

    // Add table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($data as $row) {
        $pdf->Cell(30, 7, $row['id_number'], 1);
        $pdf->Cell(40, 7, $row['purpose'], 1);
        $pdf->Cell(30, 7, $row['lab'], 1);
        $pdf->Cell(40, 7, $row['login_time'], 1);
        $pdf->Cell(40, 7, $row['logout_time'], 1);
        $pdf->Cell(20, 7, $row['duration'], 1, 1);
    }

    // Output PDF
    $pdf->Output('sit_in_data_report.pdf', 'D');
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $sitInId = $_POST['sit_in_id'];
    $feedbackText = $_POST['feedback_text'];
    
    if (addFeedback($sitInId, $feedbackText, $conn)) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error submitting feedback']);
    }
    exit();
}

// Handle feedback retrieval
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['get_feedback'])) {
    $sitInId = $_GET['sit_in_id'];
    $feedback = getFeedback($sitInId, $conn);
    echo json_encode($feedback);
    exit();
}

// Handle export request
if (isset($_POST['export_sitindata_pdf'])) {
    // Fetch data for export
    $sitInData = []; // Replace with actual data fetching logic
    exportSitInDataToPDF($sitInData);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Base styles */
        :root {
            --light: hsl(220, 50%, 90%);
            --primary: hsl(255, 30%, 55%);
            --focus: hsl(210, 90%, 50%);
            --border-color: hsla(0, 0%, 100%, .2);
            --global-background: hsl(220, 25%, 10%);
            --background: linear-gradient(to right, hsl(210, 30%, 20%), hsl(255, 30%, 25%));
            --shadow-1: hsla(236, 50%, 50%, .3);
            --shadow-2: hsla(236, 50%, 50%, .4);
        }

        *,
        *::after,
        *::before {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Open Sans', sans-serif;
            color: var(--light);
            background: var(--global-background);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background: var(--background);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.2);
        }

        .sidebar-button {
            display: block;
            width: 90%;
            margin: 10px auto;
            padding: 12px 20px;
            background: transparent;
            color: var(--light);
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            font-size: 1.4rem;
            letter-spacing: 0.2rem;
        }

        .sidebar-button:hover {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateX(5px);
        }

        /* Main Content Styles */
        main {
            margin-left: 270px;
            padding: 20px;
            min-height: 100vh;
            background: var(--global-background);
        }

        /* Modal Styles */
        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            display: none;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .modal {
            width: 60rem;
            padding: 4rem 2rem;
            border-radius: .8rem;
            color: var(--light);
            background: var(--background);
            box-shadow: .4rem .4rem 10.2rem .2rem var(--shadow-1);
            position: relative;
            overflow: hidden;
        }

        .modal h2, .modal h3 {
            font-size: 3.2rem;
            color: var(--light);
            margin-bottom: 2rem;
        }

        .close {
            width: 4rem;
            height: 4rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: var(--light);
            font-size: 2.2rem;
            position: absolute;
            top: 2rem;
            right: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: .2s;
        }

        .close:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-.2rem);
        }

        /* Form Styles */
        .modal form {
            margin-top: 2rem;
        }

        .modal input[type="text"],
        .modal input[type="email"],
        .modal input[type="password"],
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 1rem 1.6rem;
            margin: 0.8rem 0 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1.4rem;
            transition: .2s;
        }

        .modal input[type="text"]:focus,
        .modal input[type="email"]:focus,
        .modal input[type="password"]:focus,
        .modal select:focus,
        .modal textarea:focus {
            border-color: var(--focus);
            box-shadow: 0 0 0 2px var(--shadow-1);
            outline: none;
        }

        .modal select option {
            background: var(--global-background);
            color: var(--light);
        }

        .modal input[type="submit"],
        .modal button {
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: var(--light);
            background: transparent;
            font-size: 1.4rem;
            letter-spacing: .2rem;
            transition: .2s;
            cursor: pointer;
            margin-top: 2rem;
        }

        .modal input[type="submit"]:hover,
        .modal button:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-.2rem);
        }

        .feedback-content {
            margin: 2rem 0;
            font-size: 1.6rem;
            line-height: 1.6;
        }

        .feedback-details {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
        }

        .feedback-details p {
            margin: 1rem 0;
        }

        .no-feedback {
            text-align: center;
            padding: 2rem;
            color: var(--light);
            font-style: italic;
            font-size: 1.6rem;
        }

        #courseFilter {
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: var(--light);
            background: rgba(255, 255, 255, 0.1);
            font-size: 1.4rem;
            margin: 1rem 0;
            cursor: pointer;
        }

        #courseFilter:focus {
            border-color: var(--focus);
            outline: none;
        }

        /* Form Message Styles */
        #form-message {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 0.4rem;
            font-size: 1.4rem;
        }

        #form-message.success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
        }

        #form-message.error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--background);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
        }

        th, td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--light);
        }

        th {
            background: var(--primary);
            color: var(--light);
            font-weight: 600;
            letter-spacing: 0.1rem;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Search Results */
        .search-result-item {
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .search-result-item:hover {
            background: var(--focus);
            transform: translateY(-0.2rem);
        }

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
            text-align: center;
            color: var(--light);
        }

        .modal-title {
            font-size: 2.4rem;
            color: var(--light);
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Chart Container */
        .chart-container {
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
            margin-bottom: 30px;
            height: 400px;
        }

        /* Announcement Styles */
        .announcement-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px auto;
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
            width: 100%;
            max-width: 1200px;
        }

        .announcement-form {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            border-right: 1px solid var(--border-color);
        }

        .announcement-list {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .announcement-scroll {
            height: 300px;
            overflow-y: auto;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .announcement-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 0.2s ease;
        }

        .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .announcement-text {
            color: var(--light);
            font-size: 1.4rem;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .announcement-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.2rem;
            text-align: right;
            font-style: italic;
            margin-top: auto;
        }

        /* Responsive styles for announcements */
        @media (max-width: 768px) {
            .announcement-container {
                grid-template-columns: 1fr;
            }
            
            .announcement-form {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 20px;
            }
        }

        /* Filter and Header Styles */
        .student-header {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            flex: 1;
        }

        .filter-controls select {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            min-width: 150px;
            font-size: 1rem;
        }

        .filter-controls select:hover {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        .student-search-container {
            flex: 2;
            min-width: 300px;
        }

        .student-search-container input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1rem;
        }

        .student-search-container input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        /* Button Styles */
        #addStudentBtn, 
        button[onclick="resetSessions()"] {
            padding: 10px 20px;
            font-size: 1rem;
            min-width: 120px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--light);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #addStudentBtn:hover, 
        button[onclick="resetSessions()"]:hover {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary);
            transform: translateY(-2px);
        }

        /* Modal Form Styles */
        #addStudentForm {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            padding: 2rem;
        }

        #addStudentForm > div {
            display: flex;
            flex-direction: column;
        }

        #addStudentForm label {
            color: var(--light);
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }

        #addStudentForm input[type="submit"] {
            grid-column: span 2;
            width: 50%;
            margin: 2rem auto 0;
        }

        #addStudentForm input,
        #addStudentForm select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        #addStudentForm input:focus,
        #addStudentForm select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        #addStudentForm input:not(:focus):not(:placeholder-shown):valid {
            border-color: var(--border-color);
        }

        #addStudentForm input:not(:focus):not(:placeholder-shown):invalid {
            border-color: var(--border-color);
        }

        /* Modal Header Style */
        .modal h2 {
            color: var(--light);
            margin-bottom: 25px;
            font-size: 1.5rem;
            text-align: center;
        }

        /* Table Header Style */
        .student-list table th {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            padding: 15px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Logout Button Styles */
        #logoutBtn {
            margin-top: auto;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--light);
            padding: 12px 20px;
            border-radius: 100rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 90%;
            margin: 10px auto;
            text-align: left;
            font-size: 1.4rem;
            letter-spacing: 0.2rem;
        }

        #logoutBtn:hover {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateX(5px);
        }

        #logoutBtn:active {
            transform: translateX(0);
        }

        /* Logout Modal Styles */
        #logoutModal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #logoutModal .modal-content {
            background: var(--background);
            padding: 3rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
        }

        #logoutModal p {
            font-size: 1.6rem;
            margin-bottom: 2rem;
            color: var(--light);
        }

        #logoutModal .button-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        #logoutModal button {
            min-width: 120px;
            padding: 0.8rem 1.6rem;
            border-radius: 0.4rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #logoutModal button[name="logout"] {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        #logoutModal button[name="logout"]:hover {
            background: transparent;
            color: var(--primary);
            box-shadow: 0 0 15px var(--primary);
        }

        #logoutModal button[type="button"] {
            background: transparent;
            color: var(--light);
            border: 1px solid var(--border-color);
        }

        #logoutModal button[type="button"]:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Entries Display Styles */
        .entries-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.4rem;
            color: var(--light);
        }

        .entries-select {
            width: 6rem;
            padding: 0.4rem;
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
            cursor: pointer;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .chart-box {
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
        }

        .chart-box h3 {
            color: var(--light);
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.6rem;
        }

        canvas {
            width: 100% !important;
            height: 300px !important;
        }

        .student-list table th {
            background: var(--primary);
            color: var(--light);
            font-weight: 600;
            letter-spacing: 0.1rem;
            padding: 1.2rem 1.5rem;
            text-transform: uppercase;
            font-size: 0.9rem;
            border-bottom: 2px solid var(--border-color);
        }

        .student-list table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .student-list table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .data-table th {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Feedback Modal Styles */
        #feedbackModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        #feedbackModal .modal {
            background: var(--background);
            padding: 2rem;
            border-radius: 0.8rem;
            color: var(--light);
            box-shadow: 0.4rem 0.4rem 10.2rem 0.2rem var(--shadow-1);
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        #feedbackModal .modal-title {
            margin-top: 0;
            color: var(--light);
            font-size: 1.5rem;
        }

        #feedbackModal .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light);
        }

        .feedback-content {
            margin: 1.5rem 0;
            color: var(--light);
        }

        .feedback-details {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 0.5rem;
        }

        .feedback-details p {
            margin: 0.8rem 0;
            line-height: 1.5;
        }

        .no-feedback {
            text-align: center;
            padding: 2rem;
            color: var(--light);
            font-style: italic;
        }

        /* Form and Modal Specific Styles */
        #addStudentForm {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            padding: 2rem;
        }

        #addStudentForm > div {
            display: flex;
            flex-direction: column;
        }

        #addStudentForm label {
            color: var(--light);
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }

        #addStudentForm input[type="submit"] {
            grid-column: span 2;
            width: 50%;
            margin: 2rem auto 0;
        }

        .search-modal {
            background: var(--background);
            padding: 2rem;
            border-radius: 0.8rem;
            width: 90%;
            max-width: 50rem;
            position: relative;
            color: var(--light);
        }

        .search-form {
            margin: 2rem 0;
        }

        .search-form input {
            width: 100%;
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1.4rem;
        }

        .search-results {
            max-height: 40vh;
            overflow-y: auto;
            padding: 1rem;
        }

        .search-result-item {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-result-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Table Buttons */
        .action-button {
            padding: 0.8rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0.2rem;
        }

        .action-button:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        /* Points Modal */
        .points-modal {
            background: var(--background);
            padding: 2rem;
            border-radius: 0.8rem;
            width: 90%;
            max-width: 40rem;
            color: var(--light);
        }

        .points-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .points-form input[type="number"] {
            width: 100%;
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1.4rem;
        }

        /* Scrollbar Styles */
        .search-results::-webkit-scrollbar {
            width: 8px;
        }

        .search-results::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .search-results::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        /* Modal Close Button */
        .modal .close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            width: 3rem;
            height: 3rem;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--light);
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
        }

        .modal .close:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-2px);
        }

        /* Form Grid Layout */
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: var(--light);
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }

        /* Table and Action Buttons */
        .table-button,
        .action-button,
        .view-button,
        .edit-button,
        .delete-button {
            padding: 0.8rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0.2rem;
        }

        .table-button:hover,
        .action-button:hover,
        .view-button:hover,
        .edit-button:hover,
        .delete-button:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .view-button {
            border-color: var(--primary);
            color: var(--primary);
        }

        .edit-button {
            border-color: #28a745;
            color: #28a745;
        }

        .delete-button {
            border-color: #dc3545;
            color: #dc3545;
        }

        .view-button:hover {
            background: var(--primary);
            color: var(--light);
        }

        .edit-button:hover {
            background: #28a745;
            color: var(--light);
        }

        .delete-button:hover {
            background: #dc3545;
            color: var(--light);
        }

        /* Points Modal Styles */
        .points-modal .modal-content {
            background: var(--background);
            border: none;
            color: var(--light);
        }

        .points-form input[type="number"] {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--light);
        }

        .points-form button {
            margin-top: 2rem;
        }

        /* Announcement Button and Dropdown Styles */
        .announcement-form textarea {
            width: 100%;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 0.8rem;
            color: var(--light);
            resize: vertical;
            min-height: 100px;
        }

        .announcement-form button {
            padding: 1rem 2rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .announcement-form button:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        /* Filter Dropdowns */
        .filter-dropdown {
            padding: 1rem 2rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .filter-dropdown:hover {
            border-color: var(--focus);
        }

        .filter-dropdown:focus {
            outline: none;
            border-color: var(--focus);
            box-shadow: 0 0 0 2px var(--shadow-1);
        }

        .filter-dropdown option {
            background: var(--global-background);
            color: var(--light);
            padding: 1rem;
        }

        /* View Feedback Button */
        .view-feedback-btn {
            padding: 0.8rem 1.6rem;
            border: 1px solid var(--primary);
            border-radius: 100rem;
            background: transparent;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-feedback-btn:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        /* Add Points Button and Modal */
        .add-points-btn {
            padding: 0.8rem 1.6rem;
            border: 1px solid #28a745;
            border-radius: 100rem;
            background: transparent;
            color: #28a745;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-points-btn:hover {
            background: #28a745;
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
        }

        .points-modal {
            background: var(--background);
            padding: 2rem;
            border-radius: 0.8rem;
            color: var(--light);
        }

        .points-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .points-form input[type="number"] {
            width: 100%;
            padding: 1rem 1.6rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            color: var(--light);
            font-size: 1.4rem;
        }

        .points-form button {
            padding: 1rem 2rem;
            border: 1px solid #28a745;
            border-radius: 100rem;
            background: transparent;
            color: #28a745;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .points-form button:hover {
            background: #28a745;
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
        }

        /* Feedback Button and Modal Styles */
        .feedback-btn {
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--primary);
            border-radius: 100rem;
            background: transparent;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .feedback-btn:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .feedback-modal {
            background: var(--background);
            padding: 2.5rem;
            border-radius: 0.8rem;
            width: 90%;
            max-width: 50rem;
            position: relative;
            color: var(--light);
        }

        .feedback-modal h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--primary);
        }

        .feedback-details {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 0.6rem;
            margin-top: 1.5rem;
        }

        .feedback-details p {
            margin-bottom: 1rem;
            font-size: 1.4rem;
            line-height: 1.6;
        }

        .feedback-details strong {
            color: var(--primary);
            font-weight: 500;
        }

        .feedback-text {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.5rem;
            border-radius: 0.4rem;
            margin-top: 1rem;
            font-style: italic;
        }

        .no-feedback {
            text-align: center;
            padding: 2rem;
            color: var(--border-color);
            font-style: italic;
        }

        .loading-feedback {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--border-color);
        }

        .loading-feedback:after {
            content: '';
            width: 2rem;
            height: 2rem;
            border: 2px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            margin-left: 1rem;
            animation: loading 0.8s linear infinite;
        }

        @keyframes loading {
            to {
                transform: rotate(360deg);
            }
        }

        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--light);
        }

        .loading:after {
            content: '.';
            animation: dots 1.5s steps(5, end) infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60% { content: '...'; }
            80%, 100% { content: ''; }
        }

        /* Button Styles */
        .btn-primary,
        .feedback-button,
        .action-btn {
            padding: 0.8rem 1.6rem;
            border: 1px solid var(--primary);
            border-radius: 100rem;
            background: transparent;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.2rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.4rem;
        }

        .btn-primary:hover,
        .feedback-button:hover,
        .action-btn:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        /* Specific Button Styles */
        .manage-schedule-btn {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .manage-schedule-btn:hover {
            background: #4CAF50;
            color: var(--light);
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.4);
        }

        .add-points-btn {
            border-color: #2196F3;
            color: #2196F3;
        }

        .add-points-btn:hover {
            background: #2196F3;
            color: var(--light);
            box-shadow: 0 0 15px rgba(33, 150, 243, 0.4);
        }

        .export-btn {
            border-color: #9C27B0;
            color: #9C27B0;
        }

        .export-btn:hover {
            background: #9C27B0;
            color: var(--light);
            box-shadow: 0 0 15px rgba(156, 39, 176, 0.4);
        }

        /* Button Group Styles */
        .button-group {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Icon Button Styles */
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-icon i {
            font-size: 1.4rem;
        }

        /* Modal Base Styles */
        .modal-container {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-container.active {
            opacity: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--background);
            padding: 3rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal-container.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-content h2 {
            color: var(--light);
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .modal-content p {
            font-size: 1.6rem;
            margin-bottom: 2rem;
            color: var(--light);
        }

        .modal-content .close {
            position: absolute;
            right: 2rem;
            top: 2rem;
            font-size: 2.4rem;
            color: var(--light);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-content .close:hover {
            color: var(--primary);
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }

        .button-group button {
            min-width: 120px;
            padding: 1rem 2rem;
            border-radius: 0.4rem;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            background: transparent;
            color: var(--primary);
            box-shadow: 0 0 15px var(--primary);
        }

        .btn-secondary {
            background: transparent;
            color: var(--light);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Timeout Button Styles */
        .timeout-btn {
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--primary);
            border-radius: 100rem;
            background: transparent;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .timeout-btn:hover:not(:disabled) {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .timeout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: var(--border-color);
            color: var(--border-color);
        }

        /* Pagination Styles */
        .pagination, .sitin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.8rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .pagination button, .sitin-pagination button {
            padding: 0.8rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            background: transparent;
            color: var(--light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination button:hover:not(:disabled), .sitin-pagination button:hover:not(:disabled) {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .pagination button:disabled, .sitin-pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination span, .sitin-pagination span {
            color: var(--light);
            font-size: 1.2rem;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 100rem;
            min-width: 40px;
            text-align: center;
        }

        /* Points Modal Styles */
        .points-modal {
            background: var(--background);
            padding: 2rem;
            border-radius: 0.8rem;
            width: 90%;
            max-width: 40rem;
            position: relative;
        }

        .points-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .points-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .points-form label {
            color: var(--light);
            font-size: 1.4rem;
        }

        .points-form input[type="number"],
        .points-form textarea {
            width: 100%;
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .points-form input[type="number"]:focus,
        .points-form textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
            outline: none;
        }

        .points-form textarea {
            min-height: 100px;
            resize: vertical;
        }

        .points-form .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .points-form .modal-button {
            padding: 1rem 2rem;
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .points-form .modal-button.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--light);
        }

        .points-form .modal-button.primary:hover {
            background: transparent;
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .points-form .modal-button.secondary {
            background: transparent;
            color: var(--light);
        }

        .points-form .modal-button.secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .add-points-btn {
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--primary);
            border-radius: 100rem;
            background: transparent;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .add-points-btn:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--shadow-1);
        }

        .feedback-list {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .feedback-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .feedback-date {
            color: var(--primary);
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .feedback-text {
            white-space: pre-wrap;
            line-height: 1.5;
        }

        .student-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .student-info p {
            margin: 5px 0;
        }

        /* ... existing styles ... */
        .modal {
    background: var(--background) !important;
    border-radius: 10px !important;
    padding: 25px !important;
    max-width: 500px !important;
    width: 90% !important;
    position: relative !important;
    z-index: 10000 !important;
}

        .modal-content {
            background: #1a1b1e; /* Darker background */
            padding: 1.5rem;
            border-radius: 0.8rem;
            width: 100%;
            color: var(--light);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .modal h2 {
            color: #a495dd; /* Purple tint for the title */
            font-size: 1.2rem;
            margin-bottom: 1rem;
            text-align: left;
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.2rem;
            color: #a495dd;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        #feedbackForm textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            background: #13151a; /* Darker input background */
            border: 1px solid #2a2d35;
            border-radius: 0.4rem;
            color: var(--light);
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            width: 100%;
            padding: 0.8rem;
            background: #a495dd; /* Purple button */
            color: white;
            border: none;
            border-radius: 0.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .submit-btn:hover {
            background: #8a7ac0;
        }

        /* ... existing styles ... */
    </style>

</head>
<body>
    <div class="sidebar">
        <button id="homeBtn" class="sidebar-button">Home</button>
        <button id="searchBtn" class="sidebar-button">Search</button>
        <button id="studentBtn" class="sidebar-button">Students</button>
        <button id="sitinBtn" class="sidebar-button">Current Sit-in</button>
        <button id="sitInDataBtn" class="sidebar-button">Sit-in Data</button>
        <button id="reservationBtn" class="sidebar-button">Reservation</button>
        <button id="labResourcesBtn" class="sidebar-button">Lab Resources</button>
        <button id="labSchedulesBtn" class="sidebar-button">Lab Schedules</button>
        <button id="leaderboardBtn" class="sidebar-button">Leaderboard</button>
        <button id="logoutBtn" class="sidebar-button">Logout</button>
    </div>

    <main>
        <div id="dynamicContent">
            <!-- Home Content -->
            <div id="homeContent">
                <!-- Top Stats -->
                <div class="stats-container">
                    <div class="stat-box">
                        <h3>Student Registered: <span id="totalUsers"></span></h3>
                    </div>
                    <div class="stat-box">
                        <h3>Current Sit-in: <span id="currentSitIn"></span></h3>
                    </div>
                    <div class="stat-box">
                        <h3>Total Sit-in: <span id="totalSitIn"></span></h3>
                    </div>
                </div>

                <!-- Language Chart -->
                <div class="chart-container">
                    <canvas id="languageChart"></canvas>
                </div>

                <!-- Announcement Section -->
                <div class="announcement-container">
                    <!-- Left Side - Announcement Form -->
                    <div class="announcement-form">
                        <h3>Post Announcement</h3>
                        <form id="announcementForm">
                            <textarea name="announcement" rows="4" required placeholder="Type your announcement here..."></textarea>
                            <button type="submit" class="action-button">Post Announcement</button>
                        </form>
                    </div>
                    <!-- Right Side - Posted Announcements -->
                    <div class="announcement-list">
                        <h3>Posted Announcements</h3>
                        <div id="announcementList" class="announcement-scroll"></div>
                    </div>
                </div>
            </div>

            <!-- Search Modal -->
            <div id="searchModal" class="modal-container">
                <div class="search-modal">
                    <span class="close" id="closeSearchModal">&times;</span>
                    <h2 class="modal-title">Search Student</h2>
                    <div class="search-form">
                        <input type="text" id="searchIdNo" placeholder="Enter ID Number or Name">
                    </div>
                    <div class="search-results" id="searchResults">
                        <!-- Search results will be dynamically added here -->
                    </div>
                </div>
            </div>

            <!-- Student Content -->
            <div id="studentContent" style="display: none;">
                <div class="student-header">
                    <h2>Student List</h2>
                    <div class="filter-controls" style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <select id="courseFilter" class="filter-dropdown" onchange="filterStudents()">
                            <option value="">All Courses</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSECE">BSECE</option>
                            <option value="BSME">BSME</option>
                            <option value="BSCE">BSCE</option>
                            <option value="BSBA">BSBA</option>
                            <option value="BSHRM">BSHRM</option>
                            <option value="BSN">BSN</option>
                            <option value="BSA">BSA</option>
                            <option value="BSPSY">BSPSY</option>
                            <option value="BSBIO">BSBIO</option>
                            <option value="BSMATH">BSMATH</option>
                        </select>
                        <select id="yearFilter" class="filter-dropdown" onchange="filterStudents()">
                            <option value="">All Year Levels</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="student-search-container">
                        <input type="text" id="studentSearch" placeholder="Search by ID Number or Name">
                        <!-- Removing the search button since we'll make it dynamic -->
                    </div>
                    <button id="addStudentBtn">Add Student</button>
                    <button onclick="resetSessions()">Reset Sessions</button>
                </div>
                <div class="student-list">
                    <table>
                        <thead>
                            <tr>
                                <th onclick="sortTable('id_number', 'number')" style="cursor: pointer;">ID Number ↕</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th onclick="sortTable('year_level', 'number')" style="cursor: pointer;">Year Level ↕</th>
                                <th onclick="sortTable('sessions', 'number')" style="cursor: pointer;">Sessions ↕</th>
                                <th onclick="sortTable('points', 'number')" style="cursor: pointer;">Points ↕</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody"></tbody>
                    </table>
                </div>
                <div class="export-buttons">
                    
                </div>
            </div>

            <!-- Current Sit-in Content -->
            <div id="sitinContent" style="display: none;">
                <h2>CURRENT SIT IN</h2>
                <div class="sitin-header">
                    <div class="entries-display">
                        Displaying
                        <select id="entriesPerPage" class="entries-select" onchange="loadSitInData()">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                        </select>
                        entries
                    </div>
                    <div class="sitin-search">
                        <input type="text" id="sitinSearch" placeholder="Search...">
                        <button onclick="loadSitInData()">Search</button>
                    </div>
                </div>
                <div class="sitin-list">
                    <table id="sitinTable">
                        <thead>
                            <tr>
                                <th>Sit-in ID</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Purpose</th>
                                <th>Sit-in Lab</th>
                                <th>Session</th>
                                <th>Status</th>
                                
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="sitinTableBody">
                            <?php foreach ($currentSitInStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['sitin_id']; ?></td>
                                <td><?php echo $student['id_number']; ?></td>
                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                <td><?php echo $student['purpose']; ?></td>
                                <td><?php echo $student['lab']; ?></td>
                                <td><?php echo $student['sessions']; ?></td>
                                <td><?php echo $student['status']; ?></td>
                                <td>
                                    <?php if (empty($student['feedback_text'])): ?>
                                        <button onclick="openFeedbackModal(<?php echo $student['sitin_id']; ?>)">Give Feedback</button>
                                    <?php else: ?>
                                        <button onclick="viewFeedback(<?php echo $student['sitin_id']; ?>)">View Feedback</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="logoutSitIn('<?php echo $student['id_number']; ?>')">Logout</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sitin-pagination" style="text-align: center;">
                    <button onclick="goToFirstPage()"><<</button>
                    <button onclick="goToPreviousPage()"><</button>
                    <span id="currentPage">1</span>
                    <button onclick="goToNextPage()">></button>
                    <button onclick="goToLastPage()">>></button>
                </div>
            </div>

            <!-- View Sit-in History Modal -->
            <div id="viewSitInModal" class="modal-container">
                <div class="history-modal">
                    <span class="close" onclick="closeModal('viewSitInModal')">&times;</span>
                    <h2 class="modal-title">Sit-in History</h2>
                    <div class="export-options">
                        <button onclick="exportToPDF('sitinHistory')">Export to PDF</button>
                        <button onclick="exportToExcel('sitinHistory')">Export to Excel</button>
                        <button onclick="exportToCSV('sitinHistory')">Export to CSV</button>
                    </div>
                    <div class="history-content">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>ID Number</th>
                                    <th>Student Name</th>
                                    <th>Purpose</th>
                                    <th>Lab</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="sitinHistoryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sit-in Reports Modal -->
            <div id="sitInReportModal" class="modal-container">
                <div class="history-modal">
                    <span class="close" onclick="closeModal('sitInReportModal')">&times;</span>
                    <h2 class="modal-title">Sit-in Reports</h2>
                    <div class="export-options">
                        <button onclick="exportToPDF('sitInReport')">Export to PDF</button>
                        <button onclick="exportToExcel('sitInReport')">Export to Excel</button>
                        <button onclick="exportToCSV('sitInReport')">Export to CSV</button>
                        <button onclick="generateReport()">Generate Report</button>
                    </div>
                    <div class="report-filters">
                        <input type="date" id="startDate" placeholder="Start Date">
                        <input type="date" id="endDate" placeholder="End Date">
                        <select id="reportType">
                            <option value="daily">Daily Report</option>
                            <option value="weekly">Weekly Report</option>
                            <option value="monthly">Monthly Report</option>
                        </select>
                    </div>
                    <div class="report-content">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Sit-ins</th>
                                    <th>Active Users</th>
                                    <th>Most Used Lab</th>
                                    <th>Most Used Purpose</th>
                                </tr>
                            </thead>
                            <tbody id="sitInReportBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Feedback Reports Content -->
            <div id="feedbackReservationContent" style="display: none;">
                <h2>Feedback Reports</h2>
                <div class="card">
                    <div class="card-header">
                        <h5>Student Feedback</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="feedbackTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Feedback</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query to get student feedback
                                    $feedback_sql = "SELECT f.id, f.id_number, f.feedback_text, f.feedback_date, 
                                                    i.first_name, i.last_name 
                                                    FROM feedback f 
                                                    JOIN info i ON f.id_number = i.id_number 
                                                    ORDER BY f.feedback_date DESC";
                                    $feedback_result = mysqli_query($conn, $feedback_sql);
                                    
                                    if ($feedback_result && mysqli_num_rows($feedback_result) > 0) {
                                        while ($feedback_row = mysqli_fetch_assoc($feedback_result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($feedback_row['id_number']) . "</td>";
                                            echo "<td>" . htmlspecialchars($feedback_row['first_name'] . " " . $feedback_row['last_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($feedback_row['feedback_text']) . "</td>";
                                            echo "<td>" . htmlspecialchars($feedback_row['feedback_date']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>No feedback found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Resources Content -->
            <div id="labResourcesContent" style="display: none;">
                <h2>Lab Resources/Materials</h2>
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Resources</h5>
                    </div>
                    <div class="card-body">
                        <form id="resourceUploadForm" action="upload_resource.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="resourceTitle" class="form-label">Resource Title</label>
                                <input type="text" class="form-control" id="resourceTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="resourceDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="resourceDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="resourceType" class="form-label">Resource Type</label>
                                <select class="form-select" id="resourceType" name="type">
                                    <option value="pdf">PDF</option>
                                    <option value="link">Link</option>
                                    <option value="document">Document</option>
                                </select>
                            </div>
                            <div class="mb-3" id="fileUploadDiv">
                                <label for="resourceFile" class="form-label">File</label>
                                <input type="file" class="form-control" id="resourceFile" name="file">
                            </div>
                            <div class="mb-3" id="linkInputDiv" style="display: none;">
                                <label for="resourceLink" class="form-label">Link URL</label>
                                <input type="url" class="form-control" id="resourceLink" name="link">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Resource</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Available Resources</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="resourcesTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query to get resources
                                    $resources_sql = "SELECT * FROM lab_resources ORDER BY date_added DESC";
                                    $resources_result = mysqli_query($conn, $resources_sql);
                                    
                                    if ($resources_result && mysqli_num_rows($resources_result) > 0) {
                                        while ($resource_row = mysqli_fetch_assoc($resources_result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($resource_row['title']) . "</td>";
                                            echo "<td>" . htmlspecialchars($resource_row['description']) . "</td>";
                                            echo "<td>" . htmlspecialchars($resource_row['type']) . "</td>";
                                            echo "<td>" . htmlspecialchars($resource_row['date_added']) . "</td>";
                                            echo "<td>";
                                            if ($resource_row['type'] == 'link') {
                                                echo "<a href='" . htmlspecialchars($resource_row['file_path']) . "' target='_blank' class='btn btn-sm btn-info'>View</a> ";
                                            } else {
                                                echo "<a href='download_resource.php?id=" . $resource_row['id'] . "' class='btn btn-sm btn-info'>Download</a> ";
                                            }
                                            echo "<button class='btn btn-sm btn-danger' onclick='deleteResource(" . $resource_row['id'] . ")'>Delete</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No resources found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Schedules Content -->
            <div id="labSchedulesContent" style="display: none;">
                <h2>Lab Schedules</h2>
                <div class="card">
                    <div class="card-header">
                        <h5>Lab Room Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="labSchedulesTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Lab Room</th>
                                        <th>Current Occupancy</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Lab rooms
                                    $lab_rooms = ['524', '526', '528', '530', '542', '544', '517'];
                                    
                                    foreach ($lab_rooms as $room) {
                                        // Get current occupancy
                                        $occupancy_sql = "SELECT COUNT(*) as count FROM sitin WHERE lab = '$room' AND status = 'active'";
                                        $occupancy_result = mysqli_query($conn, $occupancy_sql);
                                        $occupancy_row = mysqli_fetch_assoc($occupancy_result);
                                        $current_occupancy = $occupancy_row['count'];
                                        
                                        // Determine status
                                        $status = ($current_occupancy >= 50) ? 'Full' : 'Available';
                                        $status_class = ($status == 'Full') ? 'text-danger' : 'text-success';
                                        
                                        echo "<tr>";
                                        echo "<td>Lab " . htmlspecialchars($room) . "</td>";
                                        echo "<td>" . $current_occupancy . "</td>";
                                        echo "<td>50</td>";
                                        echo "<td class='" . $status_class . "'>" . $status . "</td>";
                                        echo "<td><button class='btn btn-sm btn-primary' onclick='openScheduleModal(\"" . $room . "\")'>Manage Schedule</button></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Schedule Modal -->
            <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="scheduleModalLabel">Manage Lab Schedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="scheduleContent">
                                <!-- Schedule content will be loaded here -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Content -->
            <div id="leaderboardContent" style="display: none;">
                <h2>Leaderboard</h2>
                <div class="card">
                    <div class="card-header">
                        <h5>Top Performing Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="leaderboardTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Lab Usage Points</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query to get student points
                                    $points_sql = "SELECT i.id_number, i.first_name, i.last_name, 
                                                  COUNT(s.id) as sitin_count, 
                                                  COALESCE(p.points, 0) as admin_points
                                                  FROM info i
                                                  LEFT JOIN sitin_report s ON i.id_number = s.id_number
                                                  LEFT JOIN student_points p ON i.id_number = p.id_number
                                                  GROUP BY i.id_number
                                                  ORDER BY (COUNT(s.id) * 3 + COALESCE(p.points, 0)) DESC
                                                  LIMIT 10";
                                    $points_result = mysqli_query($conn, $points_sql);
                                    
                                    if ($points_result && mysqli_num_rows($points_result) > 0) {
                                        $rank = 1;
                                        while ($point_row = mysqli_fetch_assoc($points_result)) {
                                            $lab_points = $point_row['sitin_count'] * 3;
                                            
                                            echo "<tr>";
                                            echo "<td>" . $rank . "</td>";
                                            echo "<td>" . htmlspecialchars($point_row['id_number']) . "</td>";
                                            echo "<td>" . htmlspecialchars($point_row['first_name'] . " " . $point_row['last_name']) . "</td>";
                                            echo "<td>" . $lab_points . "</td>";
                                            echo "<td>" . $point_row['admin_points'] . "</td>";
                                            echo "</tr>";
                                            
                                            $rank++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Content -->
            <div id="reservationContent" style="display: none;">
                <h2>Reservation</h2>
                <!-- Add your reservation content here -->
            </div>

            <!-- Sit-in Data Content -->
            <div id="sitInDataContent" style="display: none;">
                <div class="content-header">
                    <h2>Sit-in Data</h2>
                    <div class="export-buttons">
                        <button onclick="showExportModal()" class="btn btn-primary">Export</button>
                    </div>
                </div>

                <!-- Export Filter Modal -->
                <div id="exportFilterModal" class="modal-container">
                    <div class="modal">
                        <span class="close" onclick="closeModal('exportFilterModal')">&times;</span>
                        <h2 class="modal-title">Export Data</h2>
                        <div class="form-group">
                            <label for="exportType">Export Type:</label>
                            <select id="exportType" class="form-control">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                                <option value="print">Print</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exportLabFilter">Lab:</label>
                            <select id="exportLabFilter" class="form-control">
                                <option value="">All Labs</option>
                                <option value="524">524</option>
                                <option value="526">526</option>
                                <option value="528">528</option>
                                <option value="530">530</option>
                                <option value="542">542</option>
                                <option value="544">544</option>
                                <option value="517">517</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exportPurposeFilter">Purpose:</label>
                            <select id="exportPurposeFilter" class="form-control">
                                <option value="">All Purposes</option>
                                <option value="C Programming">C Programming</option>
                                <option value="Java Programming">Java Programming</option>
                                <option value="Python">Python</option>
                                <option value="C#">C#</option>
                                <option value="Database">Database</option>
                                <option value="Digital Logic & Design">Digital Logic & Design</option>
                                <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                                <option value="System Integration and Architecture">System Integration and Architecture</option>
                                <option value="Computer Application">Computer Application</option>
                                <option value="Project Management">Project Management</option>
                                <option value="IT Trends">IT Trends</option>
                                <option value="Technopreneurship">Technopreneurship</option>
                                <option value="Capstone">Capstone</option>
                            </select>
                        </div>
                        <div class="button-group">
                            <button onclick="applyExportFilters()" class="modal-button primary">Export</button>
                            <button onclick="closeModal('exportFilterModal')" class="modal-button secondary">Cancel</button>
                        </div>
                    </div>
                </div>
                


                <div class="charts-container">
                    <div class="chart-box">
                        <h3>Purpose Distribution</h3>
                        <canvas id="purposePieChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h3>Lab Usage Distribution</h3>
                        <canvas id="labPieChart"></canvas>
                    </div>
                </div>
                
                <div class="search-control" style="margin: 20px 0;">
                    <input type="text" id="searchInput" placeholder="Search by ID, Name, Purpose, or Lab..." style="width: 100%; padding: 10px;">
                </div>
                
                <div class="data-controls">
                    <div class="entries-display">
                        Displaying
                        <select id="entriesPerPage" class="entries-select" onchange="loadSitInReportData()">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        entries
                    </div>
                </div>

                <div class="data-table-container">
                    <table id="sitInDataTable" class="data-table">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0, 'number')">ID Number ↕</th>
                                <th onclick="sortTable(1, 'text')">Purpose ↕</th>
                                <th onclick="sortTable(2, 'text')">Lab ↕</th>
                                <th onclick="sortTable(3, 'date')">Login Time ↕</th>
                                <th onclick="sortTable(4, 'date')">Logout Time ↕</th>
                                <th onclick="sortTable(5, 'number')">Duration ↕</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody id="sitInDataBody"></tbody>
                    </table>
                </div>

                <div class="pagination" style="text-align: center; margin-top: 20px;">
                    <button onclick="goToFirstPage()" id="firstPageBtn"><<</button>
                    <button onclick="goToPreviousPage()" id="prevPageBtn"><</button>
                    <span id="currentPage">1</span>
                    <button onclick="goToNextPage()" id="nextPageBtn">></button>
                    <button onclick="goToLastPage()" id="lastPageBtn">>></button>
                </div>

                <div id="feedbackModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
                    <div style="background-color: white; padding: 20px; border-radius: 5px; width: 80%; max-width: 600px;">
                        <span class="close" onclick="closeFeedbackModal()">&times;</span>
                        <h3>Feedback Details</h3>
                        <div id="modalFeedbackText"></div>
                        <button onclick="closeFeedbackModal()" class="btn btn-secondary mt-3">Close</button>
                    </div>
                </div>

                <script>
                    function showFeedbackModal(idNumber) {
                        const modal = document.getElementById('feedbackModal');
                        const modalContent = document.getElementById('modalFeedbackText');
                        
                        // Show modal with loading state
                        modal.style.display = 'flex';
                        modalContent.innerHTML = '<div class="loading-feedback">Loading feedback data...</div>';
                        
                        // Fetch feedback data
                        fetch(`get_feedback_data.php?id=${idNumber}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    const feedback = data[0]; // Get the most recent feedback
                                    feedbackContent.innerHTML = `
                                        <div class="feedback-details">
                                            <p><strong>Student ID:</strong> ${feedback.id_number}</p>
                                            <p><strong>Student Name:</strong> ${feedback.student_name}</p>
                                            <p><strong>Lab:</strong> ${feedback.lab || 'N/A'}</p>
                                            <p><strong>Date:</strong> ${new Date(feedback.date).toLocaleString()}</p>
                                            <p><strong>Feedback:</strong></p>
                                            <div class="feedback-text">${feedback.feedback_text}</div>
                                            ${feedback.rating ? `
                                                <p class="mt-3">
                                                    <strong>Rating:</strong> 
                                                    <span class="rating-stars">${'★'.repeat(parseInt(feedback.rating))}${'☆'.repeat(5-parseInt(feedback.rating))}</span>
                                                </p>` : ''
                                            }
                                        </div>
                                    `;
                                } else {
                                    feedbackContent.innerHTML = `
                                        <div class="no-feedback">
                                            <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                            <p>No feedback available for this student.</p>
                                        </div>
                                    `;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                feedbackContent.innerHTML = `
                                    <div class="no-feedback text-danger">
                                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                        <p>Error loading feedback. Please try again.</p>
                                    </div>
                                `;
                            });
                    }

                    function closeFeedbackModal() {
                        const modal = document.getElementById('feedbackModal');
                        modal.style.display = 'none';
                    }

window.addEventListener('click', function(event) {
    const modalContainer = document.getElementById('studentInfoModal');
    if (event.target === modalContainer) {
        modalContainer.style.display = 'none';
    }
});
                </script>

                <style>
                    .feedback-content {
                        margin: 15px 0;
                    }
                    .feedback-content p {
                        margin: 8px 0;
                    }
                    #feedbackModal .close {
                        position: absolute;
                        right: 15px;
                        top: 10px;
                        font-size: 24px;
                        cursor: pointer;
                    }
                    #modalFeedbackText {
                        margin-top: 20px;
                    }
                </style>

                <script>
                    function loadSitInReportData() {
                        const tbody = document.getElementById('sitInDataBody');
                        // Example data row creation
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>\${record.id_number}</td>
                            <td>\${record.purpose}</td>
                            <td>\${record.lab}</td>
                            <td>\${record.login_time}</td>
                            <td>\${record.logout_time}</td>
                            <td>\${calculateDuration(record.login_time, record.logout_time)}</td>
                            <td>
                                <button class="feedback-button" onclick="showFeedbackModal(this)">View Feedback</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    }
                </script>
            </div>
        </div>

        <!-- Feedback Modal -->
        <div id="feedbackModal" class="modal-container">
            <div class="feedback-modal">
                <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
                <h2>Student Feedback</h2>
                <div id="feedbackContent"></div>
            </div>
        </div>

        <script>
            function showFeedback(studentId) {
                const modal = document.getElementById('feedbackModal');
                const content = document.getElementById('feedbackContent');
                
                modal.style.display = 'flex';
                content.innerHTML = '<p class="loading">Loading feedback</p>';
                
                // Fetch feedback from sitin_reports
                fetch(`get_sitin_feedback.php?id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            let feedbackHtml = '';
                            data.forEach(feedback => {
                                feedbackHtml += `
                                    <div class="feedback-details">
                                        <p><strong>Purpose:</strong> ${feedback.purpose}</p>
                                        <p><strong>Lab:</strong> ${feedback.lab}</p>
                                        <p><strong>Login Time:</strong> ${formatDateTime(feedback.login_time)}</p>
                                        <p><strong>Logout Time:</strong> ${formatDateTime(feedback.logout_time)}</p>
                                        <p><strong>Duration:</strong> ${feedback.duration || 'N/A'}</p>
                                        <p><strong>Feedback:</strong> ${feedback.feedback || 'No feedback provided'}</p>
                                        <p><strong>Date:</strong> ${formatDateTime(feedback.feedback_date)}</p>
                                    </div>
                                `;
                            });
                            content.innerHTML = feedbackHtml;
                        } else {
                            content.innerHTML = '<p class="no-feedback">No feedback available for this student</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        content.innerHTML = '<p class="no-feedback">Error loading feedback. Please try again.</p>';
                    });
            }

            function formatDateTime(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            }

            // Update the feedback button in the table
            function createFeedbackButton(studentId) {
                return `<button onclick="showFeedback('${studentId}')" class="feedback-btn">View Feedback</button>`;
            }
        </script>
    </main>

    <div id="studentInfoModal" class="modal-container active" style="display: flex; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal" style="background: var(--background); border-radius: 10px; padding: 25px; max-width: 500px; margin: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <h2 class="modal-title" style="color: var(--light); margin-bottom: 20px; font-size: 24px; text-align: center;">Sit-in Form</h2>
            <div class="form-group" style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border-color);">
                <p style="margin: 8px 0;"><b style="color: var(--light);">ID Number:</b> <span id="studentIdNo" style="color: var(--light);">5000</span></p>
                <p style="margin: 8px 0;"><b style="color: var(--light);">Student Name:</b> <span id="studentName" style="color: var(--light);">Juan Dela Cruz</span></p>
                <p style="margin: 8px 0;"><b style="color: var(--light);">Remaining Sessions:</b> <span id="remainingSessions" style="color: var(--light);">undefined</span></p>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="purpose" style="display: block; margin-bottom: 8px; color: var(--light); font-weight: bold;">Purpose:</label>
                <select id="purpose" class="compact-select" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; background: #1a1a2e; color: var(--light); font-size: 14px;">
                    <option value="" style="background: #1a1a2e; color: var(--light);">Select Purpose</option>
                    <option value="C Programming">C Programming</option>
                    <option value="Java Programming">Java Programming</option>
                    <option value="Python">Python</option>
                    <option value="C#">C#</option>
                    <option value="Database">Database</option>
                    <option value="Digital Logic &amp; Design">Digital Logic &amp; Design</option>
                    <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                    <option value="System Integration and Architecture">System Integration and Architecture</option>
                    <option value="Computer Application">Computer Application</option>
                    <option value="Project Management">Project Management</option>
                    <option value="IT Trend">IT Trend</option>
                    <option value="Technopreneurship">Technopreneurship</option>
                    <option value="Capstone">Capstone</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label for="lab" style="display: block; margin-bottom: 8px; color: var(--light); font-weight: bold;">Lab:</label>
                <select id="lab" class="compact-select" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; background: #1a1a2e; color: var(--light); font-size: 14px;">
                    <option value="" style="background: #1a1a2e; color: var(--light);">Select Lab Room</option>
                    <option value="524">Lab 524</option>
                    <option value="526">Lab 526</option>
                    <option value="528">Lab 528</option>
                    <option value="530">Lab 530</option>
                    <option value="542">Lab 542</option>
                    <option value="544">Lab 544</option>
                    <option value="517">Lab 517</option>
                </select>
            </div>
            <div class="button-group" style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="modal-button primary" onclick="addSitIn()" style="padding: 10px 20px; background: transparent; color: var(--light); border: 1px solid var(--border-color); border-radius: 5px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">Sit-in</button>
                <button class="modal-button secondary" onclick="closeModal('studentInfoModal')" style="padding: 10px 20px; background: transparent; color: var(--light); border: 1px solid var(--border-color); border-radius: 5px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">Close</button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-container">
        <div class="modal-content">
            
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <div class="button-group">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <button type="submit" name="logout" class="btn-primary">Logout</button>
                </form>
                <button type="button" class="btn-secondary" onclick="closeModal('logoutModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal-container">
        <div class="modal">
            <span class="close" id="closeAddStudentModal">&times;</span>
            <h2>Add Student</h2>
            <form id="addStudentForm">
                <div class="form-group">
                    <label for="idno">ID Number</label>
                    <input type="text" id="idno" name="idno" required>
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>

                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>

                <div class="form-group">
                    <label for="midname">Middle Name</label>
                    <input type="text" id="midname" name="midname" required>
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course" required>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCS">BSCS</option>
                        <option value="BSECE">BSECE</option>
                        <option value="BSME">BSME</option>
                        <option value="BSCE">BSCE</option>
                        <option value="BSBA">BSBA</option>
                        <option value="BSHRM">BSHRM</option>
                        <option value="BSN">BSN</option>
                        <option value="BSA">BSA</option>
                        <option value="BSPSY">BSPSY</option>
                        <option value="BSBIO">BSBIO</option>
                        <option value="BSMATH">BSMATH</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="yearlvl">Year Level</label>
                    <select id="yearlvl" name="yearlvl" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <input type="submit" value="Add Student" class="action-button">
            </form>
            <div id="form-message"></div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal-container">
        <div class="modal">
            <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
            <h2 class="modal-title">Feedback Details</h2>
            <div id="feedbackContent" class="feedback-content">
                <!-- Feedback content will be loaded here -->
            </div>
            <div class="button-group">
                <button class="modal-button secondary" onclick="closeModal('feedbackModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Feedback Modal -->
    <div id="viewFeedbackModal" class="modal-container">
        <div class="modal">
            <span class="close" onclick="closeModal('viewFeedbackModal')">&times;</span>
            <h2 class="modal-title">Feedback</h2>
            <div id="feedbackContent" class="feedback-content">
                <!-- Feedback content will be loaded here -->
            </div>
            <div class="button-group">
                <button class="modal-button secondary" onclick="closeModal('viewFeedbackModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables for all data management
        let currentSitInData = [];
        let currentSitInPage = 1;
        let currentSitInEntriesPerPage = 10;
        let sitInReportData = [];
        let currentPage = 1;
        let entriesPerPage = 10;

        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all modals are hidden by default
            document.querySelectorAll('.modal-container').forEach(modal => {
                modal.style.display = 'none';
            });

            // Sidebar button click handlers
            document.querySelectorAll('.sidebar button').forEach(button => {
                button.addEventListener('click', function() {
                    const btnId = this.id;
                    
                    // Handle special cases first
                    if (btnId === 'searchBtn') {
                        openModal('searchModal');
                        return;
                    }
                    if (btnId === 'logoutBtn') {
                        openModal('logoutModal');
                        return;
                    }
                    if (btnId === 'viewSitInBtn') {
                        openModal('viewSitInModal');
                        return;
                    }
                    if (btnId === 'sitInReportBtn') {
                        openModal('sitInReportModal');
                        return;
                    }

                    // For other buttons, load their content
                    const contentId = btnId.replace('Btn', 'Content');
                    loadContent(contentId);

                    // Initialize specific content if needed
                    if (btnId === 'sitinBtn') {
                        initSitInContent();
                    }
                });
            });

            // Search modal handlers
            const searchBtn = document.getElementById('searchBtn');
            if (searchBtn) {
                searchBtn.addEventListener('click', () => {
                    openModal('searchModal');
                });
            }

            const closeSearchModal = document.getElementById('closeSearchModal');
            if (closeSearchModal) {
                closeSearchModal.addEventListener('click', () => {
                    closeModal('searchModal');
                });
            }

            const searchInput = document.getElementById('searchIdNo');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(searchStudent, 300); // Add a 300ms delay to prevent too many requests
                });
            }

            // Add Student modal handlers
            const addStudentBtn = document.getElementById('addStudentBtn');
            if (addStudentBtn) {
                addStudentBtn.addEventListener('click', () => {
                    openModal('addStudentModal');
                });
            }

            const closeAddStudentModal = document.getElementById('closeAddStudentModal');
            if (closeAddStudentModal) {
                closeAddStudentModal.addEventListener('click', () => {
                    closeModal('addStudentModal');
                });
            }

            // Add student form submission
            const addStudentForm = document.getElementById('addStudentForm');
            if (addStudentForm) {
                addStudentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);

                    fetch('add_student.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('form-message').innerHTML = '<div class="success">' + data.message + '</div>';
                            this.reset();
                            setTimeout(() => {
                                closeModal('addStudentModal');
                                loadStudentData(); // Reload the student list
                            }, 1500);
                        } else {
                            document.getElementById('form-message').innerHTML = '<div class="error">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('form-message').innerHTML = '<div class="error">An error occurred: ' + error + '</div>';
                    });
                });
            }

            // Add student form validation
            const idnoInput = document.getElementById('idno');
            if (idnoInput) {
                idnoInput.addEventListener('input', function() {
                    // Remove any non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }

            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    // Basic email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.value)) {
                        this.setCustomValidity('Please enter a valid email address');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }

            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    // Remove the password validation
                    this.setCustomValidity('');
                });
            }

            // Update validation styles
            const style = document.createElement('style');
            style.textContent = `
                .success {
                    color: #4CAF50;
                    padding: 10px;
                    margin: 10px 0;
                    border-radius: 4px;
                    background-color: rgba(76, 175, 80, 0.1);
                }
                .error {
                    color: #f44336;
                    padding: 10px;
                    margin: 10px 0;
                    border-radius: 4px;
                    background-color: rgba(244, 67, 54, 0.1);
                }
                /* Remove validation colors for password */
                input[type="password"] {
                    border-color: var(--border-color) !important;
                }
                input[type="password"]:focus {
                    border-color: var(--primary) !important;
                    box-shadow: 0 0 10px var(--primary);
                }
                /* Keep validation for other inputs */
                input:not([type="password"]):invalid {
                    border-color: #f44336;
                }
                input:not([type="password"]):valid {
                    border-color: #4CAF50;
                }
            `;
            document.head.appendChild(style);

            // Initial content load
            loadContent('homeContent');

            // Load sit-in data if we're on the sit-in page
            if (document.getElementById('sitinContent').style.display === 'block') {
                loadSitInData();
            }
        });

        function loadContent(contentId) {
            // Hide all content sections first
            document.querySelectorAll('#dynamicContent > div').forEach(div => {
                div.style.display = 'none';
            });

            // Show the requested content section if it exists
            const contentElement = document.getElementById(contentId);
            if (contentElement) {
                contentElement.style.display = 'block';
                
                // Load specific content data if needed
                if (contentId === 'homeContent') {
                    loadHomeData();
                } else if (contentId === 'studentContent') {
                    loadStudentData();
                }
            }
        }

        function loadHomeData() {
            document.getElementById('totalUsers').innerText = '<?php echo count($allStudents); ?>';
            document.getElementById('currentSitIn').innerText = '<?php echo count($currentSitInStudents); ?>';
            document.getElementById('totalSitIn').innerText = '50'; // Replace with actual data

            // Load language chart data
            loadLanguageData();

            // Load announcements
            loadAnnouncements();

            // Add event listener for announcement form
            const announcementForm = document.getElementById('announcementForm');
            if (announcementForm) {
                announcementForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitAnnouncement(this);
                });
            }
        }

        function loadAnnouncements() {
            var announcementList = document.getElementById('announcementList');
            announcementList.innerHTML = ''; // Clear existing content

            fetch('get_announcements.php')
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        data.forEach(announcement => {
                            let announcementItem = document.createElement('div');
                            announcementItem.className = 'announcement-item';
                            
                            // Format the date
                            const date = new Date(announcement.date_posted);
                            const formattedDate = date.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                            
                            announcementItem.innerHTML = `
                                <div class="announcement-text">${announcement.announcement_text}</div>
                                <div class="announcement-date">Posted on ${formattedDate}</div>
                            `;
                            announcementList.insertBefore(announcementItem, announcementList.firstChild);
                        });
                    } else {
                        announcementList.innerHTML = '<p class="announcement-text">No announcements found.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching announcements:', error);
                    announcementList.innerHTML = '<p class="announcement-text">Error loading announcements.</p>';
                });
        }

        function submitAnnouncement(form) {
            const formData = new FormData(form);

            fetch('update_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the form
                    form.reset();
                    
                    // Add the new announcement to the top of the list
                    const announcementList = document.getElementById('announcementList');
                    const announcementItem = document.createElement('div');
                    announcementItem.className = 'announcement-item';
                    
                    // Format the date
                    const date = new Date(data.announcement.date_posted);
                    const formattedDate = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    announcementItem.innerHTML = `
                        <div class="announcement-text">${data.announcement.announcement_text}</div>
                        <div class="announcement-date">Posted on ${formattedDate}</div>
                    `;
                    
                    // Insert at the top of the list
                    announcementList.insertBefore(announcementItem, announcementList.firstChild);
                } else {
                    alert(data.message || 'Error posting announcement');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error posting announcement. Please try again.');
            });
        }

        function loadStudentData() {
            const studentList = document.getElementById('studentTableBody');
            studentList.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';

            fetch('get_students.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        studentList.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${data.error}</td></tr>`;
                        return;
                    }

                    if (data.length === 0) {
                        studentList.innerHTML = '<tr><td colspan="7" class="text-center">No students found</td></tr>';
                        return;
                    }

                    studentList.innerHTML = '';
                    data.forEach(student => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${student.id_number}</td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.course}</td>
                            <td>${student.year_level}</td>
                            <td>${student.sessions}</td>
                            <td>${student.points || 0}</td>
                            <td>
                                <button onclick="addPoints('${student.id_number}', '${student.first_name} ${student.last_name}')" class="btn btn-primary btn-sm">
                                    Add Points
                                </button>
                            </td>
                        `;
                        studentList.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    studentList.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading student data</td></tr>';
                });
        }

        function addStudent() { /* ... */ }
        function resetSessions() {
            if (!confirm('Are you sure you want to reset sessions?\nBSIT/BSCS students will get 30 sessions.\nOther courses will get 15 sessions.')) {
                return;
            }

            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reset_sessions=true'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Reload the student data to show updated sessions
                    loadStudentData();
                } else {
                    alert(data.message || 'Error resetting sessions');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error resetting sessions. Please try again.');
            });
        }

        function loadLanguageData() {
            fetch('get_language_data.php')
                .then(response => response.json())
                .then(data => {
                    const ctx = document.getElementById('languageChart').getContext('2d');
                    
                    // Destroy existing chart if it exists
                    if (window.languageChart instanceof Chart) {
                        window.languageChart.destroy();
                    }
                    
                    window.languageChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: [
                                    'hsla(350, 100%, 70%, 0.7)',  // Red
                                    'hsla(200, 100%, 70%, 0.7)',  // Blue
                                    'hsla(200, 100%, 70%, 0.7)',  // Blue
                                    'hsla(145, 100%, 70%, 0.7)',  // Green
                                    'hsla(280, 100%, 70%, 0.7)',  // Purple
                                ],
                                borderColor: [
                                    'hsla(350, 100%, 70%, 1)',
                                    'hsla(200, 100%, 70%, 1)',
                                    'hsla(145, 100%, 70%, 1)',
                                    'hsla(45, 100%, 70%, 1)',
                                    'hsla(280, 100%, 70%, 1)',
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'hsl(220, 50%, 90%)',
                                        font: {
                                            size: 14
                                        },
                                        padding: 20
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Programming Languages Distribution',
                                    color: 'hsl(220, 50%, 90%)',
                                    font: {
                                        size: 18,
                                        weight: 'normal'
                                    },
                                    padding: 20
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching language data:', error));
        }

        function loadSitInHistory() {
            fetch('get_sitin_history.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('sitinHistoryBody');
                    tbody.innerHTML = '';
                    data.forEach(record => {
                        const row = `
                            <tr>
                                <td>${record.date}</td>
                                <td>${record.id_number}</td>
                                <td>${record.student_name}</td>
                                <td>${record.purpose}</td>
                                <td>${record.lab}</td>
                                <td>${record.duration}</td>
                                <td>${record.status}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error loading sit-in history:', error));
        }

        function loadSitInReport() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const reportType = document.getElementById('reportType').value;

            fetch(`get_sitin_report.php?start=${startDate}&end=${endDate}&type=${reportType}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('sitInReportBody');
                    tbody.innerHTML = '';
                    data.forEach(record => {
                        const row = `
                            <tr>
                                <td>${record.date}</td>
                                <td>${record.total_sitins}</td>
                                <td>${record.active_users}</td>
                                <td>${record.most_used_lab}</td>
                                <td>${record.most_used_purpose}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error loading sit-in report:', error));
        }

        function exportToPDF(type) {
            window.location.href = `export_report.php?type=${type}&format=pdf`;
        }

        function exportToExcel(type) {
            window.location.href = `export_report.php?type=${type}&format=excel`;
        }

        function exportToCSV(type) {
            window.location.href = `export_report.php?type=${type}&format=csv`;
        }

        function generateReport() {
            loadSitInReport();
        }

        function loadSitInData() {
            entriesPerPage = document.getElementById('entriesPerPage').value;
            let searchTerm = document.getElementById('sitinSearch').value.toLowerCase();

            fetch('get_current_sitin_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    if (!Array.isArray(data)) {
                        throw new Error('Invalid data format received');
                    }
                    currentSitInData = data;
                    displayCurrentSitInData(searchTerm);
                })
                .catch(error => {
                    console.error('Error:', error);
                    const sitinTableBody = document.getElementById('sitinTableBody');
                    sitinTableBody.innerHTML = `
                        <tr>
                            <td colspan="8" style="text-align: center; color: red;">
                                ${error.message || 'Error loading sit-in data. Please try again later.'}
                            </td>
                        </tr>
                    `;
                });
        }

        function displayCurrentSitInData(searchTerm = '') {
            const sitinTableBody = document.getElementById('sitinTableBody');
            sitinTableBody.innerHTML = ''; // Clear existing data
            
            const startIndex = (currentPage - 1) * entriesPerPage;
            const endIndex = startIndex + parseInt(entriesPerPage);

            let filteredData = currentSitInData.filter(item =>
                (item.id_number && item.id_number.toLowerCase().includes(searchTerm)) ||
                (item.first_name && item.first_name.toLowerCase().includes(searchTerm)) ||
                (item.last_name && item.last_name.toLowerCase().includes(searchTerm)) ||
                (item.purpose && item.purpose.toLowerCase().includes(searchTerm)) ||
                (item.lab && item.lab.toLowerCase().includes(searchTerm))
            );

            const paginatedData = filteredData.slice(startIndex, endIndex);

            if (paginatedData.length === 0) {
                sitinTableBody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center;">No current sit-in records found</td>
                    </tr>
                `;
            } else {
                paginatedData.forEach(sitin => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${sitin.sitin_id || ''}</td>
                        <td>${sitin.id_number || ''}</td>
                        <td>${(sitin.first_name || '') + ' ' + (sitin.last_name || '')}</td>
                        <td>${sitin.purpose || ''}</td>
                        <td>${sitin.lab || ''}</td>
                        <td>${sitin.sessions || ''}</td>
                        <td>${sitin.status || ''}</td>
                        <td>
                            <button onclick="logoutSitIn('${sitin.id_number}')" 
                                    class="timeout-btn"
                                    ${sitin.status !== 'active' ? 'disabled' : ''}>
                                Timeout
                            </button>
                        </td>
                    `;
                    sitinTableBody.appendChild(row);
                });
            }

            updateCurrentSitInPagination(filteredData.length);
        }

        function updateCurrentSitInPagination(totalItems) {
            const totalPages = Math.ceil(totalItems / entriesPerPage);
            document.getElementById('currentPage').textContent = currentPage;
        }

        function logoutSitIn(idNo) {
            if (!confirm('Are you sure you want to timeout this student?')) {
                return;
            }

            const formData = new FormData();
            formData.append('id_number', idNo);
            formData.append('logout_sitin', true);

            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSitInData(); // Reload the current sit-in data
                    loadStudentData(); // Reload student data to update session counts
                } else {
                    alert(data.message || 'Error timing out student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error timing out student');
            });
        }

        // Initialize when the current sit-in content is shown
        document.getElementById('sitinBtn').addEventListener('click', function() {
            currentPage = 1; // Reset to first page
            loadSitInData();
        });

        // Add search handler for current sit-in
        document.getElementById('sitinSearch').addEventListener('input', function(e) {
            currentPage = 1; // Reset to first page when searching
            loadSitInData();
        });

        function searchStudent() {
            var searchTerm = document.getElementById('searchIdNo').value;
            var searchResults = document.getElementById('searchResults');
            
            if (searchTerm.length < 2) {
                searchResults.innerHTML = '<p style="text-align: center; color: #666;">Please enter at least 2 characters</p>';
                return;
            }

            searchResults.innerHTML = '<p style="text-align: center;">Searching...</p>';

            fetch('search_students.php?term=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(students => {
                    searchResults.innerHTML = '';
                    
                    if (students.length > 0) {
                        students.forEach(student => {
                            let resultItem = document.createElement('div');
                            resultItem.className = 'search-result-item';
                            resultItem.innerHTML = `
                                <div onclick="selectStudent('${student.id_number}', '${student.first_name} ${student.last_name}', '${student.sessions}')">
                                    <strong>${student.first_name} ${student.last_name}</strong><br>
                                    ID: ${student.id_number}<br>
                                    Course: ${student.course}
                                </div>
                            `;
                            searchResults.appendChild(resultItem);
                        });
                    } else {
                        searchResults.innerHTML = '<p style="text-align: center; color: #666;">No students found</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchResults.innerHTML = '<p style="text-align: center; color: #ff4444;">Error searching students</p>';
                });
        }

        // Add event listener for selecting a student in the search modal
        function selectStudent(idNo, studentName, remainingSessions) {
            // Close the search modal
            closeModal('searchModal');

            // Populate the Sit-in Form modal with the selected student's data
            document.getElementById('studentIdNo').textContent = idNo;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('remainingSessions').textContent = remainingSessions || '0'; // Default to '0' if undefined

            // Open the Sit-in Form modal
            openModal('studentInfoModal');
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300); // Match the transition duration
            }
        }

        // Close modal if clicked outside
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal-container').forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Prevent modal close when clicking inside modal-content
        document.querySelectorAll('.modal-content').forEach(content => {
            content.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });

        function addSitIn() {
            const idNumber = document.getElementById('studentIdNo').innerText;
            const purpose = document.getElementById('purpose').value;
            const lab = document.getElementById('lab').value;
            const remainingSessions = parseInt(document.getElementById('remainingSessions').innerText);

            if (remainingSessions <= 0) {
                alert('No remaining sessions available.');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('id_number', idNumber);
            formData.append('purpose', purpose);
            formData.append('lab', lab);
            formData.append('add_sitin', true);

            // Send request to server
            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    closeModal('studentInfoModal');
                    
                    // Reload sit-in data if we're on the sit-in page
                    if (document.getElementById('sitinContent').style.display === 'block') {
                        loadSitInData();
                    }
                    
                    // Show success message
                    alert(data.message);
                } else {
                    // Show error message
                    alert(data.message || 'Error adding student to sit-in.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding student to sit-in. Please try again.');
            });
        }

        function searchStudentList() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const studentRows = document.querySelectorAll('#studentTableBody tr');
            
            studentRows.forEach(row => {
                const idCell = row.cells[0].textContent.toLowerCase();
                const nameCell = row.cells[1].textContent.toLowerCase();
                
                if (idCell.includes(searchTerm) || nameCell.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add event listener for Enter key in search input
        document.getElementById('studentSearch').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchStudentList();
            }
        });

        function loadSitInReportData() {
            fetch('get_sitin_report_data.php')
                .then(response => response.json())
                .then(data => {
                    sitInReportData = data;
                    displaySitInData();
                    updateCharts(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function displaySitInData() {
            const tbody = document.getElementById('sitInDataBody');
            tbody.innerHTML = '';
            
            const start = (currentPage - 1) * entriesPerPage;
            const end = start + entriesPerPage;
            const paginatedData = sitInReportData.slice(start, end);

            paginatedData.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.id_number}</td>
                    <td>${record.purpose}</td>
                    <td>${record.lab}</td>
                    <td>${record.login_time}</td>
                    <td>${record.logout_time}</td>
                    <td>${calculateDuration(record.login_time, record.logout_time)}</td>
                    <td>
                        <button class="feedback-button" onclick="showFeedbackModal('${record.id_number}')">View Feedback</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            updatePagination();
        }

        function calculateDuration(login, logout) {
            const start = new Date(login);
            const end = new Date(logout);
            const diff = Math.abs(end - start);
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            return `${hours}h ${remainingMinutes}m`;
        }

        function updateCharts(data) {
            // Purpose Chart
            const purposes = {};
            data.forEach(record => {
                purposes[record.purpose] = (purposes[record.purpose] || 0) + 1;
            });

            const purposeCtx = document.getElementById('purposePieChart').getContext('2d');
            new Chart(purposeCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(purposes),
                    datasets: [{
                        data: Object.values(purposes),
                        backgroundColor: [
                            'hsla(350, 100%, 70%, 0.7)',
                            'hsla(200, 100%, 70%, 0.7)',
                            'hsla(145, 100%, 70%, 0.7)',
                            'hsla(45, 100%, 70%, 0.7)',
                            'hsla(280, 100%, 70%, 0.7)',
                        ],
                        borderColor: [
                            'hsla(350, 100%, 70%, 1)',
                            'hsla(200, 100%, 70%, 1)',
                            'hsla(145, 100%, 70%, 1)',
                            'hsla(45, 100%, 70%, 1)',
                            'hsla(280, 100%, 70%, 1)',
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'hsl(220, 50%, 90%)',
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });

            // Lab Chart
            const labs = {};
            data.forEach(record => {
                labs[record.lab] = (labs[record.lab] || 0) + 1;
            });

            const labCtx = document.getElementById('labPieChart').getContext('2d');
            new Chart(labCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(labs),
                    datasets: [{
                        data: Object.values(labs),
                        backgroundColor: [
                            'hsla(180, 100%, 70%, 0.7)',
                            'hsla(120, 100%, 70%, 0.7)',
                            'hsla(60, 100%, 70%, 0.7)',
                            'hsla(0, 100%, 70%, 0.7)',
                            'hsla(240, 100%, 70%, 0.7)',
                            'hsla(300, 100%, 70%, 0.7)',
                        ],
                        borderColor: [
                            'hsla(180, 100%, 70%, 1)',
                            'hsla(120, 100%, 70%, 1)',
                            'hsla(60, 100%, 70%, 1)',
                            'hsla(0, 100%, 70%, 1)',
                            'hsla(240, 100%, 70%, 1)',
                            'hsla(300, 100%, 70%, 1)',
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'hsl(220, 50%, 90%)',
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        }

        function updatePagination() {
            const totalPages = Math.ceil(sitInReportData.length / entriesPerPage);
            document.getElementById('currentPage').textContent = currentPage;
        }

        function applyDateFilter() {
            const searchDate = document.getElementById('searchDate').value;
            if (searchDate) {
                const filteredData = sitInReportData.filter(record => 
                    record.login_time.includes(searchDate)
                );
                displayFilteredData(filteredData);
            }
        }

        function resetFilters() {
            document.getElementById('searchDate').value = '';
            document.getElementById('searchInput').value = '';
            loadSitInReportData();
        }

        // Add event listener for search input
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredData = sitInReportData.filter(record =>
                record.id_number.toLowerCase().includes(searchTerm) ||
                record.purpose.toLowerCase().includes(searchTerm) ||
                record.lab.toLowerCase().includes(searchTerm)
            );
            displayFilteredData(filteredData);
        });

        function displayFilteredData(filteredData) {
            sitInReportData = filteredData;
            currentPage = 1;
            displaySitInData();
            updateCharts(filteredData);
        }

        // Initialize when the sit-in data content is shown
        document.getElementById('sitInDataBtn').addEventListener('click', function() {
            loadSitInReportData();
        });

        // Export functionality
        function showExportModal() {
            openModal('exportFilterModal');
        }

        function applyExportFilters() {
            const exportType = document.getElementById('exportType').value;
            const labFilter = document.getElementById('exportLabFilter').value;
            const purposeFilter = document.getElementById('exportPurposeFilter').value;
            
            // Get the filtered data
            let filteredData = getFilteredData();
            
            // Apply lab filter if selected
            if (labFilter) {
                filteredData = filteredData.filter(record => record.lab === labFilter);
            }
            
            // Apply purpose filter if selected
            if (purposeFilter) {
                filteredData = filteredData.filter(record => record.purpose === purposeFilter);
            }
            
            // Close the modal
            closeModal('exportFilterModal');
            
            // Export the data based on selected type
            switch(exportType) {
                case 'pdf':
                    exportToPDF(filteredData);
                    break;
                case 'excel':
                    exportToExcel(filteredData);
                    break;
                case 'csv':
                    exportToCSV(filteredData);
                    break;
                case 'print':
                    printData(filteredData);
                    break;
            }
        }

        function printData(data) {
            // Apply any active filters
            const filteredData = getFilteredData();
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Generate the HTML content for printing
            let printContent = `
                <html>
                <head>
                    <title>Sit-in Data Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        h1, h2 { text-align: center; }
                        .report-header { margin-bottom: 20px; }
                        .report-footer { margin-top: 20px; text-align: center; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="report-header">
                        <h1>Sit-in Data Report</h1>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // Add data rows
            filteredData.forEach(record => {
                printContent += `
                    <tr>
                        <td>${record.id_number}</td>
                        <td>${record.purpose}</td>
                        <td>${record.lab}</td>
                        <td>${record.login_time}</td>
                        <td>${record.logout_time}</td>
                        <td>${calculateDuration(record.login_time, record.logout_time)}</td>
                    </tr>
                `;
            });
            
            // Close the HTML
            printContent += `
                        </tbody>
                    </table>
                    <div class="report-footer">
                        <p>Total Records: ${filteredData.length}</p>
                    </div>
                </body>
                </html>
            `;
            
            // Write to the new window and print
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            
            // Wait for content to load before printing
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        function getFilteredData() {
            // Get any active filters
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const searchDate = document.getElementById('searchDate').value;
            
            // Start with all data
            let filteredData = [...sitInReportData];
            
            // Apply search filter if active
            if (searchTerm) {
                filteredData = filteredData.filter(record =>
                    record.id_number.toLowerCase().includes(searchTerm) ||
                    record.purpose.toLowerCase().includes(searchTerm) ||
                    record.lab.toLowerCase().includes(searchTerm)
                );
            }
            
            // Apply date filter if active
            if (searchDate) {
                filteredData = filteredData.filter(record => 
                    record.login_time.includes(searchDate)
                );
            }
            
            return filteredData;
        }

        function filterStudents() {
            const courseFilter = document.getElementById('courseFilter').value;
            const yearFilter = document.getElementById('yearFilter').value;
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const studentRows = document.querySelectorAll('#studentTableBody tr');
            
            studentRows.forEach(row => {
                const course = row.cells[2].textContent;
                const yearLevel = row.cells[3].textContent;
                const idCell = row.cells[0].textContent.toLowerCase();
                const nameCell = row.cells[1].textContent.toLowerCase();
                
                const courseMatch = !courseFilter || course === courseFilter;
                const yearMatch = !yearFilter || yearLevel === yearFilter;
                const searchMatch = !searchTerm || 
                    idCell.includes(searchTerm) || 
                    nameCell.includes(searchTerm);
                
                row.style.display = courseMatch && yearMatch && searchMatch ? '' : 'none';
            });
        }

        function sortTable(column, type) {
            const tbody = document.getElementById('studentTableBody');
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            const header = document.querySelector(`th[onclick*="${column}"]`);
            
            // Toggle sort direction
            const isAscending = header.getAttribute('data-sort') !== 'asc';
            header.setAttribute('data-sort', isAscending ? 'asc' : 'desc');
            
            // Update sort indicator
            document.querySelectorAll('th').forEach(th => {
                th.textContent = th.textContent.replace('↑', '').replace('↓', '');
            });
            header.textContent += isAscending ? '↑' : '↓';
            
            // Sort rows
            rows.sort((a, b) => {
                let aValue = a.cells[getColumnIndex(column)].textContent;
                let bValue = b.cells[getColumnIndex(column)].textContent;
                
                if (type === 'number') {
                    aValue = parseFloat(aValue);
                    bValue = parseFloat(bValue);
                }
                
                if (isAscending) {
                    return aValue > bValue ? 1 : -1;
                } else {
                    return aValue < bValue ? 1 : -1;
                }
            });
            
            // Reorder rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function getColumnIndex(column) {
            const columnMap = {
                'id_number': 0,
                'year_level': 3,
                'sessions': 4,
                'points': 5
            };
            return columnMap[column];
        }

        // Add event listeners for filters
        document.getElementById('courseFilter').addEventListener('change', filterStudents);
        document.getElementById('yearFilter').addEventListener('change', filterStudents);
        document.getElementById('studentSearch').addEventListener('input', filterStudents);

        // Add event listeners for new sidebar buttons
        document.getElementById('labResourcesBtn').addEventListener('click', function() {
            loadContent('labResourcesContent');
        });

        document.getElementById('labSchedulesBtn').addEventListener('click', function() {
            loadContent('labSchedulesContent');
        });

        document.getElementById('leaderboardBtn').addEventListener('click', function() {
            loadContent('leaderboardContent');
        });

        // Resource type change handler
        document.getElementById('resourceType').addEventListener('change', function() {
            const fileUploadDiv = document.getElementById('fileUploadDiv');
            const linkInputDiv = document.getElementById('linkInputDiv');
            
            if (this.value === 'link') {
                fileUploadDiv.style.display = 'none';
                linkInputDiv.style.display = 'block';
            } else {
                fileUploadDiv.style.display = 'block';
                linkInputDiv.style.display = 'none';
            }
        });

        // Open schedule modal
        function openScheduleModal(room) {
            const modal = document.getElementById('scheduleModal');
            const modalTitle = document.getElementById('scheduleModalLabel');
            const scheduleContent = document.getElementById('scheduleContent');
            
            modalTitle.textContent = 'Manage Lab ' + room + ' Schedule';
            
            // Load schedule content via AJAX
            fetch('get_lab_schedule.php?room=' + room)
                .then(response => response.text())
                .then(html => {
                    scheduleContent.innerHTML = html;
                    new bootstrap.Modal(modal).show();
                })
                .catch(error => {
                    console.error('Error loading schedule:', error);
                    scheduleContent.innerHTML = '<div class="alert alert-danger">Error loading schedule data.</div>';
                    new bootstrap.Modal(modal).show();
                });
        }

        // Delete resource
        function deleteResource(id) {
            if (confirm('Are you sure you want to delete this resource?')) {
                fetch('delete_resource.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting resource: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting resource. Please try again.');
                    });
            }
        }

        function addPoints(studentId, studentName) {
            const modal = document.createElement('div');
            modal.className = 'modal-container show';
            modal.id = 'addPointsModal';
            modal.innerHTML = `
                <div class="points-modal">
                    <span class="close" onclick="closeModal('addPointsModal')">&times;</span>
                    <h2 class="modal-title">Add Points for ${studentName}</h2>
                    <form id="addPointsForm" class="points-form" onsubmit="submitPoints(event)">
                        <input type="hidden" name="student_id" value="${studentId}">
                        <div class="form-group">
                            <label for="points">Points to Add:</label>
                            <input type="number" id="points" name="points" min="1" max="3" required>
                            <small style="color: var(--light); opacity: 0.7;">When points reach 3, a session will be automatically added.</small>
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason for Points:</label>
                            <textarea id="reason" name="reason" required placeholder="Enter the reason for adding points..."></textarea>
                        </div>
                        <div class="button-group">
                            <button type="button" class="modal-button secondary" onclick="closeModal('addPointsModal')">Cancel</button>
                            <button type="submit" class="modal-button primary">Add Points</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function submitPoints(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            // Disable submit button to prevent double submission
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Adding...';

            fetch('add_points.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('addPointsModal');
                    // Reload the student data to reflect the changes
                    loadStudentData();
                } else {
                    alert(data.message || 'Error adding points');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding points. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = 'Add Points';
            });
        }

        function viewFeedback(idNumber, loginTime) {
            fetch(`get_feedback.php?id=${idNumber}&login_time=${loginTime}`)
                .then(response => response.json())
                .then(data => {
                    const feedbackText = document.getElementById('feedbackText');
                    if (data.feedback) {
                        feedbackText.innerHTML = `
                            <p><strong>Student ID:</strong> ${data.id_number}</p>
                            <p><strong>Date:</strong> ${data.date}</p>
                            <p><strong>Feedback:</strong></p>
                            <p>${data.feedback_text}</p>
                        `;
                    } else {
                        feedbackText.innerHTML = '<p>No feedback available for this session.</p>';
                    }
                    openModal('feedbackModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading feedback. Please try again.');
                });
        }

        function openFeedbackModal(sitInId) {
            document.getElementById('sitInId').value = sitInId;
            openModal('feedbackModal');
        }

        function viewFeedback(sitInId) {
            fetch(`?get_feedback=1&sit_in_id=${sitInId}`)
                .then(response => response.json())
                .then(data => {
                    const feedbackContent = document.getElementById('feedbackContent');
                    feedbackContent.innerHTML = `
                        <p><strong>Feedback:</strong> ${data.feedback_text}</p>
                        <p><strong>Date:</strong> ${data.feedback_date}</p>
                    `;
                    openModal('viewFeedbackModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading feedback');
                });
        }

        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('submit_feedback', '1');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Feedback submitted successfully');
                    closeModal('feedbackModal');
                    location.reload();
                } else {
                    alert('Error submitting feedback');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback');
            });
        });

        function showFeedbackModal(idNumber) {
            const modal = document.getElementById('feedbackModal');
            const feedbackContent = document.getElementById('feedbackContent');
            
            // Show modal with loading state
            modal.style.display = 'flex';
            feedbackContent.innerHTML = '<div class="loading-feedback">Loading feedback data...</div>';
            
            // Fetch feedback data
            fetch(`get_feedback_data.php?id=${idNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const feedback = data[0]; // Get the most recent feedback
                        feedbackContent.innerHTML = `
                            <div class="feedback-details">
                                <p><strong>Student ID:</strong> ${feedback.id_number}</p>
                                <p><strong>Student Name:</strong> ${feedback.student_name}</p>
                                <p><strong>Lab:</strong> ${feedback.lab || 'N/A'}</p>
                                <p><strong>Date:</strong> ${new Date(feedback.date).toLocaleString()}</p>
                                <p><strong>Feedback:</strong></p>
                                <div class="feedback-text">${feedback.feedback_text}</div>
                                ${feedback.rating ? `
                                    <p class="mt-3">
                                        <strong>Rating:</strong> 
                                        <span class="rating-stars">${'★'.repeat(parseInt(feedback.rating))}${'☆'.repeat(5-parseInt(feedback.rating))}</span>
                                    </p>` : ''
                                }
                            </div>
                        `;
                    } else {
                        feedbackContent.innerHTML = `
                            <div class="no-feedback">
                                <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>No feedback available for this student.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedbackContent.innerHTML = `
                        <div class="no-feedback text-danger">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Error loading feedback. Please try again.</p>
                        </div>
                    `;
                });
        }

        function closeFeedbackModal() {
            const modal = document.getElementById('feedbackModal');
            modal.style.display = 'none';
        }

        function createActionButtons(studentId, studentName) {
            return `
                <div class="button-group">
                    <button class="feedback-button btn-sm" onclick="showFeedbackModal('${studentId}')">
                        View Feedback
                    </button>
                    <button class="add-points-btn btn-sm" onclick="addPoints('${studentId}', '${studentName}')">
                        Add Points
                    </button>
                </div>
            `;
        }

        function createLabControls(labId) {
            return `
                <button class="manage-schedule-btn btn-sm" onclick="openScheduleModal('${labId}')">
                    Manage Schedule
                </button>
            `;
        }

        // Export button in the header
        document.getElementById('exportButton').innerHTML = `
            <button class="export-btn btn-icon" onclick="showExportModal()">
                <i class="fas fa-download"></i>
                Export Data
            </button>
        `;

        function exportStudentDataToPDF() {
            window.location.href = 'export_students_pdf.php';
        }
    </script>

    <script>
        function showFeedbackModal(idNumber) {
            const modal = document.getElementById('feedbackModal');
            const feedbackContent = document.getElementById('feedbackContent');
            
            // Show modal with loading state
            modal.style.display = 'flex';
            feedbackContent.innerHTML = '<div class="loading-feedback">Loading feedback data...</div>';
            
            // Fetch feedback data
            fetch(`get_feedback_data.php?id=${idNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const feedback = data[0]; // Get the most recent feedback
                        feedbackContent.innerHTML = `
                            <div class="feedback-details">
                                <p><strong>Student ID:</strong> ${feedback.id_number}</p>
                                <p><strong>Student Name:</strong> ${feedback.student_name}</p>
                                <p><strong>Lab:</strong> ${feedback.lab || 'N/A'}</p>
                                <p><strong>Date:</strong> ${new Date(feedback.date).toLocaleString()}</p>
                                <p><strong>Feedback:</strong></p>
                                <div class="feedback-text">${feedback.feedback_text}</div>
                                ${feedback.rating ? `
                                    <p class="mt-3">
                                        <strong>Rating:</strong> 
                                        <span class="rating-stars">${'★'.repeat(parseInt(feedback.rating))}${'☆'.repeat(5-parseInt(feedback.rating))}</span>
                                    </p>` : ''
                                }
                            </div>
                        `;
                    } else {
                        feedbackContent.innerHTML = `
                            <div class="no-feedback">
                                <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>No feedback available for this student.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    feedbackContent.innerHTML = `
                        <div class="no-feedback text-danger">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Error loading feedback. Please try again.</p>
                        </div>
                    `;
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target === modal) {
                closeModal('feedbackModal');
            }
        });
    </script>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeFeedbackModal()">&times;</span>
            <h2>Student Feedback History</h2>
            <div id="feedbackContent">
                <div class="student-info">
                    <p><strong>ID Number:</strong> <span id="feedbackStudentId"></span></p>
                    <p><strong>Purpose:</strong> <span id="feedbackPurpose"></span></p>
                    <p><strong>Lab:</strong> <span id="feedbackLab"></span></p>
                </div>
                <div class="feedback-list">
                    <!-- Feedback items will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    // ... existing code ...

    function showFeedbackModal(idNumber) {
        fetch('get_feedback_data.php?id=' + idNumber)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.feedbacks.length > 0) {
                    const feedback = data.feedbacks[0]; // Get the most recent feedback
                    document.getElementById('feedbackStudentId').textContent = idNumber;
                    document.getElementById('feedbackPurpose').textContent = feedback.purpose;
                    document.getElementById('feedbackLab').textContent = feedback.lab;

                    const feedbackList = document.querySelector('.feedback-list');
                    feedbackList.innerHTML = data.feedbacks.map(f => `
                        <div class="feedback-item">
                            <p class="feedback-date">${new Date(f.feedback_date).toLocaleString()}</p>
                            <p class="feedback-text">${f.feedback_text}</p>
                        </div>
                    `).join('');

                    document.getElementById('feedbackModal').style.display = 'block';
                } else {
                    alert('No feedback found for this student.');
                }
            })
            .catch(error => {
                console.error('Error fetching feedback:', error);
                alert('Error fetching feedback. Please try again.');
            });
    }

    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('feedbackModal');
        if (event.target === modal) {
            closeFeedbackModal();
        }
    }

    // ... existing code ...
    </script>

    <style>
    /* ... existing styles ... */

    .feedback-list {
        margin-top: 20px;
        max-height: 400px;
        overflow-y: auto;
    }

    .feedback-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .feedback-date {
        color: var(--primary);
        font-size: 0.9em;
        margin-bottom: 8px;
    }

    .feedback-text {
        white-space: pre-wrap;
        line-height: 1.5;
    }

    .student-info {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .student-info p {
        margin: 5px 0;
    }

    /* ... existing styles ... */
    </style>

    // ... existing code ...
    </body>
</html>
<?php if ($conn instanceof mysqli) { mysqli_close($conn); } ?>
