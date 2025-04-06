<?php
include("../includes/database.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = filter_input(INPUT_POST, "idno", FILTER_SANITIZE_SPECIAL_CHARS);
    $lastname = filter_input(INPUT_POST, "lastname", FILTER_SANITIZE_SPECIAL_CHARS);
    $firstname = filter_input(INPUT_POST, "firstname", FILTER_SANITIZE_SPECIAL_CHARS);
    $midname = filter_input(INPUT_POST, "midname", FILTER_SANITIZE_SPECIAL_CHARS);
    $course = filter_input(INPUT_POST, "course", FILTER_SANITIZE_SPECIAL_CHARS);
    $yearlvl = filter_input(INPUT_POST, "yearlvl", FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($idno) || empty($lastname) || empty($firstname) || empty($username) || empty($password) || empty($email) || empty($course) || empty($yearlvl) || empty($midname)) {
        $response = array('success' => false, 'message' => 'All fields are required.');
    } else {
        $check_idno_user_email_sql = "SELECT * FROM info WHERE id_number = ? OR username = ? OR email = ?";
        $stmt = $conn->prepare($check_idno_user_email_sql);
        if ($stmt === false) {
            $response = array('success' => false, 'message' => 'Prepare failed: ' . $conn->error);
            echo json_encode($response);
            exit;
        }
        $stmt->bind_param("sss", $idno, $username, $email);
        if ($stmt->execute() === false) {
            $response = array('success' => false, 'message' => 'Execute failed: ' . $stmt->error);
            echo json_encode($response);
            exit;
        }
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response = array('success' => false, 'message' => 'ID Number, Username, or email already exists. Please choose different ones.');
        } else {
            // Set default profile picture
            $profile_picture = 'default.png';

            $sql = "INSERT INTO info (id_number, last_name, first_name, middle_name, course, year_level, email, username, password, profile_picture) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $response = array('success' => false, 'message' => 'Prepare failed: ' . $conn->error);
                echo json_encode($response);
                exit;
            }
            $stmt->bind_param("ssssssssss", $idno, $lastname, $firstname, $midname, $course, $yearlvl, $email, $username, $password, $profile_picture);

            if ($stmt->execute() === false) {
                $response = array('success' => false, 'message' => 'Execute failed: ' . $stmt->error);
            } else {
                $response = array('success' => true, 'message' => 'Registration successful.');
            }
        }
    }

    if ($conn instanceof mysqli) {
        $conn->close();
    }

    echo json_encode($response);
} else {
    $response = array('success' => false, 'message' => 'Invalid request method.');
    echo json_encode($response);
}
?>
