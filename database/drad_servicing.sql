-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 13, 2025 at 12:28 AM
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
-- Database: `drad_servicing`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_confirm_date` timestamp NULL DEFAULT NULL,
  `confirmed_by_admin` int(11) DEFAULT NULL,
  `remittance_status` enum('not_remitted','remitted','verified') DEFAULT 'not_remitted',
  `remittance_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `estimated_duration` varchar(50) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `base_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `category`, `estimated_duration`, `status`, `base_price`) VALUES
(1, 'Computer Diagnostic & Repair', 'Complete computer troubleshooting including hardware diagnostics, software issues, virus removal, and system optimization.', 'Hardware/Software', '2-3 hours', 'available', 1500.00),
(2, 'Basic Network Configuration', 'Router setup, WiFi configuration, network security setup, and basic LAN troubleshooting.', 'Networking', '2-4 hours', 'available', 2000.00),
(3, 'IT Consultation & Troubleshooting', 'Expert advice and troubleshooting for specific IT issues, problem diagnosis, and solution recommendations.', 'Consultation', '1-2 hours', 'available', 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `contact_address` varchar(255) DEFAULT NULL,
  `landmark` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `problem_description` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_date` timestamp NULL DEFAULT NULL,
  `started_date` timestamp NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `payment_status` enum('pending','paid','confirmed','disputed') DEFAULT 'pending',
  `payment_notes` text DEFAULT NULL,
  `technician_assigned_date` timestamp NULL DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash_in_person'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`request_id`, `customer_id`, `service_id`, `technician_id`, `first_name`, `last_name`, `middle_name`, `phone`, `address`, `contact_address`, `landmark`, `category`, `problem_description`, `preferred_date`, `preferred_time`, `status`, `total_amount`, `request_date`, `assigned_date`, `started_date`, `completed_date`, `payment_status`, `payment_notes`, `technician_assigned_date`, `scheduled_date`, `scheduled_time`, `payment_method`) VALUES
(5, 2, 1, NULL, 'Junwel', 'Diva', '', '09954634895', 'Purok 5, Nangka, Consolacion, Cebu', '', 'near basketball court', 'Computer Repair', 'computer automatically shut down', NULL, NULL, 'cancelled', 1500.00, '2025-12-12 14:20:45', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 'cash_in_person');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_numbers`
--

CREATE TABLE `ticket_numbers` (
  `ticket_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `ticket_code` varchar(20) NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_numbers`
--

INSERT INTO `ticket_numbers` (`ticket_id`, `request_id`, `ticket_code`, `generated_date`) VALUES
(5, 5, 'DRAD-20251212-0005', '2025-12-12 14:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('customer','technician','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `is_available` tinyint(1) DEFAULT 1,
  `current_request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `bio`, `role`, `created_at`, `status`, `is_available`, `current_request_id`) VALUES
(2, 'jeonwel', '$2y$10$fx4Nge1KSid5id9vugniPOfCSN59VnVv8ivMWarlic8pP8QLQKzrW', 'junweldiva538@gmail.com', 'Junwel Diva', '09954634895', 'Purok 5, Nangka, Consolacion, Cebu', NULL, 'customer', '2025-12-12 13:20:11', 'active', 1, NULL),
(8, 'tech_diva', '$2y$10$d.mwkXcHItIxl1MRvk7EdejbYQWsM.mbJJDjyi2pUTfNfF08ycTY.', 'tech_diva@dradservicing.com', 'Junwel Diva', '09123456789', '123 Tech Street, Consolacion Cebu', NULL, 'technician', '2025-12-12 17:02:54', 'active', 1, NULL),
(9, 'tech_apay', '$2y$10$pe1pR8JcjziPrnE86mKlze6.W8.3WZ3hm0cgjNMQlqo0s8vZlJLTK', 'tech_apay@dradservicing.com', 'John Paul Apay', '09123456789', '123 Tech Street, Cebu City', NULL, 'technician', '2025-12-12 17:06:09', 'active', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `confirmed_by_admin` (`confirmed_by_admin`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`request_date`);

--
-- Indexes for table `ticket_numbers`
--
ALTER TABLE `ticket_numbers`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_ticket_code` (`ticket_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `current_request_id` (`current_request_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ticket_numbers`
--
ALTER TABLE `ticket_numbers`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`confirmed_by_admin`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_numbers`
--
ALTER TABLE `ticket_numbers`
  ADD CONSTRAINT `ticket_numbers_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`current_request_id`) REFERENCES `service_requests` (`request_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
