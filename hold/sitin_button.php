<!-- Sit-in Form Button -->
<button 
    onclick="openModal('studentInfoModal')" 
    style="position: fixed; top: 70px; right: 20px; padding: 12px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
    Show Sit-in Form
</button>

<script>
// Make sure the studentInfoModal has the correct initial style
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('studentInfoModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.style.zIndex = '1000';
    }
});
</script>
