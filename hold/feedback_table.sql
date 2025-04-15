CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sit_in_id INT NOT NULL,
    feedback_text TEXT NOT NULL,
    feedback_date DATETIME NOT NULL,
    FOREIGN KEY (sit_in_id) REFERENCES sitin(id) ON DELETE CASCADE
); 