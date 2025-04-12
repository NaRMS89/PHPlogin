<!-- Lab Resources Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Lab Resources</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="resourcesTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Date Added</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Resources will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add this before the closing body tag -->
<script>
// Load resources when page loads
$(document).ready(function() {
    loadResources();
});

function loadResources() {
    $.ajax({
        url: 'ADMIN/get_resources.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let tbody = $('#resourcesTable tbody');
                tbody.empty();
                
                response.data.forEach(function(resource) {
                    let actionBtn = '';
                    if (resource.type === 'link') {
                        actionBtn = `<a href="${resource.file_path}" target="_blank" class="btn btn-primary btn-sm">Open Link</a>`;
                    } else {
                        actionBtn = `<a href="${resource.file_path}" class="btn btn-primary btn-sm" download>Download</a>`;
                    }
                    
                    let typeLabel = resource.type.charAt(0).toUpperCase() + resource.type.slice(1);
                    
                    tbody.append(`
                        <tr>
                            <td>${resource.title}</td>
                            <td>${resource.description}</td>
                            <td>${typeLabel}</td>
                            <td>${resource.formatted_date}</td>
                            <td>${actionBtn}</td>
                        </tr>
                    `);
                });
            } else {
                console.error('Error loading resources:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
        }
    });
}
</script> 