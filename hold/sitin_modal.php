<!-- Sit-in Modal -->
<div id="studentInfoModal" class="modal-container" style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 1050;">
    <div class="modal" style="background: #1a1a2e; border-radius: 10px; padding: 25px; width: 90%; max-width: 500px; margin: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.5); border: 1px solid #2a2a3e;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 class="modal-title" style="color: #fff; margin: 0; font-size: 24px;">Sit-in Form</h2>
            <button onclick="closeModal('studentInfoModal')" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <div class="form-group" style="background: #2a2a3e; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #333;">
            <p style="margin: 8px 0;"><b style="color: #fff;">ID Number:</b> <span id="studentIdNo" style="color: #fff;"></span></p>
            <p style="margin: 8px 0;"><b style="color: #fff;">Student Name:</b> <span id="studentName" style="color: #fff;"></span></p>
            <p style="margin: 8px 0;"><b style="color: #fff;">Remaining Sessions:</b> <span id="remainingSessions" style="color: #4CAF50;"></span></p>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="purpose" style="display: block; margin-bottom: 8px; color: #fff; font-weight: bold;">Purpose:</label>
            <select id="purpose" class="form-select" style="width: 100%; padding: 10px; border: 1px solid #333; border-radius: 5px; background: #2a2a3e; color: #fff; font-size: 14px;">
                <option value="" disabled selected>Select Purpose</option>
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
        
        <div class="form-group" style="margin-bottom: 25px;">
            <label for="lab" style="display: block; margin-bottom: 8px; color: #fff; font-weight: bold;">Lab:</label>
            <select id="lab" class="form-select" style="width: 100%; padding: 10px; border: 1px solid #333; border-radius: 5px; background: #2a2a3e; color: #fff; font-size: 14px;">
                <option value="" disabled selected>Select Lab Room</option>
                <option value="524">Lab 524</option>
                <option value="526">Lab 526</option>
                <option value="528">Lab 528</option>
                <option value="530">Lab 530</option>
                <option value="542">Lab 542</option>
                <option value="544">Lab 544</option>
                <option value="517">Lab 517</option>
            </select>
        </div>
        
        <div class="button-group" style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-success" onclick="addSitIn()" style="padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Sit-in</button>
            <button class="btn btn-danger" onclick="closeModal('studentInfoModal')" style="padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Close</button>
        </div>
    </div>
</div>
