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
    <div class="computer-grid"><p>Please select a laboratory</p></div>
</div>

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
    color: #fff;
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
.computer-status-item.in-use {
    background-color: #dc3545;
    color: white;
}
.computer-status-item.disabled {
    background-color: #6c757d;
    color: white;
    opacity: 0.7;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labSelect = document.getElementById('labSelect');
    const computerGrid = document.querySelector('.computer-grid');
    function updateComputerStatus() {
        const selectedLab = labSelect.value;
        if (!selectedLab) {
            computerGrid.innerHTML = '<p>Please select a laboratory</p>';
            return;
        }
        fetch('get_computer_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
            body: 'lab=' + selectedLab
        })
        .then(response => response.json())
        .then(data => {
            computerGrid.innerHTML = '';
            data.forEach(computer => {
                const computerItem = document.createElement('div');
                computerItem.className = `computer-status-item ${computer.status}`;
                computerItem.innerHTML = `
                    <div class="computer-number">PC ${computer.number}</div>
                    <div class="status-badge">
                        ${computer.status === 'in-use' ? 'In Use' : computer.status === 'available' ? 'Available' : 'Disabled'}
                    </div>
                `;
                computerItem.addEventListener('click', function() {
                    toggleComputerStatus(selectedLab, computer.number);
                });
                computerGrid.appendChild(computerItem);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            computerGrid.innerHTML = '<p>Error loading computer status</p>';
        });
    }
    function toggleComputerStatus(lab, computerNumber) {
        fetch('toggle_computer_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
            body: `lab=${lab}&computer_number=${computerNumber}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateComputerStatus();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error toggling computer status');
        });
    }
    labSelect.addEventListener('change', updateComputerStatus);
});
</script> 