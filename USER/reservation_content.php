<!-- Reservation Content -->
<div id="reservationContent" class="dynamic-content">
    <div class="reservation-container">
        <h2>Lab Reservation</h2>
        
        <!-- Lab status cards -->
        <div class="lab-grid">
            <?php
            // Lab occupancy (you would fetch this from database in reality)
            $labs = [
                '524' => ['capacity' => 50, 'occupied' => 0],
                '526' => ['capacity' => 50, 'occupied' => 0],
                '528' => ['capacity' => 50, 'occupied' => 0],
                '530' => ['capacity' => 50, 'occupied' => 0],
                '542' => ['capacity' => 50, 'occupied' => 0],
                '544' => ['capacity' => 50, 'occupied' => 0],
                '517' => ['capacity' => 50, 'occupied' => 0]
            ];
            
            // Get actual occupancy from database
            if ($conn instanceof mysqli) {
                $sql = "SELECT lab_id, COUNT(*) as count FROM reservations WHERE reservation_date >= CURDATE() AND status = 'approved' GROUP BY lab_id";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        if (isset($labs[$row['lab_id']])) {
                            $labs[$row['lab_id']]['occupied'] = (int)$row['count'];
                        }
                    }
                }
            }
            
            // Display lab cards
            foreach ($labs as $labId => $lab) {
                $isAvailable = $lab['occupied'] < $lab['capacity'];
                $statusClass = $isAvailable ? 'available' : 'unavailable';
                ?>
                <div class="lab-card">
                    <h3>Lab <?php echo $labId; ?></h3>
                    <p class="lab-status">Current Occupancy: <?php echo $lab['occupied']; ?>/<?php echo $lab['capacity']; ?></p>
                    <p class="lab-status <?php echo $statusClass; ?>">
                        Status: <?php echo $isAvailable ? 'Available' : 'Full'; ?>
                    </p>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Reservation form -->
        <div class="reservation-form-container">
            <h3>Make a Reservation</h3>
            
            <!-- Student Info Display -->
            <div class="student-info-display">
                <p><strong>ID Number:</strong> <?php echo $_SESSION['user_data']['id_number']; ?></p>
                <p><strong>Name:</strong> <?php echo $_SESSION['user_data']['first_name'] . ' ' . $_SESSION['user_data']['last_name']; ?></p>
                <p><strong>Remaining Sessions:</strong> <?php echo $_SESSION['user_data']['sessions']; ?></p>
            </div>
            
            <!-- Form Steps -->
            <div class="reservation-steps">
                <div class="step-1">
                    <form id="reservationStepOne" class="reservation-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="labRoom">Lab Room</label>
                                <select class="form-control" id="labRoom" name="lab" required>
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
                            <div class="form-group">
                                <label for="reservationDate">Date</label>
                                <input type="date" class="form-control" id="reservationDate" name="date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="startTime">Start Time</label>
                                <input type="time" class="form-control" id="startTime" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label for="endTime">End Time</label>
                                <input type="time" class="form-control" id="endTime" name="end_time" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="purpose">Purpose</label>
                            <select class="form-control" id="purpose" name="purpose" required>
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
                        <button type="button" id="checkAvailability" class="nav-btn">Check Computer Availability</button>
                    </form>
                </div>
                
                <div class="step-2" style="display: none;">
                    <h4>Select a Computer</h4>
                    <div id="computerGrid" class="computer-grid"></div>
                    
                    <div class="selected-info" style="display: none;">
                        <p><strong>Selected:</strong> <span id="selectedInfo"></span></p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" id="backToStep1" class="nav-btn secondary">Back</button>
                        <button type="button" id="confirmReservation" class="nav-btn primary">Confirm Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Computer selection modal -->
<div id="computerSelectionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Computers in Lab <span id="modalLabId"></span></h2>
        <div id="modalComputerGrid" class="computer-grid"></div>
    </div>
</div>
