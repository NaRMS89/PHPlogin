<?php
include("database.php");

// Check if tables exist and create them if they don't

// Feedback table
$check_feedback_table = "SHOW TABLES LIKE 'feedback'";
$feedback_table_exists = mysqli_query($conn, $check_feedback_table);

if (mysqli_num_rows($feedback_table_exists) == 0) {
    $create_feedback_table = "CREATE TABLE feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        feedback_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES info(id_number)
    )";
    
    if (!mysqli_query($conn, $create_feedback_table)) {
        die("Error creating feedback table: " . mysqli_error($conn));
    }
    echo "Feedback table created successfully.<br>";
}

// Lab Resources table
$check_resources_table = "SHOW TABLES LIKE 'lab_resources'";
$resources_table_exists = mysqli_query($conn, $check_resources_table);

if (mysqli_num_rows($resources_table_exists) == 0) {
    $create_resources_table = "CREATE TABLE lab_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        file_type VARCHAR(50),
        uploaded_by VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES info(id_number)
    )";
    
    if (!mysqli_query($conn, $create_resources_table)) {
        die("Error creating lab_resources table: " . mysqli_error($conn));
    }
    echo "Lab Resources table created successfully.<br>";
}

// Lab Schedules table
$check_schedules_table = "SHOW TABLES LIKE 'lab_schedules'";
$schedules_table_exists = mysqli_query($conn, $check_schedules_table);

if (mysqli_num_rows($schedules_table_exists) == 0) {
    $create_schedules_table = "CREATE TABLE lab_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lab_room VARCHAR(50) NOT NULL,
        time_slot VARCHAR(50) NOT NULL,
        status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
        student_id VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES info(id_number)
    )";
    
    if (!mysqli_query($conn, $create_schedules_table)) {
        die("Error creating lab_schedules table: " . mysqli_error($conn));
    }
    echo "Lab Schedules table created successfully.<br>";
}

// Add points columns to info table
$check_points_column = "SHOW COLUMNS FROM info LIKE 'points'";
$points_column_exists = mysqli_query($conn, $check_points_column);

if (mysqli_num_rows($points_column_exists) == 0) {
    $add_points_column = "ALTER TABLE info ADD points INT DEFAULT 0";
    
    if (!mysqli_query($conn, $add_points_column)) {
        die("Error adding points column: " . mysqli_error($conn));
    }
    echo "Points column added to info table successfully.<br>";
}

// Add admin_points column to info table
$check_admin_points_column = "SHOW COLUMNS FROM info LIKE 'admin_points'";
$admin_points_column_exists = mysqli_query($conn, $check_admin_points_column);

if (mysqli_num_rows($admin_points_column_exists) == 0) {
    $add_admin_points_column = "ALTER TABLE info ADD admin_points INT DEFAULT 0";
    
    if (!mysqli_query($conn, $add_admin_points_column)) {
        die("Error adding admin_points column: " . mysqli_error($conn));
    }
    echo "Admin Points column added to info table successfully.<br>";
}

// Add feedback column to sitin table
$check_feedback_column = "SHOW COLUMNS FROM sitin LIKE 'feedback'";
$feedback_column_exists = mysqli_query($conn, $check_feedback_column);

if (mysqli_num_rows($feedback_column_exists) == 0) {
    $add_feedback_column = "ALTER TABLE sitin ADD feedback TEXT";
    
    if (!mysqli_query($conn, $add_feedback_column)) {
        die("Error adding feedback column: " . mysqli_error($conn));
    }
    echo "Feedback column added to sitin table successfully.<br>";
}

// Initialize lab schedules with default values
$check_schedules_data = "SELECT COUNT(*) as count FROM lab_schedules";
$schedules_data_exists = mysqli_query($conn, $check_schedules_data);
$schedules_count = mysqli_fetch_assoc($schedules_data_exists)['count'];

if ($schedules_count == 0) {
    $lab_rooms = ['524', '526', '528', '530', '542', 'Mac Lab'];
    $time_slots = [
        '7:00 AM - 8:00 AM', '8:00 AM - 9:00 AM', '9:00 AM - 10:00 AM',
        '10:00 AM - 11:00 AM', '11:00 AM - 12:00 PM', '12:00 PM - 1:00 PM',
        '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM', '3:00 PM - 4:00 PM',
        '4:00 PM - 5:00 PM', '5:00 PM - 6:00 PM', '6:00 PM - 7:00 PM',
        '7:00 PM - 8:00 PM', '8:00 PM - 9:00 PM'
    ];
    
    foreach ($lab_rooms as $room) {
        foreach ($time_slots as $slot) {
            $insert_schedule = "INSERT INTO lab_schedules (lab_room, time_slot) VALUES ('$room', '$slot')";
            mysqli_query($conn, $insert_schedule);
        }
    }
    echo "Lab schedules initialized with default values.<br>";
}

echo "Database setup completed successfully.";
?> 