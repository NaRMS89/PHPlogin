// Reservation Management for Admin Dashboard

// Load reservation data when the reservation button is clicked
document.getElementById('reservationBtn').addEventListener('click', function() {
    loadReservations();
});

// Filter reservations
document.getElementById('filterReservationsBtn').addEventListener('click', function() {
    loadReservations();
});

// Load all reservations with optional filters
function loadReservations() {
    const status = document.getElementById('reservationStatusFilter').value;
    const lab = document.getElementById('reservationLabFilter').value;
    const date = document.getElementById('reservationDateFilter').value;
    
    fetch(`get_reservations.php?status=${status}&lab=${lab}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('reservationTableBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center">No reservation requests found</td></tr>`;
                return;
            }
            
            data.forEach(reservation => {
                const row = document.createElement('tr');
                
                // Highlight based on status
                if (reservation.status === 'pending') {
                    row.classList.add('pending-row');
                } else if (reservation.status === 'approved') {
                    row.classList.add('approved-row');
                } else if (reservation.status === 'denied') {
                    row.classList.add('denied-row');
                }
                
                row.innerHTML = `
                    <td>${reservation.id}</td>
                    <td>${reservation.id_number}</td>
                    <td>${reservation.first_name} ${reservation.last_name}</td>
                    <td>${formatDate(reservation.reservation_date)}</td>
                    <td>${formatTime(reservation.start_time)} - ${formatTime(reservation.end_time)}</td>
                    <td>Lab ${reservation.lab_id}</td>
                    <td>PC ${reservation.computer_number}</td>
                    <td>${reservation.purpose}</td>
                    <td><span class="status-badge status-${reservation.status}">${capitalizeFirstLetter(reservation.status)}</span></td>
                    <td class="action-buttons">
                        ${reservation.status === 'pending' ? 
                            `<button class="approve-btn" onclick="updateReservationStatus(${reservation.id}, 'approved')">Approve</button>
                             <button class="deny-btn" onclick="updateReservationStatus(${reservation.id}, 'denied')">Deny</button>` : 
                            `<button class="status-btn" disabled>${capitalizeFirstLetter(reservation.status)}</button>`
                        }
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching reservations:', error);
            document.getElementById('reservationTableBody').innerHTML = 
                `<tr><td colspan="10" class="text-center">Error loading reservations. Please try again.</td></tr>`;
        });
}

// Update reservation status (approve/deny)
function updateReservationStatus(reservationId, status) {
    const formData = new FormData();
    formData.append('update_reservation_status', true);
    formData.append('reservation_id', reservationId);
    formData.append('status', status);
    
    fetch('admin_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Reservation ${status === 'approved' ? 'approved' : 'denied'} successfully`);
            loadReservations(); // Reload the table
        } else {
            showNotification(`Failed to update reservation: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating reservation status:', error);
        showNotification('Error updating reservation. Please try again.', 'error');
    });
}

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    // Convert "HH:MM:SS" to "HH:MM AM/PM"
    const [hours, minutes] = timeString.split(':');
    const hoursNum = parseInt(hours);
    const period = hoursNum >= 12 ? 'PM' : 'AM';
    const hours12 = hoursNum % 12 || 12; // Convert 0 to 12 for 12 AM
    
    return `${hours12}:${minutes} ${period}`;
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function showNotification(message, type = 'success') {
    // This function can be customized based on your notification system
    alert(message);
}

// --- Timeout Modal Functions ---

// Store the ID number of the student being timed out
let currentTimeoutIdNumber = null;

function openTimeoutModal(idNumber, firstName, lastName, sessions, points, totalPoints) {
    const modal = document.getElementById('timeoutModal');
    if (!modal) {
        console.error('Timeout modal element not found');
        return;
    }

    currentTimeoutIdNumber = idNumber; // Store the ID for actions

    // Populate student info
    document.getElementById('modalIdNumber').textContent = idNumber;
    document.getElementById('modalName').textContent = `${firstName} ${lastName}`;
    document.getElementById('modalSessions').textContent = sessions;
    document.getElementById('modalPoints').textContent = points;
    document.getElementById('modalTotalPoints').textContent = totalPoints;

    // Clear previous messages
    document.getElementById('timeoutModalMessage').textContent = '';
    document.getElementById('timeoutModalMessage').className = 'modal-message'; // Reset class

    // Show the modal
    modal.style.display = 'block'; 
}

function closeTimeoutModal() {
    const modal = document.getElementById('timeoutModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentTimeoutIdNumber = null; // Clear stored ID
}

function givePointAndTimeout() {
    if (!currentTimeoutIdNumber) return;

    const formData = new FormData();
    formData.append('give_point_and_timeout', true);
    formData.append('id_number', currentTimeoutIdNumber);

    fetch('admin_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('timeoutModalMessage');
        messageDiv.textContent = data.message;
        if (data.success) {
            messageDiv.className = 'modal-message success';
            // Optionally, close modal after a delay or refresh relevant UI parts
            // setTimeout(closeTimeoutModal, 2000);
            // refreshStudentList(); // Assuming you have a function to refresh the sit-in list
        } else {
            messageDiv.className = 'modal-message error';
        }
    })
    .catch(error => {
        console.error('Error giving point and timeout:', error);
        const messageDiv = document.getElementById('timeoutModalMessage');
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.className = 'modal-message error';
    });
}

function timeoutOnly() {
    if (!currentTimeoutIdNumber) return;

    const messageDiv = document.getElementById('timeoutModalMessage');
    messageDiv.textContent = 'Timeout Only function needs backend implementation.';
    messageDiv.className = 'modal-message error';
    
    // Placeholder for actual fetch call when backend is ready
    /*
    const formData = new FormData();
    formData.append('timeout_only', true); // You'll need to add this handler in PHP
    formData.append('id_number', currentTimeoutIdNumber);

    fetch('admin_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Handle response similar to givePointAndTimeout
    })
    .catch(error => {
        // Handle error similar to givePointAndTimeout
    });
    */
}

// --- Student Info Modal Functions ---
function selectStudent(id, name, remainingSessions) {
    const idEl = document.getElementById('studentIdNo');
    const nameEl = document.getElementById('studentName');
    const sessionsEl = document.getElementById('remainingSessions');
    if (idEl) idEl.textContent = id;
    if (nameEl) nameEl.textContent = name;
    if (sessionsEl) sessionsEl.textContent = remainingSessions;

    const modal = document.getElementById('studentInfoModal');
    if (modal) modal.style.display = 'flex';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

function addSitIn() {
    const labEl = document.getElementById('lab');
    const purposeEl = document.getElementById('purpose');
    const idEl = document.getElementById('studentIdNo');
    if (!labEl || !purposeEl || !idEl) return;

    const formData = new FormData();
    formData.append('add_sitin', true);
    formData.append('id_number', idEl.textContent);
    formData.append('lab', labEl.value);
    formData.append('purpose', purposeEl.value);

    fetch('admin_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || 'Sit-in request processed.');
        closeModal('studentInfoModal');
        // Optionally reload or refresh content
    })
    .catch(() => alert('Network error.'));
}
