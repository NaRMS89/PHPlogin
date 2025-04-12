<?php
// Include database connection
include '../includes/database.php';

// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $date_added = date('Y-m-d H:i:s');
    
    // Handle file upload for PDF and document types
    if ($type != 'link' && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file = $_FILES['file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed = array('pdf', 'doc', 'docx', 'txt');
        
        if (in_array($file_ext, $allowed)) {
            if ($file_error === 0) {
                if ($file_size < 5000000) { // 5MB max
                    // Create unique file name
                    $file_name_new = uniqid('resource_', true) . '.' . $file_ext;
                    $file_destination = '../uploads/resources/' . $file_name_new;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists('../uploads/resources')) {
                        mkdir('../uploads/resources', 0777, true);
                    }
                    
                    // Move file to destination
                    if (move_uploaded_file($file_tmp, $file_destination)) {
                        $file_path = 'uploads/resources/' . $file_name_new;
                    } else {
                        $_SESSION['error'] = "Error uploading file.";
                        header('Location: admin_dashboard.php');
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "File size too large. Maximum size is 5MB.";
                    header('Location: admin_dashboard.php');
                    exit;
                }
            } else {
                $_SESSION['error'] = "Error uploading file.";
                header('Location: admin_dashboard.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "File type not allowed. Allowed types: PDF, DOC, DOCX, TXT";
            header('Location: admin_dashboard.php');
            exit;
        }
    } 
    // Handle link type
    else if ($type == 'link' && isset($_POST['link'])) {
        $file_path = $_POST['link'];
    } 
    else {
        $_SESSION['error'] = "Please provide a file or link.";
        header('Location: admin_dashboard.php');
        exit;
    }
    
    // Insert resource into database
    $insert_sql = "INSERT INTO lab_resources (title, description, type, file_path, date_added) 
                   VALUES ('$title', '$description', '$type', '$file_path', '$date_added')";
    
    if (mysqli_query($conn, $insert_sql)) {
        $_SESSION['success'] = "Resource uploaded successfully.";
    } else {
        $_SESSION['error'] = "Error uploading resource: " . mysqli_error($conn);
    }
    
    header('Location: admin_dashboard.php');
    exit;
} else {
    // If not submitted via POST, redirect to dashboard
    header('Location: admin_dashboard.php');
    exit;
}
?> 