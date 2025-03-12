<?php
include("../includes/database.php"); // Include your database connection

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    if($conn){ // check if the connection to the database is working.
        $sql = "SELECT * FROM info WHERE id = '$id'"; // Assuming your ID column is named 'id'
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            echo '<h3>Search Results:</h3>';
            echo '<table>';
            echo '<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Course</th><th>Year Level</th><th>Email</th></tr>';
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['firstname'] . '</td>';
                echo '<td>' . $row['lastname'] . '</td>';
                echo '<td>' . $row['course'] . '</td>';
                echo '<td>' . $row['year_level'] . '</td>';
                echo '<td>' . $row['email'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No results found for ID: ' . $id . '</p>';
        }
    }else{
        echo "<p>Database connection error.</p>";
    }

} else {
    echo '<p>Invalid request.</p>';
}
?>