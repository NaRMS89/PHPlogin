-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2025 at 02:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `logindb`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `announcement_text` text NOT NULL,
  `date_posted` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `announcement_text`, `date_posted`) VALUES
(21, 'test', '2025-04-01');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `sit_in_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `admin_id` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','resolved','closed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `info`
--

CREATE TABLE `info` (
  `id_number` int(11) NOT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `sessions` int(11) NOT NULL DEFAULT 15,
  `points` int(11) NOT NULL DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `username`, `password`, `sessions`, `points`, `profile_picture`) VALUES
(123, '1231', '23', '123', 'BSPSY', '1', '213@gmail', '123', '123', 15, 0, 'default.png'),
(1000, 'Santos', 'Maria', '123', 'BSBA', '2', '1000@gmail.com', 'msantos1001', '123', 13, 0, 'pngfind.com-weights-png-1091286.png'),
(2000, 'Reyes', 'Maria', 'Dela', 'BSCS', '1', '2000@gmail.com', 'jreyes1002', '123', 29, 0, 'default.png'),
(3000, 'Lim', 'Anna', 'Tan', 'BSIT', '3', '3000@gmail.com', 'alim1003', '123', 29, 0, '4OTdtjem_F_thumb_1200x630.jpg'),
(4000, 'Cruz', 'Pedro', 'Reyes', 'BSME', '2', '4000@gmail.com', 'pcruz1004', '123', 15, 0, 'default.png'),
(5000, ' Dela Cruz', 'Juan', 'Santos', 'BSIT', '2', '5000@gmail.com', 'juandc', '123', 30, 0, 'default.png'),
(6000, 'Reyes', 'Maria', 'Lourdes', 'BSECE', '3', '6000@example.com', 'mariareyes', '123', 15, 0, 'default.png'),
(9000, 'test', 'test', 'test', 'BSBIO', '4', 'test@gmail.com', 'test', '123', 15, 0, 'default.png'),
(10000, 'Doe', 'John', 'Michael', 'BSIT', '1', 'john.doe@example.com', 'johndoe', '123', 30, 0, 'default.png'),
(11000, 'Specter', 'Mark', 'y', 'BSME', '2', 'mark@gmail.com', 'mark123', '123', 15, 0, 'default.png'),
(20948048, 'Singco', 'Nathaniel Ron', 'M.', 'BSIT', '3', 'nathanielron09655524395@gmail.com', 'nrms', '123', 30, 0, 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `points_log`
--

CREATE TABLE `points_log` (
  `id` int(11) NOT NULL,
  `id_number` int(11) NOT NULL,
  `points_added` int(11) NOT NULL,
  `reason` text NOT NULL,
  `added_by` varchar(50) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `lab` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitin`
--

CREATE TABLE `sitin` (
  `id` int(11) NOT NULL,
  `id_number` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin`
--

INSERT INTO `sitin` (`id`, `id_number`, `purpose`, `lab`, `status`, `login_time`) VALUES
(30, '1000', 'C Programming', '524', 'inactive', '2025-04-01 09:45:29'),
(31, '2000', 'C Programming', '524', 'inactive', '2025-04-01 10:03:22'),
(32, '1000', 'C Programming', '524', 'active', '2025-04-01 11:40:20'),
(33, '3000', 'Java Programming', '526', 'inactive', '2025-04-01 12:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_report`
--

CREATE TABLE `sitin_report` (
  `id` int(11) NOT NULL,
  `id_number` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `status` enum('active','inactive','timeout') NOT NULL DEFAULT 'active',
  `feedback` text DEFAULT NULL,
  `feedback_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 

--
-- Dumping data for table `sitin_report`
--

INSERT INTO `sitin_report` (`id`, `id_number`, `purpose`, `lab`, `login_time`, `logout_time`, `feedback`, `feedback_date`) VALUES
(151, '1000', 'C Programming', '524', '2025-04-01 09:45:29', '2025-04-01 10:01:43', 'very cold', '2025-04-01 10:01:43'),
(152, '2000', 'C Programming', '524', '2025-04-01 10:03:22', '2025-04-01 11:40:11', 'very hot', '2025-04-01 11:40:11'),
(153, '3000', 'Java Programming', '526', '2025-04-01 12:20:30', '2025-04-01 12:20:30', 'broken mouse sensor', '2025-04-01 12:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_history`
--

CREATE TABLE `sit_in_history` (
  `id` int(11) NOT NULL,
  `id_number` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','timeout') NOT NULL DEFAULT 'active',
  `admin_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Create views for common queries
--

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

-- --------------------------------------------------------

--
-- Add triggers for automatic updates
--

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

-- --------------------------------------------------------

--
-- Add stored procedures for common operations
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sit_in_id` (`sit_in_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `info`
--
ALTER TABLE `info`
  ADD PRIMARY KEY (`id_number`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `points_log`
--
ALTER TABLE `points_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_number` (`id_number`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `sitin`
--
ALTER TABLE `sitin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sitin_report`
--
ALTER TABLE `sitin_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_number` (`id_number`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
  MODIFY `id_number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20948054;

--
-- AUTO_INCREMENT for table `points_log`
--
ALTER TABLE `points_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin`
--
ALTER TABLE `sitin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `sitin_report`
--
ALTER TABLE `sitin_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `points_log`
--
ALTER TABLE `points_log`
  MODIFY COLUMN `id_number` INT(11) NOT NULL;

ALTER TABLE `points_log`
  ADD CONSTRAINT `points_log_ibfk_1` FOREIGN KEY (`id_number`) REFERENCES `info` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
