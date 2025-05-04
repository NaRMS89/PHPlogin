<!-- Reservation Content -->
<div id="reservationContent" class="dynamic-content">
    <div class="reservation-container">
        <h2>Lab Reservation</h2>
        
        <!-- Student Info Display -->
        <div class="student-info-display">
            <p><strong>ID Number:</strong> <?php echo $_SESSION['user_data']['id_number']; ?></p>
            <p><strong>Name:</strong> <?php echo $_SESSION['user_data']['first_name'] . ' ' . $_SESSION['user_data']['last_name']; ?></p>
            <p><strong>Remaining Sessions:</strong> <?php echo $_SESSION['user_data']['sessions']; ?></p>
        </div>
        
        <!-- Reservation form -->
        <div class="reservation-form-container">
            <form id="reservationForm" class="reservation-form">
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <select class="form-control" id="purpose" name="purpose" required>
                        <option value="">Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="Java Programming">Java Programming</option>
                        <option value="Python">Python</option>
                        <option value="C#">C#</option>
                        <option value="Database">Database</option>
                        <option value="Digital Logic & Design">Digital Logic & Design</option>
                        <option value="Embedded Systems and IoT">Embedded Systems and IoT</option>
                        <option value="System Integration and Architecture">System Integration and Architecture</option>
                        <option value="Computer Application">Computer Application</option>
                        <option value="Project Management">Project Management</option>
                        <option value="IT Trends">IT Trends</option>
                        <option value="Technopreneurship">Technopreneurship</option>
                        <option value="Capstone">Capstone</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="labRoom">Lab Room</label>
                    <select class="form-control" id="labRoom" name="lab" required>
                        <option value="">Select Lab Room</option>
                        <option value="524">Lab 524</option>
                        <option value="526">Lab 526</option>
                        <option value="528">Lab 528</option>
                        <option value="530">Lab 530</option>
                        <option value="542">Lab 542</option>
                        <option value="544">Lab 544</option>
                        <option value="517">Lab 517</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reservationDate">Date</label>
                    <input type="date" class="form-control" id="reservationDate" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="startTime">Start Time</label>
                    <input type="time" class="form-control" id="startTime" name="start_time" required>
                </div>

                <div class="form-group">
                    <label for="endTime">End Time</label>
                    <input type="time" class="form-control" id="endTime" name="end_time" required>
                </div>

                <div class="computer-selection-container">
                    <h3>Select Computer</h3>
                    <div id="computerGrid" class="computer-grid">
                        <!-- Computer buttons will be dynamically generated here -->
                    </div>
                </div>

                <div class="selected-computer-info" style="display: none;">
                    <h4>Selected Computer</h4>
                    <p><strong>Lab:</strong> <span id="selectedLab"></span></p>
                    <p><strong>Computer:</strong> <span id="selectedComputer"></span></p>
                </div>

                <button type="submit" class="nav-btn primary">Submit Reservation</button>
            </form>
        </div>
    </div>
</div>

<style>
.computer-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin: 20px 0;
}

.computer-button {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

.computer-button:hover {
    background-color: #e9ecef;
}

.computer-button.selected {
    background-color: #007bff;
    color: white;
    border-color: #0056b3;
}

.computer-button.unavailable {
    background-color: #dc3545;
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
}

.student-info-display {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.selected-computer-info {
    background-color: #e9ecef;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labSelect = document.getElementById('labRoom');
    const computerGrid = document.getElementById('computerGrid');
    const selectedLabSpan = document.getElementById('selectedLab');
    const selectedComputerSpan = document.getElementById('selectedComputer');
    const selectedComputerInfo = document.querySelector('.selected-computer-info');
    let selectedComputer = null;

    // Generate computer buttons
    function generateComputerButtons() {
        computerGrid.innerHTML = '';
        for (let i = 1; i <= 50; i++) {
            const button = document.createElement('button');
            button.className = 'computer-button';
            button.textContent = `PC ${i}`;
            button.dataset.computerNumber = i;
            button.addEventListener('click', function() {
                if (!this.classList.contains('unavailable')) {
                    // Remove selected class from previously selected button
                    if (selectedComputer) {
                        selectedComputer.classList.remove('selected');
                    }
                    // Add selected class to current button
                    this.classList.add('selected');
                    selectedComputer = this;
                    
                    // Update selected computer info
                    selectedLabSpan.textContent = labSelect.value;
                    selectedComputerSpan.textContent = `PC ${i}`;
                    selectedComputerInfo.style.display = 'block';
                }
            });
            computerGrid.appendChild(button);
        }
    }

    // Initialize computer buttons
    generateComputerButtons();

    // Update computer availability when lab is selected
    labSelect.addEventListener('change', function() {
        generateComputerButtons();
        // Here you would typically make an AJAX call to check computer availability
        // and update the button classes accordingly
    });

    // Form submission
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedComputer) {
            alert('Please select a computer');
            return;
        }

        // Here you would typically make an AJAX call to submit the reservation
        const formData = new FormData(this);
        formData.append('computer_number', selectedComputer.dataset.computerNumber);
        
        fetch('submit_reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reservation submitted successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the reservation');
        });
    });
});
</script>
