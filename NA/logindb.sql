-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2025 at 08:41 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_lab_stats` (IN `p_lab` VARCHAR(50))   BEGIN
    SELECT 
        COUNT(*) as total_sitins,
        COUNT(DISTINCT id_number) as unique_students,
        AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as avg_duration,
        COUNT(DISTINCT purpose) as purposes_used
    FROM sitin_report
    WHERE lab = p_lab;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_student_stats` (IN `p_id_number` VARCHAR(20))   BEGIN
    SELECT 
        COUNT(*) as total_sitins,
        AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) as avg_duration,
        COUNT(DISTINCT lab) as labs_used,
        COUNT(DISTINCT purpose) as purposes_used
    FROM sitin_report
    WHERE id_number = p_id_number;
END$$

DELIMITER ;

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

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sit_in_id` int(11) NOT NULL,
  `feedback_text` text,
  `feedback_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sit_in_id` (`sit_in_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`sit_in_id`) REFERENCES `sitin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT INTO `info` (`id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `username`, `password`, `sessions`, `points`, `total_points`, `profile_picture`) VALUES
(123, '1231', '23', '123', 'BSPSY', '1', '213@gmail', '123', '123', 15, 0, 0, 'default.png'),
(1000, 'Santos', 'Maria', '123', 'BSBA', '2', '1000@gmail.com', 'msantos1001', '123', 11, 3, 3, 'pngfind.com-weights-png-1091286.png'),
(2000, 'Reyes', 'Maria', 'Dela', 'BSCS', '1', '2000@gmail.com', 'jreyes1002', '123', 29, 1, 1, 'default.png'),
(3000, 'Lim', 'Anna', 'Tan', 'BSIT', '3', '3000@gmail.com', 'alim1003', '123', 29, 0, 0, '4OTdtjem_F_thumb_1200x630.jpg'),
(4000, 'Cruz', 'Pedro', 'Reyes', 'BSME', '2', '4000@gmail.com', 'pcruz1004', '123', 14, 0, 0, 'default.png'),
(5000, ' Dela Cruz', 'Juan', 'Santos', 'BSIT', '2', '5000@gmail.com', 'juandc', '123', 30, 0, 0, 'default.png'),
(6000, 'Reyes', 'Maria', 'Lourdes', 'BSECE', '3', '6000@example.com', 'mariareyes', '123', 13, 0, 0, 'default.png'),
(9000, 'test', 'test', 'test', 'BSBIO', '4', 'test@gmail.com', 'test', '123', 15, 0, 0, 'default.png'),
(10000, 'Doe', 'John', 'Michael', 'BSIT', '1', 'john.doe@example.com', 'johndoe', '123', 29, 0, 0, 'default.png'),
(11000, 'Specter', 'Mark', 'y', 'BSME', '2', 'mark@gmail.com', 'mark123', '123', 14, 1, 1, 'default.png'),
(20948048, 'Singco', 'Nathaniel Ron', 'M.', 'BSIT', '3', 'nathanielron09655524395@gmail.com', 'nrms', '123', 30, 0, 0, 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `lab_computers`
--

CREATE TABLE `lab_computers` (
  `id` int(11) NOT NULL,
  `lab_id` varchar(10) NOT NULL,
  `computer_number` int(11) NOT NULL,
  `status` enum('available','reserved','maintenance') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_computers`
--

INSERT INTO `lab_computers` (`id`, `lab_id`, `computer_number`, `status`) VALUES
(1, '524', 1, 'available'),
(2, '524', 2, 'available'),
(3, '524', 3, 'available'),
(4, '524', 4, 'available'),
(5, '524', 5, 'available'),
(6, '524', 6, 'available'),
(7, '524', 7, 'available'),
(8, '524', 8, 'available'),
(9, '524', 9, 'available'),
(10, '524', 10, 'available'),
(11, '524', 11, 'available'),
(12, '524', 12, 'available'),
(13, '524', 13, 'available'),
(14, '524', 14, 'available'),
(15, '524', 15, 'available'),
(16, '524', 16, 'available'),
(17, '524', 17, 'available'),
(18, '524', 18, 'available'),
(19, '524', 19, 'available'),
(20, '524', 20, 'available'),
(21, '524', 21, 'available'),
(22, '524', 22, 'available'),
(23, '524', 23, 'available'),
(24, '524', 24, 'available'),
(25, '524', 25, 'available'),
(26, '524', 26, 'available'),
(27, '524', 27, 'available'),
(28, '524', 28, 'available'),
(29, '524', 29, 'available'),
(30, '524', 30, 'available'),
(31, '524', 31, 'available'),
(32, '524', 32, 'available'),
(33, '524', 33, 'available'),
(34, '524', 34, 'available'),
(35, '524', 35, 'available'),
(36, '524', 36, 'available'),
(37, '524', 37, 'available'),
(38, '524', 38, 'available'),
(39, '524', 39, 'available'),
(40, '524', 40, 'available'),
(41, '524', 41, 'available'),
(42, '524', 42, 'available'),
(43, '524', 43, 'available'),
(44, '524', 44, 'available'),
(45, '524', 45, 'available'),
(46, '524', 46, 'available'),
(47, '524', 47, 'available'),
(48, '524', 48, 'available'),
(49, '524', 49, 'available'),
(50, '524', 50, 'available'),
(51, '526', 1, 'available'),
(52, '526', 2, 'available'),
(53, '526', 3, 'available'),
(54, '526', 4, 'available'),
(55, '526', 5, 'available'),
(56, '526', 6, 'available'),
(57, '526', 7, 'available'),
(58, '526', 8, 'available'),
(59, '526', 9, 'available'),
(60, '526', 10, 'available'),
(61, '526', 11, 'available'),
(62, '526', 12, 'available'),
(63, '526', 13, 'available'),
(64, '526', 14, 'available'),
(65, '526', 15, 'available'),
(66, '526', 16, 'available'),
(67, '526', 17, 'available'),
(68, '526', 18, 'available'),
(69, '526', 19, 'available'),
(70, '526', 20, 'available'),
(71, '526', 21, 'available'),
(72, '526', 22, 'available'),
(73, '526', 23, 'available'),
(74, '526', 24, 'available'),
(75, '526', 25, 'available'),
(76, '526', 26, 'available'),
(77, '526', 27, 'available'),
(78, '526', 28, 'available'),
(79, '526', 29, 'available'),
(80, '526', 30, 'available'),
(81, '526', 31, 'available'),
(82, '526', 32, 'available'),
(83, '526', 33, 'available'),
(84, '526', 34, 'available'),
(85, '526', 35, 'available'),
(86, '526', 36, 'available'),
(87, '526', 37, 'available'),
(88, '526', 38, 'available'),
(89, '526', 39, 'available'),
(90, '526', 40, 'available'),
(91, '526', 41, 'available'),
(92, '526', 42, 'available'),
(93, '526', 43, 'available'),
(94, '526', 44, 'available'),
(95, '526', 45, 'available'),
(96, '526', 46, 'available'),
(97, '526', 47, 'available'),
(98, '526', 48, 'available'),
(99, '526', 49, 'available'),
(100, '526', 50, 'available'),
(101, '528', 1, 'available'),
(102, '528', 2, 'available'),
(103, '528', 3, 'available'),
(104, '528', 4, 'available'),
(105, '528', 5, 'available'),
(106, '528', 6, 'available'),
(107, '528', 7, 'available'),
(108, '528', 8, 'available'),
(109, '528', 9, 'available'),
(110, '528', 10, 'available'),
(111, '528', 11, 'available'),
(112, '528', 12, 'available'),
(113, '528', 13, 'available'),
(114, '528', 14, 'available'),
(115, '528', 15, 'available'),
(116, '528', 16, 'available'),
(117, '528', 17, 'available'),
(118, '528', 18, 'available'),
(119, '528', 19, 'available'),
(120, '528', 20, 'available'),
(121, '528', 21, 'available'),
(122, '528', 22, 'available'),
(123, '528', 23, 'available'),
(124, '528', 24, 'available'),
(125, '528', 25, 'available'),
(126, '528', 26, 'available'),
(127, '528', 27, 'available'),
(128, '528', 28, 'available'),
(129, '528', 29, 'available'),
(130, '528', 30, 'available'),
(131, '528', 31, 'available'),
(132, '528', 32, 'available'),
(133, '528', 33, 'available'),
(134, '528', 34, 'available'),
(135, '528', 35, 'available'),
(136, '528', 36, 'available'),
(137, '528', 37, 'available'),
(138, '528', 38, 'available'),
(139, '528', 39, 'available'),
(140, '528', 40, 'available'),
(141, '528', 41, 'available'),
(142, '528', 42, 'available'),
(143, '528', 43, 'available'),
(144, '528', 44, 'available'),
(145, '528', 45, 'available'),
(146, '528', 46, 'available'),
(147, '528', 47, 'available'),
(148, '528', 48, 'available'),
(149, '528', 49, 'available'),
(150, '528', 50, 'available'),
(151, '530', 1, 'available'),
(152, '530', 2, 'available'),
(153, '530', 3, 'available'),
(154, '530', 4, 'available'),
(155, '530', 5, 'available'),
(156, '530', 6, 'available'),
(157, '530', 7, 'available'),
(158, '530', 8, 'available'),
(159, '530', 9, 'available'),
(160, '530', 10, 'available'),
(161, '530', 11, 'available'),
(162, '530', 12, 'available'),
(163, '530', 13, 'available'),
(164, '530', 14, 'available'),
(165, '530', 15, 'available'),
(166, '530', 16, 'available'),
(167, '530', 17, 'available'),
(168, '530', 18, 'available'),
(169, '530', 19, 'available'),
(170, '530', 20, 'available'),
(171, '530', 21, 'available'),
(172, '530', 22, 'available'),
(173, '530', 23, 'available'),
(174, '530', 24, 'available'),
(175, '530', 25, 'available'),
(176, '530', 26, 'available'),
(177, '530', 27, 'available'),
(178, '530', 28, 'available'),
(179, '530', 29, 'available'),
(180, '530', 30, 'available'),
(181, '530', 31, 'available'),
(182, '530', 32, 'available'),
(183, '530', 33, 'available'),
(184, '530', 34, 'available'),
(185, '530', 35, 'available'),
(186, '530', 36, 'available'),
(187, '530', 37, 'available'),
(188, '530', 38, 'available'),
(189, '530', 39, 'available'),
(190, '530', 40, 'available'),
(191, '530', 41, 'available'),
(192, '530', 42, 'available'),
(193, '530', 43, 'available'),
(194, '530', 44, 'available'),
(195, '530', 45, 'available'),
(196, '530', 46, 'available'),
(197, '530', 47, 'available'),
(198, '530', 48, 'available'),
(199, '530', 49, 'available'),
(200, '530', 50, 'available'),
(201, '542', 1, 'available'),
(202, '542', 2, 'available'),
(203, '542', 3, 'available'),
(204, '542', 4, 'available'),
(205, '542', 5, 'available'),
(206, '542', 6, 'available'),
(207, '542', 7, 'available'),
(208, '542', 8, 'available'),
(209, '542', 9, 'available'),
(210, '542', 10, 'available'),
(211, '542', 11, 'available'),
(212, '542', 12, 'available'),
(213, '542', 13, 'available'),
(214, '542', 14, 'available'),
(215, '542', 15, 'available'),
(216, '542', 16, 'available'),
(217, '542', 17, 'available'),
(218, '542', 18, 'available'),
(219, '542', 19, 'available'),
(220, '542', 20, 'available'),
(221, '542', 21, 'available'),
(222, '542', 22, 'available'),
(223, '542', 23, 'available'),
(224, '542', 24, 'available'),
(225, '542', 25, 'available'),
(226, '542', 26, 'available'),
(227, '542', 27, 'available'),
(228, '542', 28, 'available'),
(229, '542', 29, 'available'),
(230, '542', 30, 'available'),
(231, '542', 31, 'available'),
(232, '542', 32, 'available'),
(233, '542', 33, 'available'),
(234, '542', 34, 'available'),
(235, '542', 35, 'available'),
(236, '542', 36, 'available'),
(237, '542', 37, 'available'),
(238, '542', 38, 'available'),
(239, '542', 39, 'available'),
(240, '542', 40, 'available'),
(241, '542', 41, 'available'),
(242, '542', 42, 'available'),
(243, '542', 43, 'available'),
(244, '542', 44, 'available'),
(245, '542', 45, 'available'),
(246, '542', 46, 'available'),
(247, '542', 47, 'available'),
(248, '542', 48, 'available'),
(249, '542', 49, 'available'),
(250, '542', 50, 'available'),
(251, '544', 1, 'available'),
(252, '544', 2, 'available'),
(253, '544', 3, 'available'),
(254, '544', 4, 'available'),
(255, '544', 5, 'available'),
(256, '544', 6, 'available'),
(257, '544', 7, 'available'),
(258, '544', 8, 'available'),
(259, '544', 9, 'available'),
(260, '544', 10, 'available'),
(261, '544', 11, 'available'),
(262, '544', 12, 'available'),
(263, '544', 13, 'available'),
(264, '544', 14, 'available'),
(265, '544', 15, 'available'),
(266, '544', 16, 'available'),
(267, '544', 17, 'available'),
(268, '544', 18, 'available'),
(269, '544', 19, 'available'),
(270, '544', 20, 'available'),
(271, '544', 21, 'available'),
(272, '544', 22, 'available'),
(273, '544', 23, 'available'),
(274, '544', 24, 'available'),
(275, '544', 25, 'available'),
(276, '544', 26, 'available'),
(277, '544', 27, 'available'),
(278, '544', 28, 'available'),
(279, '544', 29, 'available'),
(280, '544', 30, 'available'),
(281, '544', 31, 'available'),
(282, '544', 32, 'available'),
(283, '544', 33, 'available'),
(284, '544', 34, 'available'),
(285, '544', 35, 'available'),
(286, '544', 36, 'available'),
(287, '544', 37, 'available'),
(288, '544', 38, 'available'),
(289, '544', 39, 'available'),
(290, '544', 40, 'available'),
(291, '544', 41, 'available'),
(292, '544', 42, 'available'),
(293, '544', 43, 'available'),
(294, '544', 44, 'available'),
(295, '544', 45, 'available'),
(296, '544', 46, 'available'),
(297, '544', 47, 'available'),
(298, '544', 48, 'available'),
(299, '544', 49, 'available'),
(300, '544', 50, 'available'),
(301, '517', 1, 'available'),
(302, '517', 2, 'available'),
(303, '517', 3, 'available'),
(304, '517', 4, 'available'),
(305, '517', 5, 'available'),
(306, '517', 6, 'available'),
(307, '517', 7, 'available'),
(308, '517', 8, 'available'),
(309, '517', 9, 'available'),
(310, '517', 10, 'available'),
(311, '517', 11, 'available'),
(312, '517', 12, 'available'),
(313, '517', 13, 'available'),
(314, '517', 14, 'available'),
(315, '517', 15, 'available'),
(316, '517', 16, 'available'),
(317, '517', 17, 'available'),
(318, '517', 18, 'available'),
(319, '517', 19, 'available'),
(320, '517', 20, 'available'),
(321, '517', 21, 'available'),
(322, '517', 22, 'available'),
(323, '517', 23, 'available'),
(324, '517', 24, 'available'),
(325, '517', 25, 'available'),
(326, '517', 26, 'available'),
(327, '517', 27, 'available'),
(328, '517', 28, 'available'),
(329, '517', 29, 'available'),
(330, '517', 30, 'available'),
(331, '517', 31, 'available'),
(332, '517', 32, 'available'),
(333, '517', 33, 'available'),
(334, '517', 34, 'available'),
(335, '517', 35, 'available'),
(336, '517', 36, 'available'),
(337, '517', 37, 'available'),
(338, '517', 38, 'available'),
(339, '517', 39, 'available'),
(340, '517', 40, 'available'),
(341, '517', 41, 'available'),
(342, '517', 42, 'available'),
(343, '517', 43, 'available'),
(344, '517', 44, 'available'),
(345, '517', 45, 'available'),
(346, '517', 46, 'available'),
(347, '517', 47, 'available'),
(348, '517', 48, 'available'),
(349, '517', 49, 'available'),
(350, '517', 50, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `lab_resources`
--

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
-- Table structure for table `computers`
--

CREATE TABLE IF NOT EXISTS computers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id VARCHAR(10) NOT NULL,
    pc_number INT NOT NULL,
    status ENUM('available', 'maintenance', 'occupied') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY lab_pc_unique (lab_id, pc_number)
);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id VARCHAR(10) NOT NULL,
    pc_number INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id, pc_number) REFERENCES computers(lab_id, pc_number)
);

--
-- Insert initial computer records for each lab
--

INSERT IGNORE INTO computers (lab_id, pc_number, status)
SELECT lab.id, pc.number, 'available'
FROM (
    SELECT '524' as id UNION ALL
    SELECT '526' UNION ALL
    SELECT '528' UNION ALL
    SELECT '530' UNION ALL
    SELECT '542' UNION ALL
    SELECT '544' UNION ALL
    SELECT '517'
) lab
CROSS JOIN (
    SELECT 1 as number UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL
    SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL
    SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL
    SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL
    SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL
    SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30 UNION ALL
    SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34 UNION ALL SELECT 35 UNION ALL
    SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL SELECT 40 UNION ALL
    SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44 UNION ALL SELECT 45 UNION ALL
    SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49 UNION ALL SELECT 50
) pc;

-- --------------------------------------------------------

--
-- Table structure for table `sitin`
--

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
(158, '10000', 'Digital Logic & Design', '542', '2025-04-15 17:32:01', '2025-04-15 17:32:01', NULL, 'active', NULL, NULL),
(159, '4000', 'Python', '528', '2025-04-26 18:09:29', '2025-04-26 18:09:29', NULL, 'active', NULL, NULL);

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
  ADD KEY `sit_in_id` (`sit_in_id`);

--
-- Indexes for table `info`
--
ALTER TABLE `info`
  ADD PRIMARY KEY (`id_number`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `lab_computers`
--
ALTER TABLE `lab_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_computer_unique` (`lab_id`,`computer_number`);

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
-- AUTO_INCREMENT for table `lab_computers`
--
ALTER TABLE `lab_computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=351;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `sitindata`
--
ALTER TABLE `sitindata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitin_report`
--
ALTER TABLE `sitin_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
