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