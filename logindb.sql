-- Create sit_in_record table
CREATE TABLE IF NOT EXISTS `sit_in_record` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `lab` VARCHAR(50) NOT NULL,
    `login_time` DATETIME NOT NULL,
    `logout_time` DATETIME,
    `date` DATE NOT NULL,
    `feedback` TEXT,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`student_id`) REFERENCES `info`(`id_number`) ON DELETE CASCADE
);

-- Add role column to users table
ALTER TABLE `users` ADD COLUMN `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user';

-- Create feedback table
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_number` VARCHAR(20) NOT NULL,
    `lab` VARCHAR(50) NOT NULL,
    `feedback_text` TEXT NOT NULL,
    `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_number`) REFERENCES `info`(`id_number`) ON DELETE CASCADE
);

-- Add indexes for better performance
ALTER TABLE `sit_in_record` ADD INDEX `idx_student_date` (`student_id`, `date`);
ALTER TABLE `sit_in_record` ADD INDEX `idx_lab_date` (`lab`, `date`);
ALTER TABLE `feedback` ADD INDEX `idx_student_date` (`id_number`, `date`);
ALTER TABLE `feedback` ADD INDEX `idx_lab_date` (`lab`, `date`);

-- Update existing tables if needed
ALTER TABLE `info` MODIFY COLUMN `sessions` INT NOT NULL DEFAULT 10;
ALTER TABLE `sitin` ADD COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active';
ALTER TABLE `sitin_report` ADD COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active';

-- Add any missing columns to existing tables
ALTER TABLE `sitin_report` 
    ADD COLUMN IF NOT EXISTS `duration` INT,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active';

-- Create views for common queries
CREATE OR REPLACE VIEW `v_active_sitins` AS
SELECT s.*, i.first_name, i.last_name
FROM sitin s
JOIN info i ON s.id_number = i.id_number
WHERE s.status = 'active';

CREATE OR REPLACE VIEW `v_daily_stats` AS
SELECT 
    DATE(login_time) as date,
    COUNT(*) as total_sitins,
    COUNT(DISTINCT id_number) as active_users,
    MAX(CASE WHEN lab_count = max_lab_count THEN lab ELSE NULL END) as most_used_lab,
    MAX(CASE WHEN purpose_count = max_purpose_count THEN purpose ELSE NULL END) as most_used_purpose,
    AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as avg_duration
FROM (
    SELECT 
        s.*,
        COUNT(*) OVER (PARTITION BY DATE(login_time), lab) as lab_count,
        MAX(COUNT(*)) OVER (PARTITION BY DATE(login_time)) as max_lab_count,
        COUNT(*) OVER (PARTITION BY DATE(login_time), purpose) as purpose_count,
        MAX(COUNT(*)) OVER (PARTITION BY DATE(login_time)) as max_purpose_count
    FROM sitin_report s
) as subquery
GROUP BY DATE(login_time);

-- Add triggers for automatic updates
DELIMITER //

CREATE TRIGGER `trg_update_sit_in_duration`
BEFORE UPDATE ON `sitin_report`
FOR EACH ROW
BEGIN
    IF NEW.logout_time IS NOT NULL AND OLD.logout_time IS NULL THEN
        SET NEW.duration = TIMESTAMPDIFF(MINUTE, NEW.login_time, NEW.logout_time);
    END IF;
END//

DELIMITER ;

-- Add stored procedures for common operations
DELIMITER //

CREATE PROCEDURE `sp_get_student_stats`(IN p_id_number VARCHAR(20))
BEGIN
    SELECT 
        COUNT(*) as total_sitins,
        AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as avg_duration,
        COUNT(DISTINCT lab) as labs_used,
        COUNT(DISTINCT purpose) as purposes_used
    FROM sitin_report
    WHERE id_number = p_id_number;
END//

CREATE PROCEDURE `sp_get_lab_stats`(IN p_lab VARCHAR(50))
BEGIN
    SELECT 
        COUNT(*) as total_sitins,
        COUNT(DISTINCT id_number) as unique_students,
        AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as avg_duration,
        COUNT(DISTINCT purpose) as purposes_used
    FROM sitin_report
    WHERE lab = p_lab;
END//

DELIMITER ; 