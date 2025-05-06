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