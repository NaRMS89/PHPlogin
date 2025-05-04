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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labSelect = document.getElementById('labSelect');
    const computerGrid = document.getElementById('computerGrid');

    function loadComputers(lab) {
        // Clear existing computers
        computerGrid.innerHTML = '';
        
        // Fetch current computer statuses
        fetch('get_computer_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `lab=${lab}`
        })
        .then(response => response.json())
        .then(computers => {
            // Create computer grid
            for (let i = 1; i <= 50; i++) {
                const computer = computers.find(c => c.number === i);
                const status = computer ? computer.status : 'available';
                
                const computerItem = document.createElement('div');
                computerItem.className = `computer-status-item ${status}`;
                computerItem.innerHTML = `
                    <div class="computer-number">PC ${i}</div>
                    <div class="status-badge">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </div>
                `;
                
                // Add click event to toggle status
                computerItem.addEventListener('click', function() {
                    toggleComputerStatus(lab, i, status);
                });
                
                computerGrid.appendChild(computerItem);
            }
        })
        .catch(error => {
            console.error('Error loading computer status:', error);
            alert('Error loading computer status. Please try again.');
        });
    }

    function toggleComputerStatus(lab, computerNumber, currentStatus) {
        fetch('toggle_computer_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `lab=${lab}&computer_number=${computerNumber}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload computers to reflect new status
                loadComputers(lab);
            } else {
                alert('Error updating computer status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error toggling computer status:', error);
            alert('Error updating computer status. Please try again.');
        });
    }

    // Load computers when lab is selected
    labSelect.addEventListener('change', function() {
        if (this.value) {
            loadComputers(this.value);
        } else {
            computerGrid.innerHTML = '';
        }
    });
});
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
</style> 