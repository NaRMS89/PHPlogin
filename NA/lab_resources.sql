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

-- Insert some sample data
INSERT INTO `lab_resources` (`title`, `type`, `description`, `quantity`, `lab_room`, `status`) VALUES
('Computer', 'equipment', 'Desktop PC with Windows 10', 20, '524', 'available'),
('Printer', 'equipment', 'HP LaserJet Pro', 2, '526', 'available'),
('Projector', 'equipment', 'Epson HD Projector', 1, '528', 'available'),
('Microscope', 'equipment', 'Digital Microscope', 5, '530', 'available'),
('Network Switch', 'equipment', '24-port Gigabit Switch', 1, '542', 'available'),
('iMac', 'equipment', 'Apple iMac 27"', 15, 'Mac Lab', 'available'); 