<?php
session_start();
include("../includes/database.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../user/index.php");
    exit();
}
?>

<div class="computer-control-container">
    <h2>Computer Control</h2>
    
    <div class="computer-control-grid">
        <div class="lab-list">
            <h3>Laboratories</h3>
            <div class="lab-checkboxes">
                <?php
                $labs = ['524', '526', '528', '530', '542', '544', '517'];
                foreach ($labs as $lab) {
                    echo "<div class='lab-checkbox'>";
                    echo "<input type='checkbox' id='lab{$lab}' name='labs[]' value='{$lab}' checked>";
                    echo "<label for='lab{$lab}'>Lab {$lab}</label>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        
        <div class="computer-status">
            <h3>Computer Status</h3>
            <div class="computer-grid">
                <!-- Computer status will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<style>
.computer-control-container {
    padding: 20px;
}

.computer-control-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
}

.lab-list {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}

.lab-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.lab-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.computer-status {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}

.computer-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

.computer-status-item {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-align: center;
    background-color: #fff;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labCheckboxes = document.querySelectorAll('input[name="labs[]"]');
    const computerGrid = document.querySelector('.computer-grid');
    
    function updateComputerStatus() {
        const selectedLabs = Array.from(labCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
            
        if (selectedLabs.length === 0) {
            computerGrid.innerHTML = '<p>Please select at least one laboratory</p>';
            return;
        }
        
        // Make AJAX call to get computer status
        fetch('get_computer_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'labs=' + JSON.stringify(selectedLabs)
        })
        .then(response => response.json())
        .then(data => {
            computerGrid.innerHTML = '';
            data.forEach(computer => {
                const computerItem = document.createElement('div');
                computerItem.className = `computer-status-item ${computer.status}`;
                computerItem.innerHTML = `
                    <div>${computer.lab} - PC ${computer.number}</div>
                    <div>${computer.status === 'in-use' ? 'In Use' : 
                          computer.status === 'available' ? 'Available' : 'Disabled'}</div>
                `;
                computerItem.addEventListener('click', function() {
                    toggleComputerStatus(computer.lab, computer.number);
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
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
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
    
    // Add event listeners to lab checkboxes
    labCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateComputerStatus);
    });
    
    // Initial load
    updateComputerStatus();
});
</script> 