// Reservation system functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize min date for reservation to today
    const dateInput = document.getElementById('reservationDate');
    if (dateInput) {
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        dateInput.min = formattedDate;
        dateInput.value = formattedDate;
    }
    
    // Check computer availability button
    const checkAvailabilityBtn = document.getElementById('checkAvailability');
    if (checkAvailabilityBtn) {
        checkAvailabilityBtn.addEventListener('click', function() {
            const lab = document.getElementById('labRoom').value;
            const date = document.getElementById('reservationDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const purpose = document.getElementById('purpose').value;
            
            // Validate inputs
            if (!lab || !date || !startTime || !endTime || !purpose) {
                alert("Please fill in all required fields");
                return;
            }
            
            if (startTime >= endTime) {
                alert("End time must be after start time");
                return;
            }
            
            // Get available computers
            getAvailableComputers(lab, date, startTime, endTime);
        });
    }
    
    // Back button in step 2
    const backToStep1Btn = document.getElementById('backToStep1');
    if (backToStep1Btn) {
        backToStep1Btn.addEventListener('click', function() {
            document.querySelector('.step-1').style.display = 'block';
            document.querySelector('.step-2').style.display = 'none';
        });
    }
    
    // Confirm reservation button
    const confirmReservationBtn = document.getElementById('confirmReservation');
    if (confirmReservationBtn) {
        confirmReservationBtn.addEventListener('click', function() {
            const lab = document.getElementById('labRoom').value;
            const date = document.getElementById('reservationDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const purpose = document.getElementById('purpose').value;
            const selectedComputerEl = document.querySelector('.computer-btn.selected');
            
            if (!selectedComputerEl) {
                alert("Please select a computer");
                return;
            }
            
            const computerNumber = selectedComputerEl.dataset.number;
            
            // Submit the reservation
            submitReservation(lab, computerNumber, purpose, date, startTime, endTime);
        });
    }
});

// Function to get available computers
function getAvailableComputers(lab, date, startTime, endTime) {
    fetch(`get_available_computers.php?lab_id=${lab}&date=${date}&start_time=${startTime}&end_time=${endTime}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            
            displayComputerGrid(data);
        })
        .catch(error => {
            console.error('Error fetching available computers:', error);
            alert('Error checking computer availability. Please try again.');
        });
}

// Function to display computer grid
function displayComputerGrid(data) {
    const computerGrid = document.getElementById('computerGrid');
    computerGrid.innerHTML = '';
    
    // Sort computers by number
    const computers = data.computers.sort((a, b) => a.number - b.number);
    
    // Create grid layout with 10 computers per row for better organization
    let html = '<div class="grid-layout">';
    
    // Display lab room information
    html += `<div class="lab-selection-info">Selected Lab: ${data.lab_id}, Date: ${data.date}</div>`;
    
    // Create rows of computers (10 per row for better visibility)
    for (let i = 0; i < computers.length; i++) {
        const computer = computers[i];
        const isAvailable = computer.status === 'available';
        const statusClass = isAvailable ? 'available' : 'unavailable';
        const disabledAttr = isAvailable ? '' : 'disabled';
        
        // Start a new row every 10 computers
        if (i % 10 === 0) {
            if (i > 0) html += '</div>'; // Close previous row
            html += '<div class="computer-row">';
        }
        
        html += `
            <div class="computer-item">
                <button class="computer-btn ${statusClass}" data-number="${computer.number}" ${disabledAttr} 
                        onclick="selectComputer(this, '${data.lab_id}', ${computer.number})">
                    PC ${computer.number}
                </button>
            </div>
        `;
        
        // Close the last row
        if (i === computers.length - 1) {
            html += '</div>';
        }
    }
    
    html += '</div>';
    computerGrid.innerHTML = html;
    
    // Show step 2
    document.querySelector('.step-1').style.display = 'none';
    document.querySelector('.step-2').style.display = 'block';
}

// Function to select a computer
function selectComputer(button, labId, computerNumber) {
    // Remove selected class from all buttons
    document.querySelectorAll('.computer-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Add selected class to clicked button
    button.classList.add('selected');
    
    // Update selected info
    const selectedInfo = document.getElementById('selectedInfo');
    selectedInfo.textContent = `Lab ${labId}, Computer ${computerNumber}`;
    
    // Show selected info
    document.querySelector('.selected-info').style.display = 'block';
}

// Function to submit reservation
function submitReservation(lab, computerNumber, purpose, date, startTime, endTime) {
    // Create form data
    const formData = new FormData();
    formData.append('make_reservation', 1);
    formData.append('lab', lab);
    formData.append('computer_number', computerNumber);
    formData.append('purpose', purpose);
    formData.append('date', date);
    formData.append('start_time', startTime);
    formData.append('end_time', endTime);
    
    // Submit form
    fetch('process_reservation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Reset form and go back to step 1
            document.getElementById('reservationStepOne').reset();
            document.querySelector('.step-1').style.display = 'block';
            document.querySelector('.step-2').style.display = 'none';
        } else {
            alert(data.message || 'Error creating reservation');
        }
    })
    .catch(error => {
        console.error('Error submitting reservation:', error);
        alert('Error submitting reservation. Please try again.');
    });
}
