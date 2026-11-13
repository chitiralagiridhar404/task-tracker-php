-- =====================================================================
-- Task Tracker Database Schema & Seed Data
-- Designed for Core PHP 8 + MySQL/MariaDB (XAMPP Environment)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `task_tracker`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `task_tracker`;

-- ---------------------------------------------------------------------
-- Table: users
-- Stores registered users with roles and securely hashed passwords.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: tasks
-- Stores task records owned by users. Cascade delete ensures cleanup.
-- ---------------------------------------------------------------------
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
  `priority` ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
  `due_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasks_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Required indexes for efficient user task lookups and status filtering
CREATE INDEX `idx_tasks_user_id` ON `tasks` (`user_id`);
CREATE INDEX `idx_tasks_status` ON `tasks` (`status`);

-- ---------------------------------------------------------------------
-- Sample Data: Users
-- Demo plain passwords are documented strictly in README.md:
--   - admin@example.com : Admin@123 (role: admin)
--   - john@example.com  : User1@123 (role: user)
--   - jane@example.com  : User2@123 (role: user)
-- ---------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`) VALUES
(1, 'System Administrator', 'admin@example.com', '$2y$10$mhIFLxlfODkOMG/TCCYiOO6jADCxjpykfWugpqctcHXanyrzLEgrC', 'admin'),
(2, 'John Doe',             'john@example.com',  '$2y$10$ElVpWO24Z0weNilN9dxB0ujBbSM8T.ISbfthYP/8WGp7aoi82hfsC', 'user'),
(3, 'Jane Smith',            'jane@example.com',  '$2y$10$QTcXg5iiKgXGK2LJ30JXGe4m1rRSKt5WwyJ1sv/s/Rry8UiGohAi.', 'user');

-- ---------------------------------------------------------------------
-- Sample Data: Tasks
-- Includes various statuses, priorities, and ownership for testing.
-- ---------------------------------------------------------------------
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`) VALUES
-- Tasks belonging to John Doe (user_id = 2)
(1, 2, 'Design database schema', 'Prepare normalized tables for users and tasks with indexes.', 'Completed', 'High', '2026-11-15'),
(2, 2, 'Build login and session authentication', 'Implement password_verify and session regeneration for secure user login.', 'In Progress', 'High', '2026-11-18'),
(3, 2, 'Implement search and status filter', 'Write prepared statements using LIKE ? to filter tasks dynamically.', 'Pending', 'Medium', '2026-11-20'),
(4, 2, 'Style dashboard using Bootstrap 5', 'Use responsive cards, badges, and clean tables for high readability.', 'Pending', 'Low', '2026-11-25'),

-- Tasks belonging to Jane Smith (user_id = 3)
(5, 3, 'Audit CSRF protection', 'Verify hidden tokens and hash_equals() checks on all POST forms.', 'In Progress', 'High', '2026-11-16'),
(6, 3, 'Test URL tampering vulnerabilities', 'Attempt accessing ID 1 and ID 2 while logged in as Jane to ensure 403 blocks.', 'Pending', 'High', '2026-11-17'),
(7, 3, 'Create user manual and walkthrough', 'Document setup steps and test cases for technical review.', 'Completed', 'Medium', '2026-11-14'),

-- Tasks belonging to Admin (user_id = 1)
(8, 1, 'Review system architecture and security guards', 'Ensure all SQL queries are prepared and output is escaped with e().', 'In Progress', 'High', '2026-11-19'),
(9, 1, 'Prepare intern technical evaluation report', 'Evaluate code readability, security best practices, and completeness.', 'Pending', 'Medium', '2026-11-22');
