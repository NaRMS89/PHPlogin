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

// Export form handling
document.getElementById('exportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const exportType = document.getElementById('exportType').value;
    const filterBy = document.getElementById('filterBy').value;
    const labRoom = document.getElementById('labRoom').value;
    const purpose = document.getElementById('purpose').value;
    
    // Prepare the data to send
    const formData = new FormData();
    formData.append('exportType', exportType);
    formData.append('filterBy', filterBy);
    formData.append('labRoom', labRoom);
    formData.append('purpose', purpose);
    
    // Send AJAX request to export data
    $.ajax({
        url: 'export_sitin_data.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                if (exportType === 'print') {
                    // For print, open in new window
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(response);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    // For other formats, trigger download
                    const blob = new Blob([response], { type: getContentType(exportType) });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `sit_in_data.${exportType}`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
                closeModal('exportModal');
            } catch(e) {
                alert('Error processing export');
                console.error(e);
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
});

// Helper function to get content type based on export type
function getContentType(exportType) {
    switch(exportType) {
        case 'csv':
            return 'text/csv';
        case 'excel':
            return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        case 'pdf':
            return 'application/pdf';
        default:
            return 'text/plain';
    }
}

// Function to update export options based on filter selection
function updateExportOptions() {
    const filterBy = document.getElementById('filterBy').value;
    const labOptions = document.getElementById('labOptions');
    const purposeOptions = document.getElementById('purposeOptions');
    
    if (filterBy === 'lab') {
        labOptions.style.display = 'block';
        purposeOptions.style.display = 'none';
    } else {
        labOptions.style.display = 'none';
        purposeOptions.style.display = 'block';
    }
}
