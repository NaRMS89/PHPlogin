<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Basic modal styling */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 600px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Student Management</h1>

    <button onclick="openModal()">Add Student</button>
    <button onclick="resetSessions()">Reset Sessions</button>

    <!-- The Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Register</h2>
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

                <input type="submit" value="Register">
            </form>
            <div id="form-message"></div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("addStudentModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("addStudentModal").style.display = "none";
        }

        // Close the modal if the user clicks outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("addStudentModal");
            if (event.target == modal) {
                closeModal();
            }
        }

        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            var formData = new FormData(this);

            fetch('../includes/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('form-message').innerHTML = '<div class="success">' + data.message + '</div>';
                    document.getElementById('addStudentForm').reset(); // Clear the form
                    //closeModal(); // Optionally close the modal on success
                } else {
                    document.getElementById('form-message').innerHTML = '<div class="error">' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('form-message').innerHTML = '<div class="error">An error occurred.</div>';
            });
        });

        function resetSessions() {
            fetch('../includes/reset_sessions.php')
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while resetting sessions.');
            });
        }
    </script>
</body>
</html>
