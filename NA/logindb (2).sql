-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2025 at 12:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(21, 'test', '2025-04-01'),
(22, 'test', '2025-04-15'),
(23, 'hi', '2025-04-15');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `sit_in_id` int(11) NOT NULL,
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
  `total_points` int(11) NOT NULL DEFAULT 0,
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
(6000, 'Reyes', 'Maria', 'Lourdes', 'BSECE', '3', '6000@example.com', 'mariareyes', '123', 13, 0, 'default.png'),
(9000, 'test', 'test', 'test', 'BSBIO', '4', 'test@gmail.com', 'test', '123', 15, 0, 'default.png'),
(10000, 'Doe', 'John', 'Michael', 'BSIT', '1', 'john.doe@example.com', 'johndoe', '123', 29, 0, 'default.png'),
(11000, 'Specter', 'Mark', 'y', 'BSME', '2', 'mark@gmail.com', 'mark123', '123', 15, 0, 'default.png'),
(20948048, 'Singco', 'Nathaniel Ron', 'M.', 'BSIT', '3', 'nathanielron09655524395@gmail.com', 'nrms', '123', 30, 0, 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `points_log`
--

CREATE TABLE `points_log` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
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
(33, '3000', 'Java Programming', '526', 'inactive', '2025-04-01 12:20:16'),
(34, '10000', 'Digital Logic & Design', '542', 'inactive', '2025-04-15 13:47:33'),
(35, '11000', 'System Integration and Architecture', '526', 'active', '2025-04-15 13:47:52'),
(36, '2000', 'Project Management', '524', 'active', '2025-04-15 13:48:10'),
(37, '6000', 'Technopreneurship', '544', 'inactive', '2025-04-15 13:48:20'),
(38, '4000', 'Python', '528', 'active', '2025-04-15 13:48:44'),
(39, '6000', 'Capstone', '517', 'inactive', '2025-04-15 17:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `sitindata`
--

CREATE TABLE `sitindata` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `feedback` text DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

INSERT INTO `sitin_report` (`id`, `id_number`, `purpose`, `lab`, `login_time`, `logout_time`, `duration`, `status`, `feedback`, `feedback_date`) VALUES
(151, '1000', 'C Programming', '524', '2025-04-01 09:45:29', '2025-04-01 10:01:43', NULL, 'active', 'testing 123 hihihi&#13;&#10;', '2025-04-15 11:20:15'),
(152, '2000', 'C Programming', '524', '2025-04-01 10:03:22', '2025-04-01 11:40:11', NULL, 'active', NULL, NULL),
(153, '3000', 'Java Programming', '526', '2025-04-01 12:20:30', '2025-04-01 12:20:30', NULL, 'active', NULL, NULL),
(154, '6000', 'Technopreneurship', '544', '2025-04-15 16:57:21', '2025-04-15 16:57:21', NULL, 'active', 'The air was so init~~, me was dying~~', NULL),
(155, '6000', 'Technopreneurship', '544', '2025-04-15 17:31:58', '2025-04-15 17:31:58', NULL, 'active', 'cold', NULL),
(156, '6000', 'Capstone', '517', '2025-04-15 17:31:58', '2025-04-15 17:31:58', NULL, 'active', 'hott&#13;&#10;', NULL),
(158, '10000', 'Digital Logic & Design', '542', '2025-04-15 17:32:01', '2025-04-15 17:32:01', NULL, 'active', NULL, NULL);

--
-- Triggers `sitin_report`
--
DELIMITER $$
CREATE TRIGGER `trg_update_sit_in_duration` BEFORE UPDATE ON `sitin_report` FOR EACH ROW BEGIN
    IF NEW.logout_time IS NOT NULL AND OLD.logout_time IS NULL THEN
        SET NEW.duration = TIMESTAMPDIFF(MINUTE, NEW.login_time, NEW.logout_time);
    END IF;
END
$$
DELIMITER ;

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
-- Stand-in structure for view `v_active_sitins`
-- (See below for the actual view)
--
CREATE TABLE `v_active_sitins` (
`id` int(11)
,`id_number` varchar(255)
,`purpose` varchar(255)
,`lab` varchar(255)
,`status` enum('active','inactive')
,`login_time` timestamp
,`first_name` varchar(255)
,`last_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_daily_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_daily_stats` (
`date` date
,`total_sitins` bigint(21)
,`active_users` bigint(21)
,`most_used_lab` varchar(255)
,`most_used_purpose` varchar(255)
,`avg_duration` decimal(24,4)
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_sitins`
--
DROP TABLE IF EXISTS `v_active_sitins`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_sitins`  AS SELECT `s`.`id` AS `id`, `s`.`id_number` AS `id_number`, `s`.`purpose` AS `purpose`, `s`.`lab` AS `lab`, `s`.`status` AS `status`, `s`.`login_time` AS `login_time`, `i`.`first_name` AS `first_name`, `i`.`last_name` AS `last_name` FROM (`sitin` `s` join `info` `i` on(`s`.`id_number` = `i`.`id_number`)) WHERE `s`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `v_daily_stats`
--
DROP TABLE IF EXISTS `v_daily_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_daily_stats`  AS SELECT cast(`subquery`.`login_time` as date) AS `date`, count(0) AS `total_sitins`, count(distinct `subquery`.`id_number`) AS `active_users`, max(case when `subquery`.`lab_count` = `subquery`.`max_lab_count` then `subquery`.`lab` else NULL end) AS `most_used_lab`, max(case when `subquery`.`purpose_count` = `subquery`.`max_purpose_count` then `subquery`.`purpose` else NULL end) AS `most_used_purpose`, avg(timestampdiff(MINUTE,`subquery`.`login_time`,`subquery`.`logout_time`)) AS `avg_duration` FROM (select `s`.`id` AS `id`,`s`.`id_number` AS `id_number`,`s`.`purpose` AS `purpose`,`s`.`lab` AS `lab`,`s`.`login_time` AS `login_time`,`s`.`logout_time` AS `logout_time`,`s`.`duration` AS `duration`,`s`.`status` AS `status`,`s`.`feedback` AS `feedback`,`s`.`feedback_date` AS `feedback_date`,count(0) over ( partition by cast(`s`.`login_time` as date),`s`.`lab`) AS `lab_count`,max(count(0)) over ( partition by cast(`s`.`login_time` as date)) AS `max_lab_count`,count(0) over ( partition by cast(`s`.`login_time` as date),`s`.`purpose`) AS `purpose_count`,max(count(0)) over ( partition by cast(`s`.`login_time` as date)) AS `max_purpose_count` from `sitin_report` `s`) AS `subquery` GROUP BY cast(`subquery`.`login_time` as date) ;

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
-- Indexes for table `sitindata`
--
ALTER TABLE `sitindata`
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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `sitindata`
--
ALTER TABLE `sitindata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin_report`
--
ALTER TABLE `sitin_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
