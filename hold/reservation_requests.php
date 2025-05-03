<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}
?>

<div class="reservation-requests-container">
    <h2>Reservation Requests</h2>
    
    <div class="reservation-requests-table">
        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Lab</th>
                    <th>Computer</th>
                    <th>Purpose</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reservationRequestsBody">
                <!-- Reservation requests will be loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<style>
.reservation-requests-container {
    padding: 20px;
}

.reservation-requests-table {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

th {
    background-color: #e9ecef;
    font-weight: bold;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.approve-btn, .deny-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    color: white;
    font-weight: bold;
}

.approve-btn {
    background-color: #28a745;
}

.deny-btn {
    background-color: #dc3545;
}

.approve-btn:hover {
    background-color: #218838;
}

.deny-btn:hover {
    background-color: #c82333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestsBody = document.getElementById('reservationRequestsBody');
    
    function loadReservationRequests() {
        fetch('get_reservation_requests.php')
            .then(response => response.json())
            .then(data => {
                requestsBody.innerHTML = '';
                data.forEach(request => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${request.id_number}</td>
                        <td>${request.name}</td>
                        <td>${request.date}</td>
                        <td>${request.start_time} - ${request.end_time}</td>
                        <td>Lab ${request.lab}</td>
                        <td>PC ${request.computer_number}</td>
                        <td>${request.purpose}</td>
                        <td class="action-buttons">
                            <button class="approve-btn" onclick="handleReservationAction(${request.id}, 'approve')">Approve</button>
                            <button class="deny-btn" onclick="handleReservationAction(${request.id}, 'deny')">Deny</button>
                        </td>
                    `;
                    requestsBody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                requestsBody.innerHTML = '<tr><td colspan="8">Error loading reservation requests</td></tr>';
            });
    }
    
    window.handleReservationAction = function(reservationId, action) {
        fetch('handle_reservation_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `reservation_id=${reservationId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadReservationRequests();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    };
    
    // Initial load
    loadReservationRequests();
    
    // Refresh every 30 seconds
    setInterval(loadReservationRequests, 30000);
});
</script> 