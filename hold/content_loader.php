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
                    <form id="announcementForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
    if (contentId === 'homeContent') { loadHomeData(); }
    if (contentId === 'studentContent') { loadStudentData(); }
}

function loadHomeData() {
    document.getElementById('totalUsers').innerText = '<?php echo count($allStudents); ?>';
    document.getElementById('currentSitIn').innerText = '<?php echo count($currentSitInStudents); ?>';
    document.getElementById('totalSitIn').innerText = '50'; // Replace with actual data

    var announcementList = document.getElementById('announcementList');
    announcementList.innerHTML = '';

    fetch('get_announcements.php')
    .then(response => response.json())
    .then(data => {
        data.forEach(announcement => {
            announcementList.innerHTML += `
                <div class="announcement-item">
                    <p>ID: ${announcement.announcement_id}</p>
                    <p>${announcement.announcement_text}</p>
                    <p>Date Posted: ${announcement.date_posted}</p>
                </div>
            `;
        });
        announcementList.style.maxHeight = '200px'; // Set max height for scrolling
        announcementList.style.overflowY = 'scroll'; // Enable vertical scrolling
    })
    .catch(error => {
        console.error('Error:', error);
        announcementList.innerHTML = '<p>Error loading announcements.</p>';
    });
}

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

function postAnnouncement() {
    var form = document.getElementById('announcementForm');
    var formData = new FormData(form);

    fetch('ADMIN/post_announcement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadHomeData(); // Reload announcements after posting
        } else {
            alert('Error posting announcement: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error posting announcement.');
    });
}

document.getElementById('announcementForm').onsubmit = function(event) {
    event.preventDefault();
    postAnnouncement();
};
</script>
