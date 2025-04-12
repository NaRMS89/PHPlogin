<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../user/index.php");
    exit();
}

function getStudentData($idNo, $conn) {
    $sql = "SELECT * FROM info WHERE id_number = '$idNo'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

function getAllStudents($conn) {
    $sql = "SELECT * FROM info";
    $result = mysqli_query($conn, $sql);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function addStudentToSitIn($idNo, $purpose, $lab, $conn) {
    $sql = "INSERT INTO sitin (id_number, purpose, lab, status) VALUES ('$idNo', '$purpose', '$lab', 'active')";
    return mysqli_query($conn, $sql);
}

function getCurrentSitInStudents($conn) {
    $sql = "SELECT s.*, i.first_name, i.last_name, i.sessions FROM sitin s JOIN info i ON s.id_number = i.id_number WHERE s.status = 'active'";
    $result = mysqli_query($conn, $sql);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

function removeStudentFromSitIn($idNo, $conn) {
    $sql = "UPDATE sitin SET status = 'inactive' WHERE id_number = '$idNo' AND status = 'active'";
    mysqli_query($conn, $sql);

    $sql = "INSERT INTO sitin_report (id_number, purpose, lab, logout_time) SELECT id_number, purpose, lab, NOW() FROM sitin WHERE id_number = '$idNo' AND status = 'inactive'";
    return mysqli_query($conn, $sql);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout_sitin'])) {
    removeStudentFromSitIn($_POST['id_number'], $conn);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sitin'])) {
    addStudentToSitIn($_POST['id_number'], $_POST['purpose'], $_POST['lab'], $conn);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    // Implement adding a new student
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_sessions'])) {
    $sql = "UPDATE info SET sessions = 10"; // Reset sessions to 10
    mysqli_query($conn, $sql);
}

$currentSitInStudents = getCurrentSitInStudents($conn);
$allStudents = getAllStudents($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../styles.css">

</head>
<body>
    <div class="sidebar">
        <button id="homeBtn" class="sidebar-button">Home</button>
        <button id="searchBtn" class="sidebar-button">Search</button>
        <button id="studentBtn" class="sidebar-button">Students</button>
        <button id="sitinBtn" class="sidebar-button">Current Sit-in</button>
        <button id="viewSitInBtn" class="sidebar-button">View Sit-in History</button>
        <button id="sitInReportBtn" class="sidebar-button">Sit-in Reports</button>
        <button id="feedbackReservationBtn" class="sidebar-button">Feedback Reports</button>
        <button id="reservationBtn" class="sidebar-button">Reservation</button>
        <button id="logoutBtn" class="sidebar-button">Logout</button>
    </div>
    
    <!-- Added main container with left margin to offset the sidebar -->
    <main style="margin-left: 220px;">
        <div id="dynamicContent"></div>

    </main>

    <div id="searchModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('searchModal')">&times;</span>
            <h2>SEARCH STUDENT</h2>
            <div class="search-form">
                <input type="text" id="searchIdNo" placeholder="Enter ID Number">
                <button onclick="searchStudent()">Search</button>
            </div>
        </div>
    </div>

    <div id="studentInfoModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('studentInfoModal')">&times;</span>
            
            <h2>SIT IN FORM</h2>
            <p><b>ID Number:</b> <span id="studentIdNo"></span></p>
            <p><b>Student Name:</b> <span id="studentName"></span></p>

            <label for="purpose"><b>Purpose:</b></label>
            <select id="purpose">
                <option value="C Programming">C Programming</option>
                <option value="Java Programming">Java Programming</option>
                <option value="C# Programming">C# Programming</option>
                <option value="PHP Programming">PHP Programming</option>
                <option value="ASP.NET Programming">ASP.NET Programming</option>
            </select><br><br>

            <label for="lab"><b>Lab:</b></label>
            <select id="lab">
                <option value="524">524</option>
                <option value="526">526</option>
                <option value="528">528</option>
                <option value="530">530</option>
                <option value="542">542</option>
                <option value="Mac Lab">Mac Lab</option>
            </select><br><br>

            <p><b>Remaining Sessions:</b> <span id="remainingSessions"></span></p>

            <button class="modal-button" onclick="addSitIn()">Sit-in</button>
            <button class="modal-button" onclick="closeModal('studentInfoModal')">Close</button>
        </div>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('logoutModal')">&times;</span>
            <p>Are you sure you want to logout?</p>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
    </div>

    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addStudentModal')">&times;</span>
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
        document.querySelectorAll('.sidebar button').forEach(button => {
            button.onclick = function() {
                if (this.id === 'searchBtn') {
                    openModal('searchModal');
                } else if (this.id === 'logoutBtn') {
                    openModal('logoutModal');
                } else {
                    loadContent(this.id.replace('Btn', 'Content'));
                }
            }
        });
        function loadContent(contentId) {
    let content = '';
    switch(contentId) {
        case 'homeContent': content = `
            <div class="home-left">
                <h3>Student Registered: <span id="totalUsers"></span></h3>
                <h3>Current Sit-in: <span id="currentSitIn"></span></h3>
                <h3>Total Sit-in: <span id="totalSitIn"></span></h3>
            </div>
            <div class="home-right">
                <h3>Announcement</h3>
                <form id="announcementForm">
                    <textarea name="announcement" rows="4" cols="50" required></textarea>
                    <button type="submit">Submit</button>
                </form>
                <h3>Posted Announcements</h3>
                <div class="announcement-list" id="announcementList">
                </div>
            </div>
        `; break;
        case 'studentContent': content = `
            <div class="student-header">
                <h2>Student List</h2>
                <input type="text" id="studentSearch" placeholder="Search Students">
                <button onclick="openModal('addStudentModal')">Add Student</button>
                <button onclick="resetSessions()">Reset Sessions</button>
            </div>
            <div class="student-list">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Sessions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                    </tbody>
                </table>
            </div>
        `; break;
        case 'sitinContent': content = `
            <h2>CURRENT SIT IN</h2>
            <div class="sitin-header">
                <div class="sitin-options">
                    <label for="entriesPerPage">Show 
                        <select id="entriesPerPage" onchange="loadSitInData()">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                        </select>
                    entries</label>
                </div>
                <div class="sitin-search">
                    <input type="text" id="sitinSearch" placeholder="Search...">
                    <button onclick="loadSitInData()">Search</button>
                </div>
            </div>
            <div class="sitin-list">
                <table id="sitinTable">
                    <thead>
                        <tr>
                            <th>Sit-in ID</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Sit-in Lab</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="sitinTableBody">
                    </tbody>
                </table>
            </div>
            <div class="sitin-pagination">
                <button onclick="goToFirstPage()">&lt;&lt;</button>
                <button onclick="goToPreviousPage()">&lt;</button>
                <span id="currentPage">1</span>
                <button onclick="goToNextPage()">&gt;</button>
                <button onclick="goToLastPage()">&gt;&gt;</button>
            </div>
        `; break;
        case 'viewSitInContent': content = '<p>View current sit-in goes here...</p>'; break;
        case 'sitInReportContent': content = '<p>Sit-in reports go here...</p>'; break;
        case 'feedbackReservationContent': content = '<p>View feedback/reports and reservations goes here...</p>'; break;
        default: content = '<p>Content not found.</p>';
    }
    document.getElementById('dynamicContent').innerHTML = content;

    if (contentId === 'homeContent') { 
        loadHomeData(); 
    }
    if (contentId === 'studentContent') { loadStudentData(); }

    if (contentId === 'homeContent') {
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            var formData = new FormData(this);

            fetch('update_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Announcement posted successfully.');
                    loadHomeData(); // Reload announcements
                } else {
                    alert('Error posting announcement: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error);
            });
        });
    }
}

function loadHomeData() {
    document.getElementById('totalUsers').innerText = '<?php echo count($allStudents); ?>';
    document.getElementById('currentSitIn').innerText = '<?php echo count($currentSitInStudents); ?>';
    document.getElementById('totalSitIn').innerText = '50'; // Replace with actual data

    var announcementList = document.getElementById('announcementList');
    announcementList.innerHTML = ''; // Clear existing content

    fetch('get_announcements.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                data.forEach(announcement => {
                    let announcementItem = document.createElement('div');
                    announcementItem.className = 'announcement-item';
                    announcementItem.innerHTML = `<p>${announcement.announcement_text}</p><p>${announcement.date_posted}</p>`;
                    announcementList.appendChild(announcementItem);
                });
            } else {
                announcementList.innerHTML = '<p>No announcements found.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching announcements:', error);
            announcementList.innerHTML = '<p>Error loading announcements.</p>';
        });
}

