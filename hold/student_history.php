<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student History</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        :root {
            --light: hsl(220, 50%, 90%);
            --primary: hsl(255, 30%, 55%);
            --focus: hsl(210, 90%, 50%);
            --border-color: hsla(0, 0%, 100%, .2);
            --global-background: hsl(220, 25%, 10%);
            --background: linear-gradient(to right, hsl(210, 30%, 20%), hsl(255, 30%, 25%));
            --shadow-1: hsla(236, 50%, 50%, .3);
            --shadow-2: hsla(236, 50%, 50%, .4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Open Sans', sans-serif;
            color: var(--light);
            background: var(--global-background);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .history-filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .entries-dropdown {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .entries-dropdown select {
            width: 60px;
            height: 30px;
            padding: 0 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
            font-size: 14px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
        }

        .history-search input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
            font-size: 14px;
            width: 200px;
        }

        .history-search button {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .history-search button:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        .history-table-container {
            margin-top: 20px;
            overflow-x: auto;
            background: var(--background);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            background: var(--primary);
            color: var(--light);
            font-weight: 600;
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .history-pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            align-items: center;
        }

        .history-pagination button {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .history-pagination button:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        .history-pagination span {
            padding: 8px 16px;
            color: var(--light);
        }

        /* Feedback Modal Styles */
        .modal-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal {
            background: var(--background);
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-button {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--background);
            color: var(--light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-button:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="history-header">
            <h2>Student History</h2>
            <div class="history-filters">
                <div class="entries-dropdown">
                    Show <select id="entriesPerPage" onchange="loadHistoryData()">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                    </select> entries
                </div>
                <div class="history-search">
                    <input type="text" id="searchInput" placeholder="Search...">
                    <button onclick="loadHistoryData()">Search</button>
                </div>
            </div>
        </div>

        <div class="history-table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Sit-in Purpose</th>
                        <th>Lab</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody"></tbody>
            </table>
        </div>

        <div class="history-pagination">
            <button onclick="goToFirstPage()"><<</button>
            <button onclick="goToPreviousPage()"><</button>
            <span id="currentPage">1</span>
            <button onclick="goToNextPage()">></button>
            <button onclick="goToLastPage()">>></button>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal-container">
        <div class="modal">
            <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
            <h2>Add Feedback</h2>
            <div class="student-info">
                <p><b>Student:</b> <span id="feedbackStudentName"></span></p>
                <p><b>ID Number:</b> <span id="feedbackStudentId"></span></p>
            </div>
            <form id="feedbackForm" onsubmit="submitFeedback(event)">
                <div class="form-group">
                    <label for="feedbackType">Feedback Type:</label>
                    <select id="feedbackType" required>
                        <option value="positive">Positive</option>
                        <option value="negative">Negative</option>
                        <option value="neutral">Neutral</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="feedbackText">Feedback:</label>
                    <textarea id="feedbackText" required></textarea>
                </div>
                <div class="button-group">
                    <button type="submit" class="modal-button">Submit</button>
                    <button type="button" class="modal-button" onclick="closeModal('feedbackModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentStudentId = '';

        function loadHistoryData() {
            const entriesPerPage = document.getElementById('entriesPerPage').value;
            const searchTerm = document.getElementById('searchInput').value;

            fetch(`get_student_history.php?entriesPerPage=${entriesPerPage}&page=${currentPage}&term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    displayHistoryData(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function displayHistoryData(data) {
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = '';

            data.forEach(record => {
                const row = `
                    <tr>
                        <td>${record.id_number}</td>
                        <td>${record.student_name}</td>
                        <td>${record.purpose}</td>
                        <td>${record.lab}</td>
                        <td>${record.login_time}</td>
                        <td>${record.logout_time}</td>
                        <td>${record.date}</td>
                        <td><button onclick="openFeedbackModal('${record.id_number}', '${record.student_name}')">Add Feedback</button></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            document.getElementById('currentPage').innerText = currentPage;
            totalPages = data[0]?.totalPages || 1;
        }

        function goToFirstPage() {
            currentPage = 1;
            loadHistoryData();
        }

        function goToPreviousPage() {
            if (currentPage > 1) {
                currentPage--;
                loadHistoryData();
            }
        }

        function goToNextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                loadHistoryData();
            }
        }

        function goToLastPage() {
            currentPage = totalPages;
            loadHistoryData();
        }

        function openFeedbackModal(studentId, studentName) {
            currentStudentId = studentId;
            document.getElementById('feedbackStudentName').textContent = studentName;
            document.getElementById('feedbackStudentId').textContent = studentId;
            document.getElementById('feedbackModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function submitFeedback(event) {
            event.preventDefault();
            const feedbackType = document.getElementById('feedbackType').value;
            const feedbackText = document.getElementById('feedbackText').value;

            const formData = new FormData();
            formData.append('student_id', currentStudentId);
            formData.append('feedback_type', feedbackType);
            formData.append('feedback_text', feedbackText);

            fetch('add_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Feedback submitted successfully');
                    closeModal('feedbackModal');
                    document.getElementById('feedbackForm').reset();
                } else {
                    alert('Error submitting feedback: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback');
            });
        }

        // Initial load
        loadHistoryData();

        // Add event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                loadHistoryData();
            }
        });
    </script>
</body>
</html> 