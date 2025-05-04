<!-- Reservation Content -->
<div id="reservationContent" style="display: none;">
    <div class="reservation-management">
        <div class="reservation-header">
            <h2>Reservation Requests</h2>
            <div class="filter-controls">
                <select id="reservationStatusFilter" class="dashboard-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
                <select id="reservationLabFilter" class="dashboard-select">
                    <option value="all">All Labs</option>
                    <option value="524">Lab 524</option>
                    <option value="526">Lab 526</option>
                    <option value="528">Lab 528</option>
                    <option value="530">Lab 530</option>
                    <option value="542">Lab 542</option>
                    <option value="544">Lab 544</option>
                    <option value="517">Lab 517</option>
                </select>
                <input type="date" id="reservationDateFilter" class="dashboard-select">
                <button id="filterReservationsBtn" class="dashboard-btn">Filter</button>
                <button id="resetFiltersBtn" class="dashboard-btn">Reset</button>
            </div>
        </div>
        <div class="reservation-stats">
            <div class="stat-box">
                <span class="stat-label">Total Requests</span>
                <span class="stat-value" id="totalRequests">0</span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Pending</span>
                <span class="stat-value" id="pendingRequests">0</span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Approved</span>
                <span class="stat-value" id="approvedRequests">0</span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Denied</span>
                <span class="stat-value" id="deniedRequests">0</span>
            </div>
        </div>
        <div class="reservation-list">
            <div class="table-responsive">
                <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Lab</th>
                            <th>PC No.</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservationTableBody">
                        <!-- Reservation data will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
            <div class="pagination-controls">
                <div class="entries-per-page">
                    <span>Show</span>
                    <select id="entriesPerPage" class="dashboard-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="pagination-info" id="paginationInfo">
                    Showing 0 to 0 of 0 entries
                </div>
                <div class="pagination-buttons">
                    <button id="prevPage" class="dashboard-btn" disabled>Previous</button>
                    <button id="nextPage" class="dashboard-btn" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.reservation-management {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}
.reservation-header {
    margin-bottom: 20px;
}
.reservation-header h2 {
    color: #fff;
    margin-bottom: 15px;
}
.reservation-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}
.stat-box {
    background: var(--background);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.stat-label {
    display: block;
    font-size: 14px;
    color: var(--light);
    margin-bottom: 5px;
}
.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}
.table-responsive {
    overflow-x: auto;
    margin-bottom: 20px;
}
.reservation-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--background);
    border-radius: 8px;
    overflow: hidden;
}
.reservation-table th,
.reservation-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    color: var(--light);
}
.reservation-table th {
    background: var(--primary);
    color: #fff;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}
.reservation-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-pending {
    background: #ffc107;
    color: #000;
}
.status-approved {
    background: #28a745;
    color: #fff;
}
.status-denied {
    background: #dc3545;
    color: #fff;
}
.action-buttons {
    display: flex;
    gap: 5px;
}
.action-button {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.approve-btn {
    background: #28a745;
    color: #fff;
}
.deny-btn {
    background: #dc3545;
    color: #fff;
}
.action-button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
.pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: var(--background);
    border-radius: 8px;
    margin-top: 20px;
}
.entries-per-page {
    display: flex;
    align-items: center;
    gap: 10px;
}
.pagination-info {
    color: var(--light);
}
.pagination-buttons {
    display: flex;
    gap: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let entriesPerPage = 10;
    let totalEntries = 0;
    let allReservations = [];
    let filteredReservations = [];

    const statusFilter = document.getElementById('reservationStatusFilter');
    const labFilter = document.getElementById('reservationLabFilter');
    const dateFilter = document.getElementById('reservationDateFilter');
    const filterBtn = document.getElementById('filterReservationsBtn');
    const resetBtn = document.getElementById('resetFiltersBtn');
    const entriesSelect = document.getElementById('entriesPerPage');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const paginationInfo = document.getElementById('paginationInfo');
    const tableBody = document.getElementById('reservationTableBody');

    function loadReservations() {
        fetch('get_reservation_requests.php')
            .then(response => response.json())
            .then(data => {
                allReservations = data;
                applyFilters();
                updateStats();
                updateTable();
            })
            .catch(error => {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td colspan="10">Error loading reservations</td></tr>';
            });
    }

    function applyFilters() {
        filteredReservations = allReservations.filter(reservation => {
            const statusMatch = statusFilter.value === 'all' || reservation.status === statusFilter.value;
            const labMatch = labFilter.value === 'all' || reservation.lab === labFilter.value;
            const dateMatch = !dateFilter.value || reservation.date === dateFilter.value;
            return statusMatch && labMatch && dateMatch;
        });
        currentPage = 1;
        totalEntries = filteredReservations.length;
        updatePagination();
    }

    function updateStats() {
        const total = allReservations.length;
        const pending = allReservations.filter(r => r.status === 'pending').length;
        const approved = allReservations.filter(r => r.status === 'approved').length;
        const denied = allReservations.filter(r => r.status === 'denied').length;

        document.getElementById('totalRequests').textContent = total;
        document.getElementById('pendingRequests').textContent = pending;
        document.getElementById('approvedRequests').textContent = approved;
        document.getElementById('deniedRequests').textContent = denied;
    }

    function updateTable() {
        const start = (currentPage - 1) * entriesPerPage;
        const end = start + entriesPerPage;
        const currentReservations = filteredReservations.slice(start, end);

        tableBody.innerHTML = '';
        currentReservations.forEach(reservation => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${reservation.id}</td>
                <td>${reservation.student_id}</td>
                <td>${reservation.student_name}</td>
                <td>${reservation.date}</td>
                <td>${reservation.time}</td>
                <td>Lab ${reservation.lab}</td>
                <td>PC ${reservation.pc_number}</td>
                <td>${reservation.purpose}</td>
                <td><span class="status-badge status-${reservation.status}">${reservation.status}</span></td>
                <td class="action-buttons">
                    ${reservation.status === 'pending' ? `
                        <button class="action-button approve-btn" onclick="handleReservation(${reservation.id}, 'approve')">Approve</button>
                        <button class="action-button deny-btn" onclick="handleReservation(${reservation.id}, 'deny')">Deny</button>
                    ` : '-'}
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalEntries / entriesPerPage);
        const start = (currentPage - 1) * entriesPerPage + 1;
        const end = Math.min(start + entriesPerPage - 1, totalEntries);

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalEntries === 0;
        paginationInfo.textContent = `Showing ${start} to ${end} of ${totalEntries} entries`;
    }

    function handleReservation(id, action) {
        fetch('handle_reservation_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadReservations();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    }

    // Event Listeners
    filterBtn.addEventListener('click', applyFilters);
    resetBtn.addEventListener('click', function() {
        statusFilter.value = 'all';
        labFilter.value = 'all';
        dateFilter.value = '';
        applyFilters();
    });
    entriesSelect.addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updatePagination();
        updateTable();
    });
    prevPageBtn.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
            updateTable();
        }
    });
    nextPageBtn.addEventListener('click', function() {
        if (currentPage < Math.ceil(totalEntries / entriesPerPage)) {
            currentPage++;
            updatePagination();
            updateTable();
        }
    });

    // Initial load
    loadReservations();
    setInterval(loadReservations, 30000); // Refresh every 30 seconds
});
</script>
