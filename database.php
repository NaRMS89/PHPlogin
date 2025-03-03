<?php

$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database_name";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the profile_picture column exists
$check_column_query = "SHOW COLUMNS FROM info LIKE 'profile_picture'";
$column_result = mysqli_query($conn, $check_column_query);
if (mysqli_num_rows($column_result) == 0) {
    $alter_table_query = "ALTER TABLE info ADD profile_picture VARCHAR(255) DEFAULT 'default.png'";
    if (!mysqli_query($conn, $alter_table_query)) {
        die("Error adding profile_picture column: " . mysqli_error($conn));
    }
}

// Add this code to ensure the profile_picture column exists
$alter_table_sql = "ALTER TABLE info ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT 'default.png'";
if (!mysqli_query($conn, $alter_table_sql)) {
    die("Error altering table: " . mysqli_error($conn));
}
?>