-- ============================================================
-- LABORA Database Schema
-- Multi-Laboratory Appointment System
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `labora` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `labora`;

-- 1. Users Table (Patients, Lab Managers, Admin)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('patient', 'lab_manager', 'admin') NOT NULL DEFAULT 'patient',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo Users (Password: demo123 for all)
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`) VALUES
(1, 'أحمد', 'المخبري', 'lab1@labora.dz', '0550112233', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(2, 'كريم', 'الشفاء', 'lab2@labora.dz', '0550445566', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(3, 'سمير', 'المريض', 'patient@labora.dz', '0661998877', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'patient');

-- 2. Laboratories Table
DROP TABLE IF EXISTS `laboratories`;
CREATE TABLE `laboratories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `manager_id` INT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `laboratories` (`id`, `name`, `city`, `address`, `phone`, `manager_id`, `status`) VALUES
(1, 'مخبر ابن سينا للتحاليل الطبية', 'الجزائر العاصمة', '12 شارع ديدوش مراد', '021634578', 1, 'active'),
(2, 'مخبر الشفاء المركزي', 'وهران', '45 نهج الأمير عبد القادر', '041289012', 2, 'active'),
(3, 'مخبر النور للتحاليل', 'قسنطينة', '08 حي الجمال', '031887766', NULL, 'active');

-- 3. Availability Slots (Date + Time + Max capacity + Current bookings)
DROP TABLE IF EXISTS `availability_slots`;
CREATE TABLE `availability_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `laboratory_id` INT NOT NULL,
  `slot_date` DATE NOT NULL,
  `slot_time` TIME NOT NULL,
  `max_bookings` INT NOT NULL DEFAULT 2,
  `current_bookings` INT NOT NULL DEFAULT 0,
  `status` ENUM('available', 'full', 'blocked') NOT NULL DEFAULT 'available',
  UNIQUE KEY `uniq_lab_slot` (`laboratory_id`, `slot_date`, `slot_time`),
  FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Generate demo slots for upcoming days
INSERT INTO `availability_slots` (`laboratory_id`, `slot_date`, `slot_time`, `max_bookings`, `current_bookings`, `status`) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:30:00', 2, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00', 2, 1, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00', 2, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:30:00', 2, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 2, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:30:00', 2, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:30:00', 2, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 2, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 2, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 2, 0, 'available');

-- 4. Appointments Table
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `laboratory_id` INT NOT NULL,
  `slot_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `analyses_text` TEXT NOT NULL,
  `prescription_file` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `rejection_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`slot_id`) REFERENCES `availability_slots`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo appointment
INSERT INTO `appointments` (`id`, `patient_id`, `laboratory_id`, `slot_id`, `appointment_date`, `appointment_time`, `analyses_text`, `status`) VALUES
(1, 3, 1, 2, CURDATE(), '09:00:00', 'تعداد الدم الكامل (NFS)، السكر التراكمي (HbA1c)', 'pending');

SET FOREIGN_KEY_CHECKS = 1;
