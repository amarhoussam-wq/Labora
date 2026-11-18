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
(1, 'د. أمنية', 'للتشخيص', 'oumnia@labora.dz', '0663352898', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(2, 'د. شربي', 'البيولوجي', 'charby@labora.dz', '029712030', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(3, 'د. بلعيد', 'الطبي', 'belaid@labora.dz', '029715566', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(4, 'د. الرازي', 'المخبري', 'razi@labora.dz', '0696440492', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(5, 'د. لكحل', 'محمد لمين', 'lekhel@labora.dz', '029718899', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(6, 'أحمد', 'المخبري', 'lab1@labora.dz', '0550112233', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(7, 'كريم', 'الشفاء', 'lab2@labora.dz', '0550445566', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'lab_manager'),
(8, 'سمير', 'المريض', 'patient@labora.dz', '0661998877', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi', 'patient');

-- 2. Laboratories Table
DROP TABLE IF EXISTS `laboratories`;
CREATE TABLE `laboratories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `working_hours` VARCHAR(100) DEFAULT '07:30 - 16:30',
  `manager_id` INT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `laboratories` (`id`, `name`, `city`, `address`, `phone`, `working_hours`, `manager_id`, `status`) VALUES
(1, 'مخبر أمنية للتحاليل الطبية — Oumnia Laboratory', 'ورقلة', 'حي رويسات، 30013 ورقلة', '0663 35 28 98', '07:30 - 16:00', 1, 'active'),
(2, 'مخبر تحاليل طبية الدكتور شربي — Dr Charby', 'ورقلة', 'نهج عطوات قدور (بجانب ألفا موبايل)، ورقلة', '029 71 20 30', '07:30 - 17:00', 2, 'active'),
(3, 'مخبر بلعيد للتحاليل الطبية — Laboratoire Belaïd', 'ورقلة', 'شارع سي الحواس، ورقلة', '029 71 55 66', '07:30 - 17:00', 3, 'active'),
(4, 'مخبر الرازي للتحاليل الطبية — Laboratoire El Razi', 'ورقلة', 'حي باحميد (بجانب عيادة د. خالدي)، ورقلة 30000', '0696 44 04 92', '07:30 - 16:00', 4, 'active'),
(5, 'مخبر التحاليل الطبية لكحل محمد لمين — Dr Lekhel', 'ورقلة', 'طريق N49، تقاطع نهج سي الحواس وعطوات قدور، ورقلة', '029 71 88 99', '07:30 - 16:00', 5, 'active');

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
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 3, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:30:00', 3, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 3, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00', 3, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 3, 0, 'available'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:30:00', 3, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 3, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 3, 0, 'available'),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 3, 0, 'available'),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 3, 0, 'available'),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 3, 0, 'available'),
(4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 3, 0, 'available'),
(4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 3, 0, 'available'),
(5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 3, 0, 'available'),
(5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 3, 0, 'available');

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
  `contact_phone` VARCHAR(20) DEFAULT NULL,
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

-- 5. Analyses Table
DROP TABLE IF EXISTS `analyses`;
CREATE TABLE `analyses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name_ar` VARCHAR(150) NOT NULL,
  `name_fr` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `sample_type` VARCHAR(100) DEFAULT 'دم وريدي',
  `fasting_required` TINYINT(1) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `analyses` (`id`, `name_ar`, `name_fr`, `category`, `fasting_required`) VALUES
(1, 'تعداد الدم الكامل', 'NFS - Numération Formule Sanguine', 'أمراض الدم (Hématologie)', 0),
(2, 'سرعة الترسيب', 'VS - Vitesse de Sédimentation', 'أمراض الدم (Hématologie)', 0),
(3, 'فصيلة الدم والريزوس', 'Groupage Sanguin + Rhésus', 'أمراض الدم (Hématologie)', 0),
(4, 'زمن التخثر', 'TP / INR', 'أمراض الدم (Hématologie)', 0),
(5, 'سكر الدم على الريق', 'Glycémie à jeun', 'الكيمياء الحيوية (Biochimie)', 1),
(6, 'السكر التراكمي', 'HbA1c - Hémoglobine Glyquée', 'الكيمياء الحيوية (Biochimie)', 0),
(7, 'الكوليسترول الكلي والدهون', 'Bilan Lipidique (Cholestérol, Triglycérides)', 'الكيمياء الحيوية (Biochimie)', 1),
(8, 'وظائف الكلى (البولة والكراتينين)', 'Urée & Créatinine', 'الكيمياء الحيوية (Biochimie)', 0),
(9, 'وظائف الكبد', 'Bilan Hépatique (ASAT, ALAT, Bilirubine)', 'الكيمياء الحيوية (Biochimie)', 0),
(10, 'حمض البوليك (النقرس)', 'Acide Urique', 'الكيمياء الحيوية (Biochimie)', 0),
(11, 'فحص الأملاح والمعادن', 'Ionogramme Sanguin (Na+, K+, Cl-)', 'الكيمياء الحيوية (Biochimie)', 0),
(12, 'هرمون الغدة الدرقية', 'TSH - Thyréostimuline', 'الهرمونات (Hormonologie)', 0),
(13, 'فيتامين د', 'Vitamine D (25-OH)', 'الهرمونات والفيتامينات', 0),
(14, 'مخزون الحديد', 'Ferritine', 'الهرمونات والفيتامينات', 0),
(15, 'بروتين الالتهاب', 'CRP - Protéine C-Réactive', 'الأمصال والمناعة', 0),
(16, 'فحص وزرع البول', 'ECBU - Examen Cytobactériologique des Urines', 'الميكروبيولوجيا', 0);

-- 6. Laboratory Analyses Table
DROP TABLE IF EXISTS `laboratory_analyses`;
CREATE TABLE `laboratory_analyses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `laboratory_id` INT NOT NULL,
  `analysis_id` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `turnaround_time` VARCHAR(100) DEFAULT 'خلال 24 ساعة',
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  UNIQUE KEY `uniq_lab_analysis` (`laboratory_id`, `analysis_id`),
  FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `laboratory_analyses` (`laboratory_id`, `analysis_id`, `price`) VALUES
(1, 1, 800), (1, 2, 400), (1, 3, 600), (1, 4, 800), (1, 5, 400), (1, 6, 1500), (1, 7, 1400), (1, 8, 1000), (1, 9, 1600), (1, 10, 600), (1, 11, 1200), (1, 12, 1800), (1, 13, 2800), (1, 14, 2000), (1, 15, 800), (1, 16, 1200),
(2, 1, 750), (2, 2, 400), (2, 3, 500), (2, 4, 750), (2, 5, 350), (2, 6, 1400), (2, 7, 1300), (2, 8, 950), (2, 9, 1500), (2, 10, 550), (2, 11, 1100), (2, 12, 1700), (2, 13, 2600), (2, 14, 1900), (2, 15, 750), (2, 16, 1100),
(3, 1, 800), (3, 2, 400), (3, 3, 600), (3, 4, 800), (3, 5, 400), (3, 6, 1500), (3, 7, 1400), (3, 8, 1000), (3, 9, 1600), (3, 10, 600), (3, 11, 1200), (3, 12, 1800), (3, 13, 2800), (3, 14, 2000), (3, 15, 800), (3, 16, 1200),
(4, 1, 800), (4, 2, 400), (4, 3, 600), (4, 4, 800), (4, 5, 400), (4, 6, 1500), (4, 7, 1400), (4, 8, 1000), (4, 9, 1600), (4, 10, 600), (4, 11, 1200), (4, 12, 1800), (4, 13, 2800), (4, 14, 2000), (4, 15, 800), (4, 16, 1200),
(5, 1, 800), (5, 2, 400), (5, 3, 600), (5, 4, 800), (5, 5, 400), (5, 6, 1500), (5, 7, 1400), (5, 8, 1000), (5, 9, 1600), (5, 10, 600), (5, 11, 1200), (5, 12, 1800), (5, 13, 2800), (5, 14, 2000), (5, 15, 800), (5, 16, 1200);

SET FOREIGN_KEY_CHECKS = 1;
