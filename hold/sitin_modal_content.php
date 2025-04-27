<!-- Sit-in Form Modal Content -->
<div id="studentInfoModal" class="modal-container" style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
    <div class="modal" style="background: var(--background); border-radius: 10px; padding: 25px; max-width: 500px; margin: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h2 class="modal-title" style="color: var(--light); margin-bottom: 20px; font-size: 24px; text-align: center;">Sit-in Form</h2>
        <div class="form-group" style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--border-color);">
            <p style="margin: 8px 0;"><b style="color: var(--light);">ID Number:</b> <span id="studentIdNo" style="color: var(--light);">5000</span></p>
            <p style="margin: 8px 0;"><b style="color: var(--light);">Student Name:</b> <span id="studentName" style="color: var(--light);">Juan Dela Cruz</span></p>
            <p style="margin: 8px 0;"><b style="color: var(--light);">Remaining Sessions:</b> <span id="remainingSessions" style="color: var(--light);">undefined</span></p>
            
        </div>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="purpose" style="display: block; margin-bottom: 8px; color: var(--light); font-weight: bold;">Purpose:</label>
            <select id="purpose" class="compact-select" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; background: #1a1a2e; color: var(--light); font-size: 14px;">
                <option value="" style="background: #1a1a2e; color: var(--light);">Select Purpose</option>
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
            <label for="lab" style="display: block; margin-bottom: 8px; color: var(--light); font-weight: bold;">Lab:</label>
            <select id="lab" class="compact-select" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; background: #1a1a2e; color: var(--light); font-size: 14px;">
                <option value="" style="background: #1a1a2e; color: var(--light);">Select Lab Room</option>
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
            <button class="modal-button primary" onclick="addSitIn()" style="padding: 10px 20px; background: transparent; color: var(--light); border: 1px solid var(--border-color); border-radius: 5px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">Sit-in</button>
            <button class="modal-button secondary" onclick="closeModal('studentInfoModal')" style="padding: 10px 20px; background: transparent; color: var(--light); border: 1px solid var(--border-color); border-radius: 5px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">Close</button>
        </div>
    </div>
</div>
