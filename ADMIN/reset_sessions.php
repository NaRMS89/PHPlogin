<?php
session_start();
session_unset();
session_destroy();

header('Content-Type: application/json');
$response = array('message' => 'Sessions have been reset.');
echo json_encode($response);
?>
