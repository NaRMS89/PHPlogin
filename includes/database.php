<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "logindb";
$conn = null;

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
} catch (mysqli_sql_exception $e) {
    echo "Could not connect: " . $e->getMessage();
    exit();
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