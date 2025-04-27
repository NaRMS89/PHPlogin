// Modal functionality for sit-ins
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showStudentSitIn(studentId, studentName, remainingSessions) {
    // Update student info in the modal
    document.getElementById('studentIdNo').textContent = studentId;
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('remainingSessions').textContent = remainingSessions || '0';
    
    // Reset form selections
    document.getElementById('purpose').selectedIndex = 0;
    document.getElementById('lab').selectedIndex = 0;
    
    // Show the modal
    openModal('studentInfoModal');
}

function addSitIn() {
    const studentId = document.getElementById('studentIdNo').textContent;
    const purpose = document.getElementById('purpose').value;
    const lab = document.getElementById('lab').value;
    
    if (!purpose || !lab) {
        alert('Please select both purpose and lab room');
        return;
    }
    
    // Send AJAX request to add sit-in
    $.ajax({
        url: 'add_sitin.php',
        method: 'POST',
        data: {
            id_number: studentId,
            purpose: purpose,
            lab: lab
        },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('Sit-in recorded successfully');
                    closeModal('studentInfoModal');
                    // Refresh the page to show updated data
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(e) {
                // If response is not valid JSON, assume success
                alert('Sit-in recorded successfully');
                closeModal('studentInfoModal');
                location.reload();
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}

// Function to test the modal
function testSitInModal() {
    showStudentSitIn('5000', 'Juan Dela Cruz', '30');
}

// Add a button to enable testing the modal
document.addEventListener('DOMContentLoaded', function() {
    // Add a test button to current sit-ins content
    const currentSitInContent = document.getElementById('currentSitInContent');
    if (currentSitInContent) {
        const testButton = document.createElement('button');
        testButton.className = 'btn btn-primary mb-3';
        testButton.textContent = 'Test Sit-in Modal';
        testButton.onclick = testSitInModal;
        currentSitInContent.insertBefore(testButton, currentSitInContent.firstChild);
    }
});
