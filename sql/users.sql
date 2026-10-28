-- ============================================================================
-- Student Hub Portal - Users Table Definition & Data
-- ============================================================================
USE `student_portal`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(255) NULL,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed: Admin & Hemangi Korat (Student)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `created_at`)
VALUES
(1, 'admin', 'admin@studenthub.edu', '$2y$10$eA7swyN6XZyunaFlQVFfEO8WvOePG7RO.LEQNNPHBovhTpeIk5o1O', 'admin', 'active', '2026-08-01 09:00:00'),
(2, '25CS035', 'hemangi.korat@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-08-05 10:15:00')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);
