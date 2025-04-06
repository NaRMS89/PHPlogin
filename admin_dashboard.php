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
            i.first_name, i.last_name, i.sessions 
            FROM sitin s 
            JOIN info i ON s.id_number = i.id_number 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

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
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: flex-start; /* Changed from center to flex-start */
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            overflow-y: auto; /* Allow background scrolling */
        }

        .modal-container.show {
            display: flex;
        }

        .modal {
            width: 40rem;
            max-height: 80vh; /* Limit height to 80% of viewport */
            overflow-y: auto; /* Enable scrolling inside modal */
            padding: 3rem 2rem;
            border-radius: 0.8rem;
            color: var(--light);
            background: var(--background);
            box-shadow: 0.4rem 0.4rem 10.2rem 0.2rem var(--shadow-1);
            position: relative;
            margin-top: 5vh; /* Add some space from top */
        }

        .modal::-webkit-scrollbar {
            width: 8px;
        }

        .modal::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .modal::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

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
        input:invalid {
            border-color: #f44336;
        }
        input:valid {
            border-color: #4CAF50;
        }

        #addStudentForm {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        #addStudentForm input,
        #addStudentForm select {
            margin: 5px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--light);
        }

        .search-modal,
        .history-modal {
            width: 90%;
            max-width: 800px;
            padding: 4rem 2rem;
            border-radius: 0.8rem;
            background: var(--background);
            box-shadow: 0.4rem 0.4rem 10.2rem 0.2rem var(--shadow-1);
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
            transition: 0.2s;
            cursor: pointer;
        }

        .close:hover {
            background: var(--focus);
            border-color: var(--focus);
            transform: translateY(-0.2rem);
        }

        /* Form Styles */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 1.4rem;
            margin: 8px 0;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: transparent;
            color: var(--light);
            font-size: 1.4rem;
        }

        button {
            padding: 1rem 1.6rem;
            border: 1px solid var(--border-color);
            border-radius: 100rem;
            color: var(--light);
            background: transparent;
            font-size: 1.4rem;
            letter-spacing: 0.2rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-0.2rem);
        }

        button:active {
            transform: translateY(0);
        }

        .sidebar-button:hover {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateX(5px);
        }

        .modal__btn:hover,
        .modal__btn:focus {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-.2rem);
        }

        .link-1:hover,
        .link-1:focus {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-.2rem);
        }

        .link-2:hover,
        .link-2:focus {
            background: transparent;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary),
                       0 0 30px var(--primary),
                       0 0 45px var(--primary);
            transform: translateY(-.2rem);
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
            margin-top: 30px;
            background: var(--background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0.4rem 0.4rem 2.4rem 0.2rem var(--shadow-1);
        }

        .announcement-form {
            padding: 20px;
        }

        .announcement-form h3,
        .announcement-list h3 {
            color: var(--light);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .announcement-form textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: transparent;
            color: var(--light);
            margin-bottom: 15px;
            resize: vertical;
        }

        .announcement-list {
            padding: 20px;
        }

        .announcement-scroll {
            height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .announcement-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .announcement-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .announcement-scroll::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .announcement-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }

        .announcement-item:last-child {
            margin-bottom: 0;
        }

        .announcement-text {
            color: var(--light);
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .announcement-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.2rem;
            text-align: right;
            font-style: italic;
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
            display: flex;
            flex-direction: column;
            gap: 20px;
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

        #addStudentForm button[type="submit"] {
            padding: 12px 25px;
            font-size: 1.1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: var(--light);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            width: 100%;
        }

        #addStudentForm button[type="submit"]:hover {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary);
            transform: translateY(-2px);
        }

        .form-group label {
            color: var(--light);
            margin-bottom: 8px;
            display: block;
            font-size: 1rem;
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
        #logoutModal .modal {
            text-align: center;
            padding: 3rem 2rem;
        }

        #logoutModal p {
            font-size: 1.8rem;
            margin-bottom: 2rem;
        }

        #logoutModal button {
            min-width: 120px;
            margin: 0 10px;
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
    </style>

