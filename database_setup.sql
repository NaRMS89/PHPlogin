-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    lab VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES info(id_number) ON DELETE CASCADE
);

-- Create lab_resources table
CREATE TABLE IF NOT EXISTS lab_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('pdf', 'link', 'document') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create student_points table
CREATE TABLE IF NOT EXISTS student_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES info(id_number) ON DELETE CASCADE
);

-- Create points_log table
CREATE TABLE IF NOT EXISTS points_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    points INT NOT NULL,
    reason TEXT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES info(id_number) ON DELETE CASCADE
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL,
    feedback_text TEXT NOT NULL,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_number) REFERENCES info(id_number) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_reservations_date ON reservations(date);
CREATE INDEX idx_reservations_lab ON reservations(lab);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_student_points_id ON student_points(id_number);
CREATE INDEX idx_points_log_id ON points_log(id_number);
CREATE INDEX idx_feedback_id ON feedback(id_number); 