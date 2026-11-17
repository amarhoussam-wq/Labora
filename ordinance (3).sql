-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2025 at 05:17 PM
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
-- Database: `ordinance`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `number` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `name`, `email`, `number`, `date`, `time`, `status`) VALUES
(1, 'Mira Collins', 'mira@demo.local', '5550102', '2026-06-10', '09:30:00', 'Pending'),
(2, 'Arben Krasniqi', 'arben@demo.local', '5550103', '2026-06-11', '11:00:00', 'Pending'),
(3, 'Elira Morina', 'elira@demo.local', '5550104', '2026-06-12', '14:15:00', 'Done'),
(4, 'Nora Selimi', 'nora@demo.local', '5550105', '2026-06-13', '10:45:00', 'Pending'),
(5, 'Driton Aliti', 'driton@demo.local', '5550106', '2026-06-14', '13:00:00', 'Done');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `number` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `lastname`, `number`, `username`, `email`, `password`) VALUES
(1, 'bleron', 'hyseni', '071354953', 'bleronny', 'bleron@demo.local', '$2y$10$OTyp5Ba0O5YRWedKywIzMef5dgDOkH0EWEpaygnZAIVeXD8ENf6oe'),
(2, 'duro', 'aliti', '076600008', 'duro', 'duro@demo.local', '$2y$10$ZNNfvshKeUJ/wqHyt6bSIu3GC3chxcTsS.arZT.AmDpO8UqsSaw36'),
(3, 'ana', 'karenina', '071569134', 'Ana', 'ana@demo.local', '$2y$10$HMvTfTkjQuaWhPLuoBLm2OmjDz7qqnN1PVAWMX0.GserkP3z8pNOy'),
(4, 'sara', 'fazliu', '072643591', 'Sara', 'sara@demo.local', '$2y$10$yQM88ChB9lYMfNSSdpqGae0lcB8yKsGb3ScWb8sTukstrI.6t0z5W'),
(5, 'Demo', 'Doctor', '5550101', 'demo_doctor', 'doctor@demo.local', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `name`, `lastname`, `username`, `diagnosis`, `password`) VALUES
(1, 'Mira', 'Collins', 'mira', 'Hypertension monitoring', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi'),
(2, 'Arben', 'Krasniqi', 'arben', 'Cardiology follow-up', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi'),
(3, 'Elira', 'Morina', 'elira', 'Pediatric consultation', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi'),
(4, 'Nora', 'Selimi', 'nora', 'Neurology review', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi'),
(5, 'Demo', 'Patient', 'demo_patient', 'General checkup', '$2y$10$X782bI.ARiQe0oNSu0Txf.LO5JOlqfELMc/ecA5zTpRS77niKfNQi');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `name`, `email`, `message`) VALUES
(1, 'Mira Collins', 'mira@demo.local', 'The booking process was clear and the visit felt organized.'),
(2, 'Arben Krasniqi', 'arben@demo.local', 'The doctor explained my follow-up plan clearly.'),
(3, 'Elira Morina', 'elira@demo.local', 'A polished and reassuring patient experience.');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `visitdate` date NOT NULL,
  `anamnesis` varchar(255) NOT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `treatment` varchar(255) NOT NULL,
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `visitdate`, `anamnesis`, `diagnosis`, `treatment`, `patient_id`) VALUES
(1, '2026-05-20', 'Routine blood pressure review', 'Hypertension monitoring', 'Continue current plan and review in 30 days', 1),
(2, '2026-05-28', 'Intermittent palpitations', 'Cardiology follow-up', 'ECG review and lifestyle guidance', 2),
(3, '2026-06-01', 'Routine portfolio demo visit', 'General checkup', 'Follow-up if symptoms change', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
