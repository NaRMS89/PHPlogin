-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2025 at 05:33 PM
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
  `profile_picture` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `username`, `password`, `sessions`, `profile_picture`) VALUES
(1000, 'Santos', 'Maria', 'Cruz', 'BSBA', '2', '1000@gmail.com', 'msantos1001', '123', 3, 'default.png'),
(2000, 'Reyes', 'Maria', 'Dela', 'BSCS', '1', '2000@gmail.com', 'jreyes1002', '123', 15, 'default.png'),
(3000, 'Lim', 'Anna', 'Tan', 'BSIT', '3', '3000@gmail.com', 'alim1003', '123', 10, '4OTdtjem_F_thumb_1200x630.jpg'),
(4000, 'Cruz', 'Pedro', 'Reyes', 'BSME', '2', '4000@gmail.com', 'pcruz1004', '123', 15, 'default.png'),
(5000, ' Dela Cruz', 'Juan', 'Santos', 'BSIT', '2', '5000@gmail.com', 'juandc', '123', 15, 'default.png'),
(6000, 'Reyes', 'Maria', 'Lourdes', 'BSECE', '3', '6000@example.com', 'mariareyes', '123', 15, 'default.png'),
(20948048, 'Singco', 'Nathaniel Ron', 'M.', 'BSIT', '3', 'nathanielron09655524395@gmail.com', 'nrms', '123', 8, 'default.png');

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
(1, '1000', 'C Programming', '524', 'inactive', '2025-03-09 08:38:02'),
(2, '1000', 'C Programming', '524', 'inactive', '2025-03-09 08:43:40'),
(3, '3000', 'C# Programming', '528', 'inactive', '2025-03-09 08:43:56'),
(4, '3000', 'C# Programming', '528', 'inactive', '2025-03-09 08:44:27'),
(5, '1000', 'ASP.NET Programming', 'Mac Lab', 'inactive', '2025-03-09 08:44:42'),
(6, '2000', 'C# Programming', '530', 'active', '2025-03-09 08:47:30'),
(7, '20948048', 'PHP Programming', '542', 'inactive', '2025-03-09 08:47:55'),
(8, '3000', 'PHP Programming', '542', 'inactive', '2025-03-09 08:48:12'),
(9, '3000', 'PHP Programming', '542', 'active', '2025-03-09 08:48:19'),
(10, '3000', 'C# Programming', '530', 'active', '2025-03-09 11:25:59'),
(11, '1000', 'C Programming', '524', 'inactive', '2025-03-09 12:23:39'),
(12, '2000', 'ASP.NET Programming', '524', 'active', '2025-03-09 12:23:54'),
(13, '1000', 'C Programming', '524', 'inactive', '2025-03-09 12:51:50'),
(14, '6000', 'PHP Programming', '524', 'active', '2025-03-09 13:05:36'),
(15, '1000', 'ASP.NET Programming', 'Mac Lab', 'inactive', '2025-03-09 13:50:59'),
(16, '1000', 'ASP.NET Programming', 'Mac Lab', 'inactive', '2025-03-09 13:51:11'),
(17, '1000', 'ASP.NET Programming', '524', 'inactive', '2025-03-09 14:06:09'),
(18, '1000', 'C Programming', '524', 'inactive', '2025-03-11 16:23:40');

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
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_report`
--

INSERT INTO `sitin_report` (`id`, `id_number`, `purpose`, `lab`, `login_time`, `logout_time`) VALUES
(1, '1000', 'C Programming', '524', '2025-03-09 08:44:21', '2025-03-09 08:44:21'),
(2, '1000', 'C Programming', '524', '2025-03-09 08:44:21', '2025-03-09 08:44:21'),
(4, '3000', 'C# Programming', '528', '2025-03-09 08:44:22', '2025-03-09 08:44:22'),
(5, '20948048', 'PHP Programming', '542', '2025-03-09 08:47:58', '2025-03-09 08:47:58'),
(6, '3000', 'C# Programming', '528', '2025-03-09 08:48:05', '2025-03-09 08:48:05'),
(7, '3000', 'C# Programming', '528', '2025-03-09 08:48:05', '2025-03-09 08:48:05'),
(9, '3000', 'C# Programming', '528', '2025-03-09 08:48:15', '2025-03-09 08:48:15'),
(10, '3000', 'C# Programming', '528', '2025-03-09 08:48:15', '2025-03-09 08:48:15'),
(11, '3000', 'PHP Programming', '542', '2025-03-09 08:48:15', '2025-03-09 08:48:15'),
(12, '1000', 'C Programming', '524', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(13, '1000', 'C Programming', '524', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(14, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(15, '1000', 'C Programming', '524', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(16, '1000', 'C Programming', '524', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(17, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 13:51:05', '2025-03-09 13:51:05'),
(19, '1000', 'C Programming', '524', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(20, '1000', 'C Programming', '524', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(21, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(22, '1000', 'C Programming', '524', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(23, '1000', 'C Programming', '524', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(24, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(25, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:05:49', '2025-03-09 14:05:49'),
(26, '1000', 'C Programming', '524', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(27, '1000', 'C Programming', '524', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(28, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(29, '1000', 'C Programming', '524', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(30, '1000', 'C Programming', '524', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(31, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(32, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(33, '1000', 'ASP.NET Programming', '524', '2025-03-09 14:06:12', '2025-03-09 14:06:12'),
(34, '1000', 'C Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(35, '1000', 'C Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(36, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(37, '1000', 'C Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(38, '1000', 'C Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(39, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(40, '1000', 'ASP.NET Programming', 'Mac Lab', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(41, '1000', 'ASP.NET Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45'),
(42, '1000', 'C Programming', '524', '2025-03-11 16:23:45', '2025-03-11 16:23:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `info`
--
ALTER TABLE `info`
  ADD PRIMARY KEY (`id_number`),
  ADD UNIQUE KEY `username` (`username`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
  MODIFY `id_number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20948054;

--
-- AUTO_INCREMENT for table `sitin`
--
ALTER TABLE `sitin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sitin_report`
--
ALTER TABLE `sitin_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
    `announcement_id` INT AUTO_INCREMENT PRIMARY KEY,
    `announcement_text` TEXT NOT NULL,
    `date_posted` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
