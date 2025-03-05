<?php
session_start();
include("database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="profile-header">
            <h2>Admin Dashboard</h2>
        </div>
        <button id="homeBtn" class="sidebar-button">Home</button>
        <button id="searchBtn" class="sidebar-button">Search</button>
        <button id="studentsBtn" class="sidebar-button">Students</button>
        <button id="viewSitInBtn" class="sidebar-button">View Sit-in</button>
        <button id="sitInReportBtn" class="sidebar-button">Sit-in Report</button>
        <button id="feedbackReportBtn" class="sidebar-button">Feedback Report</button>
        <button id="reservationBtn" class="sidebar-button">Reservation</button>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <button type="submit" name="logout" class="sidebar-button">Logout</button>
        </form>
    </div>
    <div class="main-content">
        <div id="dynamicContent">
        </div>
    </div>
</div>

<div id="searchModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeSearchModal">&times;</span>
        <h2>Search Student</h2>
        <input type="text" id="searchId" placeholder="Enter Student ID">
        <button id="performSearch" class="search-button">Search</button>
    </div>
</div>

<div id="sitInModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeSitInModal">&times;</span>
        <h2>Sit-in Form</h2>
        <div id="sitInDetails">
            <p><strong>Student ID:</strong> <span id="sitInStudentId"></span></p>
            <p><strong>Student Name:</strong> <span id="sitInStudentName"></span></p>
        </div>
        <label for="purpose">Purpose:</label>
        <select id="purpose">
            <option value="c">C Programming</option>
            <option value="java">Java Programming</option>
            <option value="csharp">C# Programming</option>
            <option value="php">PHP Programming</option>
            <option value="aspnet">ASP.NET Programming</option>
        </select>
        <label for="lab">Lab:</label>
        <select id="lab">
            <option value="524">524</option>
            <option value="526">526</option>
            <option value="528">528</option>
            <option value="530">530</option>
            <option value="542">542</option>
            <option value="maclab">Mac Lab</option>
        </select>
        <p><strong>Remaining Sessions:</strong> <span id="remainingSessions"></span></p>
        <button id="performSitIn" class="sit-in-button">Sit-in</button>
    </div>
</div>

<style>
    #sitInModal .modal-content {
        max-width: 240px;
    }
</style>

<script>
    var buttons = document.querySelectorAll('.sidebar-button');
    var modal = document.getElementById("searchModal");
    var span = document.getElementById("closeSearchModal");
    var searchButton = document.getElementById("performSearch");
    var sitInModal = document.getElementById("sitInModal");
    var closeSitInModal = document.getElementById("closeSitInModal");
    var performSitIn = document.getElementById("performSitIn");

    buttons.forEach(function(button) {
        button.onclick = function() {
            var contentId = button.id.replace('Btn', 'Content');
            if (button.id === 'searchBtn') {
                modal.style.display = "block";
            } else {
                loadContent(contentId);
            }
        }
    });

    span.onclick = function() {
        modal.style.display = "none";
    }

    closeSitInModal.onclick = function() {
        sitInModal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
        if (event.target == sitInModal) {
            sitInModal.style.display = "none";
        }
    }

    searchButton.onclick = function() {
        var studentId = document.getElementById("searchId").value;
        performSearchAndOpenModal(studentId);
    }

    function performSearchAndOpenModal(studentId) {
        fetch('search_student.php?id=' + encodeURIComponent(studentId))
            .then(response => response.json())
            .then(data => {
                if (data) {
                    modal.style.display = "none"; // Close search modal
                    sitInModal.style.display = "block"; // Open sit-in modal
                    document.getElementById('sitInStudentId').textContent = data.id_number;
                    document.getElementById('sitInStudentName').textContent = data.name;
                    document.getElementById('remainingSessions').textContent = data.sessions;
                } else {
                    alert("Student not found.");
                    modal.style.display = "none";
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred.");
                modal.style.display = "none";
            });
    }

    function loadContent(contentId) {
        var content = '';
        switch (contentId) {
            case 'homeContent':
                content = '<p>Welcome to the Admin Dashboard Home Page.</p>';
                break;
            case 'studentsContent':
                content = `
                    <h2>Student List</h2>
                    <p>List of students goes here...</p>
                `;
                break;
            case 'viewSitInContent':
                content = `
                    <h2>View Current Sit-ins</h2>
                    <p>View current sit-in details here...</p>
                `;
                break;
            case 'sitInReportContent':
                content = `
                    <h2>Sit-in Reports</h2>
                    <p>Sit-in reports and analytics go here...</p>
                `;
                break;
            case 'feedbackReportContent':
                content = `
                    <h2>Feedback Reports</h2>
                    <p>User feedback and reports go here...</p>
                `;
                break;
            case 'reservationContent':
                content = `
                    <h2>Reservations</h2>
                    <p>View and manage reservations here...</p>
                `;
                break;
            default:
                content = '<p>Content not found.</p>';
        }
        document.getElementById('dynamicContent').innerHTML = content;
    }

    loadContent('homeContent');
</script>
</body>
</html>