// Assume loadStudentData(), openModal(), resetSessions(), loadSitInData(), goToFirstPage(),
// goToPreviousPage(), goToNextPage(), goToLastPage() are defined elsewhere in your code.
function loadHomeData() {
    document.getElementById('totalUsers').innerText = '<?php echo count($allStudents); ?>';
    document.getElementById('currentSitIn').innerText = '<?php echo count($currentSitInStudents); ?>';
    document.getElementById('totalSitIn').innerText = '50'; // Replace with actual data

    var announcementList = document.getElementById('announcementList');
    announcementList.innerHTML = ''; // Clear existing content

    fetch('get_announcements.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                data.forEach(announcement => {
                    let announcementItem = document.createElement('div');
                    announcementItem.className = 'announcement-item';
                    announcementItem.innerHTML = `<p>${announcement.announcement_text}</p><p>${announcement.date_posted}</p>`;
                    announcementList.appendChild(announcementItem);
                });
            } else {
                announcementList.innerHTML = '<p>No announcements found.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching announcements:', error);
            announcementList.innerHTML = '<p>Error loading announcements.</p>';
        });
}

// Assume loadStudentData(), openModal(), resetSessions(), loadSitInData(), goToFirstPage(),
// goToPreviousPage(), goToNextPage(), goToLastPage() are defined elsewhere in your code.

        function openModal(modalId) { document.getElementById(modalId).style.display = "block"; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = "none"; }
        function searchStudent() {
            var idNo = document.getElementById('searchIdNo').value;

            // Use AJAX to fetch student data
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_student_data.php?id=' + idNo, true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var student = JSON.parse(xhr.responseText);
                    if (student) {
                        document.getElementById('studentName').innerText = student.first_name + ' ' + student.last_name;
                        document.getElementById('studentIdNo').innerText = student.id_number;
                        document.getElementById('remainingSessions').innerText = student.sessions;
                        closeModal('searchModal');
                        openModal('studentInfoModal');
                    } else {
                        alert('Student not found.');
                    }
                } else {
                    alert('Request failed.  Returned status of ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                alert('Request failed.');
            };
            xhr.send();
        }
        function addSitIn() {
            var idNo = document.getElementById('studentIdNo').innerText;
            var purpose = document.getElementById('purpose').value;
            var lab = document.getElementById('lab').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_sitin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    closeModal('studentInfoModal');
                    loadContent('sitinContent');
                    initSitInContent();
                } else {
                    alert('Request failed.  Returned status of ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                alert('Request failed.');
            };
            xhr.send('id_number=' + idNo + '&purpose=' + purpose + '&lab=' + lab);
        }
        function loadStudentData() {
            var studentList = document.getElementById('studentTableBody');
            studentList.innerHTML = ''; // Clear existing data
            <?php foreach ($allStudents as $student): ?>
            studentList.innerHTML += `
                <tr class="student-item">
                    <td><?php echo $student['id_number']; ?></td>
                    <td><?php echo $student['last_name'] . ', ' . $student['first_name']; ?></td>
                    <td><?php echo $student['course']; ?></td>
                    <td><?php echo $student['year_level']; ?></td>
                    <td><?php echo $student['sessions']; ?></td>
                </tr>
            `;
            <?php endforeach; ?>
        }
        function addStudent() { /* ... */ }
        function resetSessions() { /* ... */ }

        loadContent('homeContent');
    </script>
    <script>
        let currentPage = 1;
        let entriesPerPage = 5;
        let sitinData = [];

        function loadSitInData() {
            entriesPerPage = document.getElementById('entriesPerPage').value;
            let searchTerm = document.getElementById('sitinSearch').value.toLowerCase();

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_current_sitin_data.php', true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    sitinData = JSON.parse(xhr.responseText);
                    displaySitInData(currentPage, entriesPerPage, searchTerm);
                } else {
                    alert('Request failed.  Returned status of ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                alert('Request failed.');
            };
            xhr.send();
        }

        function displaySitInData(page, entries, searchTerm = '') {
            const sitinTableBody = document.getElementById('sitinTableBody');
            sitinTableBody.innerHTML = ''; // Clear existing data
            const startIndex = (page - 1) * entries;
            const endIndex = startIndex + parseInt(entries);

            let filteredData = sitinData.filter(item =>
                item.id_number.toLowerCase().includes(searchTerm) ||
                item.first_name.toLowerCase().includes(searchTerm) ||
                item.last_name.toLowerCase().includes(searchTerm) ||
                item.purpose.toLowerCase().includes(searchTerm) ||
                item.lab.toLowerCase().includes(searchTerm)
            );

            for (let i = startIndex; i < endIndex && i < filteredData.length; i++) {
                const sitin = filteredData[i];
                const row = `
                    <tr>
                        <td>${sitin.sitin_id}</td>
                        <td>${sitin.id_number}</td>
                        <td>${sitin.first_name} ${sitin.last_name}</td>
                        <td>${sitin.purpose}</td>
                        <td>${sitin.lab}</td>
                        <td>${sitin.sessions}</td>
                        <td>${sitin.status}</td>
                        <td><button onclick="logoutSitIn('${sitin.id_number}')">Logout</button></td>
                    </tr>
                `;
                sitinTableBody.innerHTML += row;
            }

            document.getElementById('currentPage').innerText = page;
        }

        function logoutSitIn(idNo) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'logout_sitin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // After successful logout, decrement the session
                    var xhr2 = new XMLHttpRequest();
                    xhr2.open('POST', 'decrement_session.php', true);
                    xhr2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr2.onload = function() {
                        if (xhr2.status >= 200 && xhr2.status < 300) {
                            loadSitInData(); // Reload data after session decrement
                        } else {
                            alert('Request failed.  Returned status of ' + xhr2.status);
                        }
                    };
                    xhr2.onerror = function() {
                        alert('Request failed.');
                    };
                    xhr2.send('id_number=' + idNo);

                    loadSitInData(); // Reload data after logout
                } else {
                    alert('Request failed.  Returned status of ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                alert('Request failed.');
            };
            xhr.send('id_number=' + idNo);
        }

        function goToFirstPage() {
            currentPage = 1;
            displaySitInData(currentPage, entriesPerPage);
        }

        function goToPreviousPage() {
            if (currentPage > 1) {
                currentPage--;
                displaySitInData(currentPage, entriesPerPage);
            }
        }

        function goToNextPage() {
            const totalPages = Math.ceil(sitinData.length / entriesPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                displaySitInData(currentPage, entriesPerPage);
            }
        }

        function goToLastPage() {
            const totalPages = Math.ceil(sitinData.length / entriesPerPage);
            currentPage = totalPages;
            displaySitInData(currentPage, entriesPerPage);
        }

        // Initial load of sit-in data when the page loads
        function initSitInContent() {
            loadSitInData();
        }

        // Call initSitInContent when sitinContent is loaded
        document.querySelectorAll('.sidebar button').forEach(button => {
            button.onclick = function() {
                if (this.id === 'searchBtn') {
                    openModal('searchModal');
                } else if (this.id === 'logoutBtn') {
                    openModal('logoutModal');
                } else {
                    loadContent(this.id.replace('Btn', 'Content'));
                    if (this.id === 'sitinBtn') {
                        initSitInContent();
                    }
                }
            }
        });

        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            var formData = new FormData(this);

            fetch('add_student.php', {
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
                document.getElementById('form-message').innerHTML = '<div class="error">An error occurred: ' + error + '</div>';
            });
        });

        function resetSessions() {
            fetch('reset_sessions.php')
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
<?php if ($conn instanceof mysqli) { mysqli_close($conn); } ?>