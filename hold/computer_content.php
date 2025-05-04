<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}
?>

<div class="computer-control-container">
    <div class="lab-select-container">
        <h3>Select Laboratory</h3>
        <select id="labSelect" class="form-control">
            <option value="">Select a Laboratory</option>
            <option value="524">Lab 524</option>
            <option value="526">Lab 526</option>
            <option value="528">Lab 528</option>
            <option value="530">Lab 530</option>
            <option value="542">Lab 542</option>
            <option value="544">Lab 544</option>
            <option value="517">Lab 517</option>
        </select>
    </div>
    
    <div class="computer-grid" id="computerGrid">
        <!-- Computer status will be loaded dynamically -->
    </div>

    <div class="reservation-controls">
        <input type="text" id="studentName" class="form-control" placeholder="Enter student name">
        <button id="reserveBtn" class="btn btn-primary" disabled>Reserve PC</button>
        <div id="message" class="message"></div>
    </div>
</div>

<script>
const labSelect = document.getElementById('labSelect');
const computerGrid = document.getElementById('computerGrid');
let selectedPcNumber = null;
let labsData = {};

function initializeLabsData() {
    // Initialize empty data structure for each lab
    const labs = ['524', '526', '528', '530', '542', '544', '517'];
    labs.forEach(lab => {
        labsData[lab] = Array(50).fill().map((_, i) => ({
            pcNumber: i + 1,
            status: 'available',
            reservation: null
        }));
    });
}

function getLabsData() {
    return labsData;
}

function setLabsData(data) {
    labsData = data;
}

function loadComputersForLab(labID) {
    fetch(`get_computer_status.php?lab=${labID}`)
        .then(res => res.json())
        .then(pcs => {
            computerGrid.innerHTML = '';
            for (let i = 1; i <= 50; i++) {
                const pc = pcs.find(p => parseInt(p.computer_number) === i);
                let status = pc ? pc.status : 'available';
                const card = document.createElement('div');
                card.className = `computer-card ${status}`;
                card.innerHTML = `
                    <div>PC ${i}</div>
                    <button class="btn-toggle ${status === 'available' ? 'activate' : 'deactivate'}"
                        ${status === 'reserved' ? 'disabled' : ''}
                        onclick="togglePCStatus('${labID}', ${i}, '${status === 'available' ? 'maintenance' : 'available'}')">
                        ${status === 'available' ? 'Deactivate' : 'Activate'}
                    </button>
                `;
                if (status === 'available') {
                    card.onclick = () => selectPC(i);
                } else {
                    card.onclick = null;
                }
                if (selectedPcNumber === i) card.classList.add('selected');
                computerGrid.appendChild(card);
            }
        });
}

function togglePCStatus(labID, pcNumber, newStatus) {
    fetch('toggle_computer_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `lab=${labID}&pc_number=${pcNumber}&status=${newStatus}`
    })
    .then(res => res.json())
    .then(() => loadComputersForLab(labID));
}

function selectPC(pcNumber) {
    selectedPcNumber = pcNumber;
    Array.from(computerGrid.children).forEach((div, idx) => {
        if (idx === pcNumber - 1) div.classList.add('selected');
        else div.classList.remove('selected');
    });
    toggleReserveButton();
}

function toggleReserveButton() {
    const nameField = document.getElementById('studentName');
    const reserveBtn = document.getElementById('reserveBtn');
    reserveBtn.disabled = !(labSelect.value && selectedPcNumber && nameField.value.trim().length > 0);
}

function acceptReservation(labID, pcNumber) {
    const data = getLabsData();
    const pc = data[labID].find(pc => pc.pcNumber === pcNumber);
    if(!pc || !pc.reservation) return;
    pc.reservation.status = 'accepted';
    pc.status = 'occupied';
    setLabsData(data);
    updateReservationAdminList();
    if(selectedLab === labID) {
        loadComputersForLab(labID);
    }
}

function rejectReservation(labID, pcNumber) {
    const data = getLabsData();
    const pc = data[labID].find(pc => pc.pcNumber === pcNumber);
    if(!pc || !pc.reservation) return;
    pc.reservation = null;
    setLabsData(data);
    updateReservationAdminList();
    if(selectedLab === labID) {
        loadComputersForLab(labID);
    }
}

// Event Listeners
labSelect.addEventListener('change', function() {
    const lab = this.value;
    if (!lab) {
        computerGrid.innerHTML = '';
        return;
    }
    loadComputersForLab(lab);
});

document.getElementById('studentName').addEventListener('input', () => {
    toggleReserveButton();
    document.getElementById('message').textContent = '';
});

document.getElementById('reserveBtn').addEventListener('click', function() {
    const labID = labSelect.value;
    const pcNumber = selectedPcNumber;
    const studentName = document.getElementById('studentName').value.trim();
    if (!labID || !pcNumber || !studentName) return;
    fetch('reserve_pc.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `lab=${labID}&pc_number=${pcNumber}&student_name=${encodeURIComponent(studentName)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('message').textContent = 'Reservation request sent!';
            loadComputersForLab(labID);
        } else {
            document.getElementById('message').textContent = data.error || 'Reservation failed.';
        }
    });
});

// Initialize on load
initializeLabsData();
</script>

<style>
.computer-control-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.lab-select-container {
    margin-bottom: 20px;
    text-align: center;
}

.lab-select-container h3 {
    margin-bottom: 10px;
    color: #333;
}

.form-control {
    width: 200px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.computer-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-top: 20px;
}

.computer-status-item {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-align: center;
    background-color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.computer-status-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.computer-status-item.available {
    background-color: #28a745;
    color: white;
}

.computer-status-item.disabled {
    background-color: #dc3545;
    color: white;
}

.computer-status-item.in-use {
    background-color: #ffc107;
    color: #000;
}

.computer-number {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
}

.status-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    background-color: rgba(255,255,255,0.2);
}

.reservation-controls {
    margin-top: 20px;
    text-align: center;
}

.reservation-controls .form-control {
    width: 300px;
    margin-right: 10px;
}

.message {
    margin-top: 10px;
    color: #28a745;
    font-weight: bold;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}
</style> 