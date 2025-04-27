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

// JavaScript to handle dynamic lab and computer selection
document.addEventListener('DOMContentLoaded', function() {
    const labRoomSelect = document.getElementById('labRoom');
    const computerSelectionDiv = document.getElementById('computerSelection');
    const computerSelect = document.getElementById('computerSelect'); // New dropdown element
    const selectedLabSpan = document.getElementById('selectedLab');
    const selectedComputerInput = document.getElementById('selectedComputer');
    const computerDisplaySpan = document.getElementById('computerDisplay');
    const timeInInput = document.getElementById('timeIn');
    const reservationDateInput = document.getElementById('reservationDate');
    const studentIdSpan = document.getElementById('studentId'); // Assuming it's populated by PHP
    const studentNameSpan = document.getElementById('studentName'); // Assuming it's populated by PHP
    const remainingSessionsInput = document.getElementById('remainingSessions'); // Assuming it's populated by PHP

    // Function to fetch available computers via AJAX
    async function fetchComputers(labRoom) {
        try {
            // **IMPORTANT**: Update this URL to your actual PHP script
            const response = await fetch(`get_available_computers.php?lab=${labRoom}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json(); // Expecting { available: [pc_number], unavailable: [pc_number] }
            return data;
        } catch (error) {
            console.error('Error fetching computer availability:', error);
            // Fallback: Assume all are available if fetch fails
            return { available: Array.from({ length: 50 }, (_, i) => i + 1), unavailable: [] };
        }
    }

    // Function to populate computer dropdown based on selected lab and availability
    async function populateComputerSelect(selectedRoom) {
        if (selectedRoom) {
            selectedLabSpan.textContent = selectedRoom;
            computerSelect.innerHTML = ''; // Clear loading/previous options
            computerSelectionDiv.style.display = 'block';
            computerSelect.disabled = true; // Keep disabled while loading
            selectedComputerInput.value = ''; // Reset selection
            computerDisplaySpan.textContent = 'None'; // Reset display

            const availability = await fetchComputers(selectedRoom);
            computerSelect.innerHTML = ''; // Clear loading/previous options

            let firstAvailablePCIdentifier = null; // Variable to store the first available PC

            for (let i = 1; i <= 50; i++) {
                const computerOption = document.createElement('option');
                const pcIdentifier = `${selectedRoom}-PC${i}`;
                computerOption.value = pcIdentifier; // Value like 524-PC1

                if (availability.unavailable.includes(i)) {
                    computerOption.textContent = `PC ${i} (Unavailable)`;
                    computerOption.disabled = true;
                } else {
                    computerOption.textContent = `PC ${i}`;
                    computerOption.disabled = false;
                    // Store the first available PC found
                    if (firstAvailablePCIdentifier === null) {
                        firstAvailablePCIdentifier = pcIdentifier;
                    }
                }
                computerSelect.appendChild(computerOption);
            }
            computerSelect.disabled = false; // Enable the dropdown

            // Automatically select the first available PC if one exists
            if (firstAvailablePCIdentifier) {
                computerSelect.value = firstAvailablePCIdentifier;
                // Manually trigger change event to update display and hidden input
                computerSelect.dispatchEvent(new Event('change'));
            } else {
                 // Handle case where no PCs are available (optional: add a disabled message option)
                 const noPCOption = document.createElement('option');
                 noPCOption.textContent = 'No PCs Available';
                 noPCOption.disabled = true;
                 computerSelect.appendChild(noPCOption);
                 computerSelect.value = ''; // Ensure no value is selected
                 selectedComputerInput.value = '';
                 computerDisplaySpan.textContent = 'None';
            }

        } else {
            computerSelectionDiv.style.display = 'none';
            // Clear options instead of adding placeholder
            computerSelect.innerHTML = ''; 
            computerSelect.disabled = true;
            selectedComputerInput.value = '';
            computerDisplaySpan.textContent = 'None';
        }
    }

    // Event listener for lab room selection change
    if (labRoomSelect) {
        labRoomSelect.addEventListener('change', function() {
            populateComputerSelect(this.value);
        });
    }

    // Event listener for computer selection change
    if (computerSelect) {
        computerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value && !selectedOption.disabled) {
                selectedComputerInput.value = selectedOption.value;
                // Update display text correctly, handling the '(Unavailable)' case
                let displayText = selectedOption.textContent;
                computerDisplaySpan.textContent = `Room ${selectedLabSpan.textContent} ${displayText}`;
            } else {
                selectedComputerInput.value = '';
                computerDisplaySpan.textContent = 'None';
            }
        });
    }

    // Set the "Time In" to the current time (HH:MM format)
    if (timeInInput) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInInput.value = `${hours}:${minutes}`;
    }

    // Set the minimum date for the reservation date input to today
    if (reservationDateInput) {
        const today = new Date().toISOString().split('T')[0];
        reservationDateInput.setAttribute('min', today);
        // reservationDateInput.value = today; // Optionally set default to today
    }

    // Placeholder: Load pending reservations (requires another AJAX call)
    const pendingReservationsDiv = document.getElementById('pendingReservations');
    if (pendingReservationsDiv) {
        // Example: fetchPendingReservations();
        // This function would fetch and display pending reservations for the user
        // pendingReservationsDiv.innerHTML = 'Loading pending reservations...';
    }

    // --- Additions for handling existing content visibility logic ---
    const navLinks = document.querySelectorAll('.nav-link');
    const dynamicContents = document.querySelectorAll('.dynamic-content');

    // Function to hide all content sections
    function hideAllSections() {
        dynamicContents.forEach(content => content.classList.remove('active'));
    }

    // Function to show a specific content section
    function showSection(sectionId) {
        hideAllSections();
        const sectionToShow = document.getElementById(sectionId);
        if (sectionToShow) {
            sectionToShow.classList.add('active');
        }
    }

    // Add click event listeners to navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default anchor behavior
            const targetId = this.getAttribute('data-target');
            showSection(targetId);

            // Optional: Update active state for nav links if needed
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Show the default section (e.g., reservation) on page load
    // Make sure the #reservationContent div in dashboard.php has the 'active' class initially
    // showSection('reservationContent'); // Or check which one should be active by default

    // If #reservationContent is the default, ensure it's shown
    if (document.getElementById('reservationContent')?.classList.contains('active')) {
       // It's already set to active in the HTML, no JS action needed for initial load
    } else if (dynamicContents.length > 0 && !document.querySelector('.dynamic-content.active')){
         // Fallback: If no section is active, activate the first one (or reservation)
         showSection('reservationContent');
    }

});
