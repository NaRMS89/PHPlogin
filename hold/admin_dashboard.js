// Global variables
let currentTimeoutId = null;
let sitInReportData = [];
let currentPage = 1;
let entriesPerPage = 5;
let sortColumn = 'id_number';
let sortDirection = 'asc';
let purposeChart = null;
let labChart = null;
let languageChart = null;

// Initialize event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize table sorting
    const tableHeaders = document.querySelectorAll('#sitInReportTable th[data-column]');
    tableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.getAttribute('data-column');
            sortSitInTable(column);
        });
    });

    // Initialize entries per page selector
    const entriesPerPageSelect = document.getElementById('entriesPerPage');
    if (entriesPerPageSelect) {
        entriesPerPageSelect.addEventListener('change', function() {
            changeEntriesPerPage(this.value);
        });
    }

    // Initialize pagination buttons
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');
    if (prevButton) prevButton.addEventListener('click', prevPage);
    if (nextButton) nextButton.addEventListener('click', nextPage);
});

function sortSitInTable(column) {
    if (sortColumn === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = column;
        sortDirection = 'asc';
    }
    
    sitInReportData.sort((a, b) => {
        let aValue = a[column];
        let bValue = b[column];
        
        if (column === 'login_time' || column === 'logout_time') {
            aValue = new Date(aValue);
            bValue = new Date(bValue);
        }
        
        if (sortDirection === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    displaySitInData();
}

function updateCharts(data) {
    // Destroy existing charts if they exist
    if (purposeChart) purposeChart.destroy();
    if (labChart) labChart.destroy();
    if (languageChart) languageChart.destroy();

    // Purpose Chart
    const purposes = {};
    data.forEach(record => {
        purposes[record.purpose] = (purposes[record.purpose] || 0) + 1;
    });

    const purposeCtx = document.getElementById('purposePieChart').getContext('2d');
    purposeChart = new Chart(purposeCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(purposes),
            datasets: [{
                data: Object.values(purposes),
                backgroundColor: [
                    'hsla(350, 100%, 70%, 0.7)',
                    'hsla(200, 100%, 70%, 0.7)',
                    'hsla(145, 100%, 70%, 0.7)',
                    'hsla(45, 100%, 70%, 0.7)',
                    'hsla(280, 100%, 70%, 0.7)',
                ],
                borderColor: [
                    'hsla(350, 100%, 70%, 1)',
                    'hsla(200, 100%, 70%, 1)',
                    'hsla(145, 100%, 70%, 1)',
                    'hsla(45, 100%, 70%, 1)',
                    'hsla(280, 100%, 70%, 1)',
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'hsl(220, 50%, 90%)',
                        font: { size: 12 }
                    }
                }
            }
        }
    });

    // Lab Chart
    const labs = {};
    data.forEach(record => {
        labs[record.lab] = (labs[record.lab] || 0) + 1;
    });

    const labCtx = document.getElementById('labPieChart').getContext('2d');
    labChart = new Chart(labCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(labs),
            datasets: [{
                data: Object.values(labs),
                backgroundColor: [
                    'hsla(350, 100%, 70%, 0.7)',
                    'hsla(200, 100%, 70%, 0.7)',
                    'hsla(145, 100%, 70%, 0.7)',
                    'hsla(45, 100%, 70%, 0.7)',
                    'hsla(280, 100%, 70%, 0.7)',
                ],
                borderColor: [
                    'hsla(350, 100%, 70%, 1)',
                    'hsla(200, 100%, 70%, 1)',
                    'hsla(145, 100%, 70%, 1)',
                    'hsla(45, 100%, 70%, 1)',
                    'hsla(280, 100%, 70%, 1)',
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'hsl(220, 50%, 90%)',
                        font: { size: 12 }
                    }
                }
            }
        }
    });
}

function changeEntriesPerPage(value) {
    entriesPerPage = parseInt(value);
    currentPage = 1;
    displaySitInData();
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        displaySitInData();
    }
}

function nextPage() {
    const totalPages = Math.ceil(sitInReportData.length / entriesPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        displaySitInData();
    }
}

function updatePagination() {
    const totalPages = Math.ceil(sitInReportData.length / entriesPerPage);
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');
    if (prevButton) prevButton.disabled = currentPage === 1;
    if (nextButton) nextButton.disabled = currentPage === totalPages;
}

function displaySitInData() {
    const tbody = document.getElementById('sitInDataBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * entriesPerPage;
    const end = start + parseInt(entriesPerPage);
    const paginatedData = sitInReportData.slice(start, end);
    paginatedData.forEach(record => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${record.id_number}</td>
            <td>${record.purpose}</td>
            <td>${record.lab}</td>
            <td>${record.login_time}</td>
            <td>${record.logout_time}</td>
            <td>${calculateDuration(record.login_time, record.logout_time)}</td>
            <td>
                <button class="feedback-button" onclick="showFeedbackModal('${record.id_number}')">View Feedback</button>
            </td>
        `;
        tbody.appendChild(row);
    });
    // Update display entries info
    const displayStart = sitInReportData.length === 0 ? 0 : start + 1;
    const displayEnd = Math.min(end, sitInReportData.length);
    document.getElementById('displayStart').textContent = displayStart;
    document.getElementById('displayEnd').textContent = displayEnd;
    document.getElementById('displayTotal').textContent = sitInReportData.length;
    updatePagination();
}

function calculateDuration(login, logout) {
    const start = new Date(login);
    const end = new Date(logout);
    const diff = Math.abs(end - start);
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
} 