-- ============================================================================
-- Student Hub Portal - Students Table Definition & Data
-- ============================================================================
USE `student_portal`;

CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `student_id` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `mobile` VARCHAR(15) NOT NULL,
  `course` VARCHAR(100) NOT NULL,
  `year` INT NOT NULL,
  `department` VARCHAR(100) NOT NULL DEFAULT 'Computer Science & Engineering',
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Other',
  `cgpa` DECIMAL(3,2) NOT NULL DEFAULT 8.00,
  `country` VARCHAR(50) NOT NULL DEFAULT 'India',
  `state` VARCHAR(50) NOT NULL DEFAULT 'Gujarat',
  `city` VARCHAR(50) NOT NULL DEFAULT 'Ahmedabad',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_students_course` (`course`),
  INDEX `idx_students_year` (`year`),
  INDEX `idx_students_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
