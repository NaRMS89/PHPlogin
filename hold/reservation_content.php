<!-- Reservation Content -->
<div id="reservationContent" style="display: none;">
    <h2>Reservation Requests</h2>
    <div class="reservation-management">
        <div class="reservation-header">
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
            </div>
        </div>
        <div class="reservation-list">
            <table>
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
    </div>
</div>
