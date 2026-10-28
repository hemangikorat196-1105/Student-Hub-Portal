-- ============================================================================
-- Student Hub Portal - Events Table Definition & Data
-- ============================================================================
USE `student_portal`;

CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Technical',
  `event_date` DATE NOT NULL,
  `time` VARCHAR(50) NOT NULL DEFAULT '10:00 AM',
  `venue` VARCHAR(200) NOT NULL,
  `speaker` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `seats` INT NOT NULL DEFAULT 100,
  `poster` VARCHAR(255) NULL,
  `status` ENUM('open', 'closed', 'upcoming', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_events_user` FOREIGN KEY (`created_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_events_category` (`category`),
  INDEX `idx_events_date` (`event_date`),
  INDEX `idx_events_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
