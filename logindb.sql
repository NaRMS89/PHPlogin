-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `logindb`;
USE `logindb`;

-- Create info table (for user/student information)
CREATE TABLE IF NOT EXISTS `info` (
  `id_number` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50),
  `course` varchar(50) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `sessions` int(11) NOT NULL DEFAULT 15,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `points` int(11) NOT NULL DEFAULT 0,
  `date_registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sitin table
CREATE TABLE IF NOT EXISTS `sitin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) NOT NULL,
  `purpose` text NOT NULL,
  `lab` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_number` (`id_number`),
  CONSTRAINT `sitin_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `info` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create feedback table
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sit_in_id` int(11) NOT NULL,
  `feedback_text` text,
  `feedback_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sit_in_id` (`sit_in_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`sit_in_id`) REFERENCES `sitin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create lab_resources table
CREATE TABLE IF NOT EXISTS `lab_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'file',
  `description` text,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `lab_room` varchar(50) NOT NULL,
  `file_path` varchar(255),
  `status` enum('available','in_use','maintenance') NOT NULL DEFAULT 'available',
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create announcements table
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `posted_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `info` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reservations table
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `info` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sitin_report table
CREATE TABLE IF NOT EXISTS `sitin_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) NOT NULL,
  `purpose` text NOT NULL,
  `lab` varchar(50) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_number` (`id_number`),
  CONSTRAINT `sitin_report_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `info` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample admin user
INSERT INTO `info` (`id_number`, `first_name`, `last_name`, `course`, `year_level`, `email`, `password`, `role`) VALUES
('admin', 'Admin', 'User', 'BSIT', '4th Year', 'admin@example.com', '$2y$10$8K1p/a0dR1Ux5Y5Y5Y5Y5O5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y5Y', 'admin');

-- Insert sample lab resources
INSERT INTO `lab_resources` (`title`, `type`, `description`, `quantity`, `lab_room`, `status`) VALUES
('Computer', 'equipment', 'Desktop PC with Windows 10', 20, '524', 'available'),
('Printer', 'equipment', 'HP LaserJet Pro', 2, '526', 'available'),
('Projector', 'equipment', 'Epson HD Projector', 1, '528', 'available'),
('Microscope', 'equipment', 'Digital Microscope', 5, '530', 'available'),
('Network Switch', 'equipment', '24-port Gigabit Switch', 1, '542', 'available'),
('iMac', 'equipment', 'Apple iMac 27"', 15, 'Mac Lab', 'available');

-- Insert sample announcement
INSERT INTO `announcements` (`title`, `content`, `posted_by`) VALUES
('Welcome to the Lab!', 'Welcome to our computer laboratory. Please follow all rules and regulations.', 'admin'); 