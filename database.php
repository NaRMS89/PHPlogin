<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "logindb"; // Updated database name
$conn = "";

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
} 
catch(Exception $e) {
    echo '<div class="connection-error">could not connect! <br>' . $e->getMessage() . '</div>';
}
?>