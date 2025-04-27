<!-- Student Info Modal -->
<div id="studentInfoModal" class="modal-container">
    <div class="modal">
        <span class="close" onclick="closeModal('studentInfoModal')">&times;</span>
        <h2>Sit-in Form</h2>
        <div class="student-info">
            <p><strong>ID Number:</strong> <span id="studentIdNo"></span></p>
            <p><strong>Student Name:</strong> <span id="studentName"></span></p>
            <p><strong>Remaining Sessions:</strong> <span id="remainingSessions"></span></p>
        </div>
        <div class="form-group">
            <label for="purpose">Purpose:</label>
            <select id="purpose" class="dashboard-select">
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
            <label for="lab">Lab:</label>
            <select id="lab" class="dashboard-select">
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
        <div class="modal-buttons">
            <button class="give-point-btn" onclick="addSitIn()">Sit-in</button>
            <button class="cancel-btn" onclick="closeModal('studentInfoModal')">Close</button>
        </div>
    </div>
</div>
