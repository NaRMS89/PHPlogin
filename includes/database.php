<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "logindb";
$conn = null;


try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    error_log("Database connection established successfully.");
}

} catch (Exception $e) {
    die("Could not connect: " . $e->getMessage());
}

$check_column_query = "SHOW COLUMNS FROM info LIKE 'profile_picture'";
$column_result = mysqli_query($conn, $check_column_query);
if (mysqli_num_rows($column_result) == 0) {
    $alter_table_query = "ALTER TABLE info ADD profile_picture VARCHAR(255) DEFAULT 'default.png'";
    if (!mysqli_query($conn, $alter_table_query)) {
        die("Error adding profile_picture column: " . mysqli_error($conn));
    }
}
?>