</head>
<body>
    <div class="sidebar">
        <button id="homeBtn" class="sidebar-button">Home</button>
        <button id="searchBtn" class="sidebar-button">Search</button>
        <button id="studentBtn" class="sidebar-button">Students</button>
        <button id="sitinBtn" class="sidebar-button">Current Sit-in</button>
        <button id="sitInDataBtn" class="sidebar-button">Sit-in Data</button>
        <button id="feedbackReservationBtn" class="sidebar-button">Feedback Reports</button>
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
                            <textarea name="announcement" rows="4" required></textarea>
                            <button type="submit">Submit</button>
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
                        <!-- Search results will be displayed here -->
                    </div>
                </div>
            </div>

            <!-- Student Content -->
            <div id="studentContent" style="display: none;">
                <div class="student-header">
                    <h2>Student List</h2>
                    <div class="filter-controls" style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <select id="courseFilter" onchange="filterStudents()">
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
                        <select id="yearFilter" onchange="filterStudents()">
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
                        <tbody id="sitinTableBody"></tbody>
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
                                    $lab_rooms = ['524', '526', '528', '530', '542', 'Mac Lab'];
                                    
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
                                        <th>Admin Points</th>
                                        <th>Total Points</th>
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
                                            $total_points = $lab_points + $point_row['admin_points'];
                                            
                                            echo "<tr>";
                                            echo "<td>" . $rank . "</td>";
                                            echo "<td>" . htmlspecialchars($point_row['id_number']) . "</td>";
                                            echo "<td>" . htmlspecialchars($point_row['first_name'] . " " . $point_row['last_name']) . "</td>";
                                            echo "<td>" . $lab_points . "</td>";
                                            echo "<td>" . $point_row['admin_points'] . "</td>";
                                            echo "<td>" . $total_points . "</td>";
                                            echo "</tr>";
                                            
                                            $rank++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Add Admin Points</h5>
                    </div>
                    <div class="card-body">
                        <form id="adminPointsForm" action="add_admin_points.php" method="post">
                            <div class="mb-3">
                                <label for="studentId" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="studentId" name="id_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="points" class="form-label">Points</label>
                                <input type="number" class="form-control" id="points" name="points" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Points</button>
                        </form>
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
                                <option value="Mac Lab">Mac Lab</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exportPurposeFilter">Purpose:</label>
                            <select id="exportPurposeFilter" class="form-control">
                                <option value="">All Purposes</option>
                                <option value="C Programming">C Programming</option>
                                <option value="Java Programming">Java Programming</option>
                                <option value="C# Programming">C# Programming</option>
                                <option value="PHP Programming">PHP Programming</option>
                                <option value="ASP.NET Programming">ASP.NET Programming</option>
                            </select>
                        </div>
                        <div class="button-group">
                            <button onclick="applyExportFilters()" class="modal-button primary">Export</button>
                            <button onclick="closeModal('exportFilterModal')" class="modal-button secondary">Cancel</button>
                        </div>
                    </div>
                </div>
                
                <div class="search-control" style="margin: 20px 0;">
                    <input type="text" id="searchInput" placeholder="Search by ID, Name, Purpose, or Lab..." style="width: 100%; padding: 10px;">
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
                                <th onclick="sortTable(0)">Student ID ↕</th>
                                <th onclick="sortTable(1)">Purpose ↕</th>
                                <th onclick="sortTable(2)">Lab ↕</th>
                                <th onclick="sortTable(3)">Login Time ↕</th>
                                <th onclick="sortTable(4)">Logout Time ↕</th>
                                <th onclick="sortTable(5)">Duration ↕</th>
                            </tr>
                        </thead>
                        <tbody id="sitInDataBody"></tbody>
                    </table>
                </div>

                <div class="pagination" style="text-align: center; margin-top: 20px;">
                    <button onclick="goToFirstPage()"><<</button>
                    <button onclick="goToPreviousPage()"><</button>
                    <span id="currentPage">1</span>
                    <button onclick="goToNextPage()">></button>
                    <button onclick="goToLastPage()">>></button>
                </div>
            </div>
        </div>
    </main>

    <div id="studentInfoModal" class="modal-container">
        <div class="modal">
            <span class="close" onclick="closeModal('studentInfoModal')">&times;</span>
            
            <h2 class="modal-title">Sit-in Form</h2>
            <div class="form-group">
                <p><b>ID Number:</b> <span id="studentIdNo"></span></p>
                <p><b>Student Name:</b> <span id="studentName"></span></p>
                <p><b>Remaining Sessions:</b> <span id="remainingSessions"></span></p>
            </div>

            <div class="form-group">
                <label for="purpose"><b>Purpose:</b></label>
                <select id="purpose" class="compact-select">
                    <option value="C Programming">C Programming</option>
                    <option value="Java Programming">Java Programming</option>
                    <option value="C# Programming">C# Programming</option>
                    <option value="PHP Programming">PHP Programming</option>
                    <option value="ASP.NET Programming">ASP.NET Programming</option>
                </select>
            </div>

            <div class="form-group">
                <label for="lab"><b>Lab:</b></label>
                <select id="lab" class="compact-select">
                    <option value="524">524</option>
                    <option value="526">526</option>
                    <option value="528">528</option>
                    <option value="530">530</option>
                    <option value="542">542</option>
                    <option value="Mac Lab">Mac Lab</option>
                </select>
            </div>

            <div class="button-group">
                <button class="modal-button primary" onclick="addSitIn()">Sit-in</button>
                <button class="modal-button secondary" onclick="closeModal('studentInfoModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-container">
        <div class="modal">
            <span class="close" onclick="closeModal('logoutModal')">&times;</span>
            <h2 class="modal-title">Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <button type="submit" name="logout">Logout</button>
                <button type="button" onclick="closeModal('logoutModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal-container">
        <div class="modal">
            <span class="close" id="closeAddStudentModal">&times;</span>
            <h2>Add Student</h2>
            <form id="addStudentForm">
                ID Number: <br>
                <input type="text" id="idno" name="idno" required><br>

                Last Name: <br>
                <input type="text" id="lastname" name="lastname" required><br>

                First Name: <br>
                <input type="text" id="firstname" name="firstname" required><br>

                Middle Name: <br>
                <input type="text" id="midname" name="midname" required><br>

                Course: <br>
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
                </select><br>

                Year Level: <br>
                <select id="yearlvl" name="yearlvl" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select><br>

                Email: <br>
                <input type="email" id="email" name="email" required><br>

                Username: <br>
                <input type="text" id="username" name="username" required><br>

                Password: <br>
                <input type="password" id="password" name="password" required><br>

                <input type="submit" value="Add Student">
            </form>
            <div id="form-message"></div>
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
                                    'hsla(145, 100%, 70%, 0.7)',  // Green
                                    'hsla(45, 100%, 70%, 0.7)',   // Yellow
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
                                <div onclick="selectStudent('${student.id_number}')">
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

        function selectStudent(idNo) {
            document.getElementById('searchIdNo').value = idNo;
            searchResults.innerHTML = '';
            
            // First check if student is already in sit-in
            fetch(`check_existing_sitin.php?id=${idNo}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    alert('This student is already in sit-in.');
                    return;
                }
                
                // If not in sit-in, proceed with fetching student data
                fetch('get_student_data.php?id=' + idNo)
                .then(response => response.json())
                .then(student => {
                    if (student) {
                        if (student.sessions <= 0) {
                            alert('This student has no remaining sessions.');
                            return;
                        }
                        
                        document.getElementById('studentName').innerText = student.first_name + ' ' + student.last_name;
                        document.getElementById('studentIdNo').innerText = student.id_number;
                        document.getElementById('remainingSessions').innerText = student.sessions;
                        closeModal('searchModal');
                        openModal('studentInfoModal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching student data. Please try again.');
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking student status. Please try again.');
            });
        }

        function openModal(modalId) {
            // Hide all modals first
            document.querySelectorAll('.modal-container').forEach(modal => {
                modal.style.display = "none";
                modal.classList.remove('show');
            });
            
            // Show the requested modal
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "flex";
                modal.classList.add('show');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "none";
                modal.classList.remove('show');
                
                // Clear form messages if it's the add student modal
                if (modalId === 'addStudentModal') {
                    document.getElementById('form-message').innerHTML = '';
                    document.getElementById('addStudentForm').reset();
                }
                
                // Remove dynamically created modals from DOM
                if (modalId === 'addPointsModal') {
                    modal.remove();
                }
            }
        }

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
            // Open modal for adding points
            const modal = document.createElement('div');
            modal.className = 'modal-container show';
            modal.id = 'addPointsModal';
            modal.innerHTML = `
                <div class="modal">
                    <span class="close" onclick="closeModal('addPointsModal')">&times;</span>
                    <h2 class="modal-title">Add Points for ${studentName}</h2>
                    <form id="addPointsForm" onsubmit="submitPoints(event)">
                        <input type="hidden" name="student_id" value="${studentId}">
                        <div class="form-group">
                            <label for="points">Points:</label>
                            <input type="number" id="points" name="points" min="1" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason:</label>
                            <textarea id="reason" name="reason" required class="form-control"></textarea>
                        </div>
                        <div class="button-group">
                            <button type="submit" class="modal-button primary">Add Points</button>
                            <button type="button" class="modal-button secondary" onclick="closeModal('addPointsModal')">Cancel</button>
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

            fetch('add_points.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('addPointsModal');
                    loadStudentData(); // Reload the student list
                } else {
                    alert(data.message || 'Error adding points');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding points. Please try again.');
            });
        }
    </script>
</body>
</html>
<?php if ($conn instanceof mysqli) { mysqli_close($conn); } ?>
