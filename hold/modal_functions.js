// Modal functionality for admin dashboard
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Initialize all modals to be hidden on page load
document.addEventListener('DOMContentLoaded', function() {
    // Close all modals
    const modals = document.querySelectorAll('.modal-container');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
});

function showStudentSitIn(studentId, studentName, remainingSessions) {
    // Update student info in the modal
    document.getElementById('studentIdNo').textContent = studentId;
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('remainingSessions').textContent = remainingSessions || '0';
    
    // Reset form selections
    document.getElementById('purpose').value = '';
    document.getElementById('lab').value = '';
    
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
                    // Refresh the current sit-ins table if it exists
                    if (typeof loadCurrentSitIns === 'function') {
                        loadCurrentSitIns();
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(e) {
                alert('Error processing sit-in');
                console.error(e);
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}
