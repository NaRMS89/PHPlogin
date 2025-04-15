<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Base styles */
        .dynamic-content {
            display: none;
            padding: 2rem;
            min-height: 90vh;
        }

        .dynamic-content.active {
            display: block;
        }

        /* Three Column Layout */
        .three-column-layout {
            display: grid;
            grid-template-columns: 25% 45% 30%;
            gap: 2rem;
            max-width: 1800px;
            margin: 0 auto;
            min-height: 800px;
        }

        /* Profile Column */
        .profile-column {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-info {
            text-align: center;
        }

        .profile-info h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .profile-picture-wrapper {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #4CAF50;
        }

        .profile-picture-main {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info p {
            margin: 1rem 0;
            font-size: 1.1rem;
            color: #4a5568;
        }

        .profile-info strong {
            color: #2d3748;
            font-weight: 600;
        }

        .link-1 {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 2rem;
            background: #4CAF50;
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .link-1:hover {
            background: #45a049;
        }

        /* Announcement Column */
        .announcement-column {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .announcement-column h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d3748;
            text-align: center;
        }

        .announcement-list {
            max-height: 700px;
            overflow-y: auto;
            padding: 1rem;
        }

        .no-announcements {
            text-align: center;
            color: #718096;
            font-style: italic;
            padding: 2rem;
        }

        /* Rules Column */
        .rules-column {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .rules-column h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .rules-column h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 1.5rem 0 1rem;
            color: #4CAF50;
        }

        .rules-column ol {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .rules-column li {
            margin-bottom: 1rem;
            color: #4a5568;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .rules-column ul {
            padding-left: 1.5rem;
            margin: 0.5rem 0;
            list-style-type: disc;
        }

        .rules-column p {
            margin: 1rem 0;
            color: #4a5568;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .three-column-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .profile-column, .announcement-column, .rules-column {
                max-width: 800px;
                margin: 0 auto;
            }
        }

        /* Original styles for other components */
        .announcement-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        .announcement-form, .announcement-list {
            flex: 1;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .announcement-form textarea {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
        }
        .announcement-form button {
            background: #4CAF50;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .announcement-form button:hover {
            background: #45a049;
        }
        .announcement-scroll {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* History Table Styles */
        .history-section {
            margin-top: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .history-table th,
        .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .history-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .feedback-btn {
            background: #4CAF50;
            color: white;
            padding: 0.25rem 0.75rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .feedback-btn:hover {
            background: #45a049;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
        }
        .close-modal {
            float: right;
            cursor: pointer;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto">
        <!-- Home Content -->
        <div id="homeContent" class="dynamic-content active">
            <div class="three-column-layout">
                <!-- Left Column - Profile -->
                <aside class="profile-column">
                    <section class="profile-info">
                        <h2>My Profile</h2>
                        <div class="profile-picture-wrapper">
                            <img src="../uploads/pngfind.com-weights-png-1091286.png" alt="Profile Picture" class="profile-picture-main">
                        </div>
                        <p><strong>Name:</strong> Maria Santos</p>
                        <p><strong>Student ID:</strong> 1000</p>
                        <p><strong>Course:</strong> BSBA</p>
                        <p><strong>Year Level:</strong> 2</p>
                        <p><strong>Email:</strong> 1000@gmail.com</p>
                        <p><strong>Sessions Remaining:</strong> 13</p>
                        <a href="javascript:void(0);" class="link-1" onclick="openEditProfileModal()">Edit Profile</a>
                    </section>
                </aside>

                <!-- Center Column - Announcements -->
                <main class="announcement-column">
                    <h2>Announcements</h2>
                    <div id="announcementList" class="announcement-list">
                        <p class="no-announcements">Unable to load announcements. Please try again later.</p>
                    </div>
                </main>

                <!-- Right Column - Rules -->
                <aside class="rules-column">
                    <section id="rulesContent">
                        <h2>Rules</h2>
                        <h3>Laboratory Rules and Regulations</h3>
                        <ol>
                            <li>Maintain silence and discipline. Switch off personal devices.</li>
                            <li>No games allowed.</li>
                            <li>Internet use only with permission. No downloads/installations.</li>
                            <li>No access to unrelated or inappropriate sites.</li>
                            <li>Altering system settings or deleting files is prohibited.</li>
                            <li>Fifteen-minute grace period per use; then seat is forfeited.</li>
                            <li>
                                Proper decorum:
                                <ul>
                                    <li>Enter only with instructor present.</li>
                                    <li>Store bags at the counter.</li>
                                    <li>Follow seating assignments.</li>
                                    <li>Close all software and return chairs after use.</li>
                                </ul>
                            </li>
                            <li>No eating, drinking, gum, smoking, or vandalism.</li>
                            <li>Disturbances and offensive acts will not be tolerated.</li>
                            <li>Hostile behavior will lead to removal or CSU involvement.</li>
                            <li>Report issues to lab staff immediately.</li>
                        </ol>

                        <h3>DISCIPLINARY ACTION</h3>
                        <p><strong>First Offense:</strong> May lead to class suspension via Guidance Center.</p>
                        <p><strong>Further Offenses:</strong> Harsher sanctions to follow.</p>
                    </section>
                </aside>
            </div>
        </div>

        <!-- Rest of your existing content -->
        <!-- History Section -->
        <div class="history-section">
            <h2 class="text-xl font-semibold mb-4">My Sit-in History</h2>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- Table content will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeFeedbackModal()">&times;</span>
            <h2 class="text-xl font-semibold mb-4">Add Feedback</h2>
            <form id="feedbackForm" onsubmit="submitFeedback(event)">
                <input type="hidden" id="sitInId" name="sitInId">
                <div class="mb-4">
                    <label class="block mb-2">Your Feedback:</label>
                    <textarea 
                        id="feedbackText" 
                        name="feedbackText" 
                        rows="4" 
                        class="w-full p-2 border rounded"
                        required
                    ></textarea>
                </div>
                <button type="submit" class="feedback-btn">Submit Feedback</button>
            </form>
        </div>
    </div>

    <script>
        // Your existing JavaScript code
        document.addEventListener('DOMContentLoaded', loadHistoryData);

        function loadHistoryData() {
            fetch('get_user_history.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('historyTableBody');
                    tbody.innerHTML = '';

                    data.forEach(record => {
                        const row = document.createElement('tr');
                        
                        const loginTime = new Date(record.login_time);
                        const logoutTime = record.logout_time ? new Date(record.logout_time) : null;
                        const duration = logoutTime 
                            ? calculateDuration(loginTime, logoutTime)
                            : 'Ongoing';

                        row.innerHTML = `
                            <td>${formatDate(loginTime)}</td>
                            <td>${record.purpose}</td>
                            <td>${record.lab}</td>
                            <td>${formatTime(loginTime)}</td>
                            <td>${logoutTime ? formatTime(logoutTime) : '-'}</td>
                            <td>${duration}</td>
                            <td>${record.feedback || '-'}</td>
                            <td>
                                ${!record.feedback ? 
                                    `<button class="feedback-btn" onclick="openFeedbackModal(${record.id})">
                                        Add Feedback
                                    </button>` : 
                                    ''
                                }
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading history:', error));
        }

        function calculateDuration(start, end) {
            const diff = end - start;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            return `${hours}h ${minutes}m`;
        }

        function formatDate(date) {
            return date.toLocaleDateString();
        }

        function formatTime(date) {
            return date.toLocaleTimeString();
        }

        function openFeedbackModal(sitInId) {
            document.getElementById('sitInId').value = sitInId;
            document.getElementById('feedbackModal').style.display = 'flex';
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
            document.getElementById('feedbackForm').reset();
        }

        function submitFeedback(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            fetch('submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Feedback submitted successfully!');
                    closeFeedbackModal();
                    loadHistoryData();
                } else {
                    alert('Error submitting feedback: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback');
            });
        }
    </script>
</body>
</html>
