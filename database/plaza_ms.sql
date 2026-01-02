-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 08:30 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plaza_ms`
--

-- --------------------------------------------------------

--
-- Table structure for table `agreements`
--

CREATE TABLE `agreements` (
  `agreement_id` int(11) NOT NULL,
  `agreement_number` varchar(50) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `space_type` enum('shop','room','basement') NOT NULL,
  `space_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT 0.00,
  `terms` text DEFAULT NULL,
  `status` enum('active','expired','terminated','renewed') DEFAULT 'active',
  `document_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agreements`
--

INSERT INTO `agreements` (`agreement_id`, `agreement_number`, `tenant_id`, `space_type`, `space_id`, `start_date`, `end_date`, `monthly_rent`, `security_deposit`, `terms`, `status`, `document_file`, `created_at`, `updated_at`, `customer_id`) VALUES
(4, 'AGR-S-20251223-8834', 0, 'shop', 1, '0000-00-00', '0000-00-00', 11.00, 0.00, '', 'terminated', NULL, '2025-12-23 10:49:32', '2025-12-23 11:44:36', 1),
(5, 'AGR-S-20251224-3305', 0, 'shop', 1, '0000-00-00', '0000-00-00', 5000.00, 1200.00, '', 'terminated', NULL, '2025-12-24 04:57:27', '2025-12-24 04:58:14', 1),
(6, 'AGR-S-20251224-7131', 0, 'shop', 1, '0000-00-00', '0000-00-00', 5000.00, 1000.00, '', 'terminated', NULL, '2025-12-24 05:35:06', '2025-12-24 05:37:19', 1),
(7, 'AGR-S-20251224-7126', 0, 'shop', 1, '2025-12-18', '2025-12-25', 50000.00, 1500.00, '', 'terminated', NULL, '2025-12-24 05:37:37', '2025-12-24 05:55:50', 1),
(8, 'AGR-S-20251224-9414', 0, 'shop', 1, '0000-00-00', '0000-00-00', 50000.00, 0.00, '', 'terminated', NULL, '2025-12-24 06:16:20', '2025-12-24 06:37:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `basements`
--

CREATE TABLE `basements` (
  `basement_id` int(11) NOT NULL,
  `basement_number` varchar(20) NOT NULL,
  `basement_name` varchar(100) DEFAULT NULL,
  `area_sqft` decimal(10,2) DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `space_type` enum('parking','storage','other') DEFAULT 'parking',
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `description` text DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `cnic` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Pakistan',
  `occupation` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `reference_name` varchar(100) DEFAULT NULL,
  `reference_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `full_name`, `gender`, `email`, `phone`, `alternate_phone`, `cnic`, `address`, `city`, `country`, `occupation`, `emergency_contact_name`, `emergency_contact_phone`, `reference_name`, `reference_phone`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'MUHAMMAD WAIM', 'male', 'muhammadwasim.cusit@gmail.com', '03342372772', '03257627554', '14101-1346079-7', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayatabad\r\nDoctor Guest House, Hayatabad Phase 4, Peshawar, Pakistan', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayata', 'Pakistan', '', '', '', '', '', 'active', '', '2025-12-23 10:45:40', '2025-12-23 10:45:40');

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `ledger_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `agreement_id` int(11) DEFAULT NULL,
  `transaction_type` enum('rent','maintenance','service_charge','deposit','refund','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','online','check') DEFAULT 'cash',
  `description` text DEFAULT NULL,
  `status` enum('paid','pending','overdue') DEFAULT 'pending',
  `invoice_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ledger`
--

INSERT INTO `ledger` (`ledger_id`, `tenant_id`, `agreement_id`, `transaction_type`, `amount`, `payment_date`, `payment_method`, `description`, `status`, `invoice_number`, `created_at`, `updated_at`, `customer_id`) VALUES
(1, 0, 4, 'rent', 11.00, '2025-12-23', '', 'Monthly Rent - Agreement: AGR-S-20251223-8834', 'paid', 'INV-RENT-20251223-1230', '2025-12-23 10:49:32', '2025-12-23 12:48:43', 1),
(2, 0, 5, 'deposit', 1200.00, '2025-12-24', '', 'Security Deposit - Agreement: AGR-S-20251224-3305', 'paid', 'INV-20251224-3306', '2025-12-24 04:57:27', '2025-12-24 05:01:46', 1),
(3, 0, 5, 'rent', 5000.00, '2025-12-24', 'cash', 'Monthly Rent - Agreement: AGR-S-20251224-3305', 'paid', 'INV-RENT-20251224-3424', '2025-12-24 04:57:27', '2025-12-24 05:49:53', 1),
(4, 0, 7, 'deposit', 500.00, '2025-12-24', 'cash', 'Security Deposit - Agreement: AGR-S-20251224-7126', 'paid', 'INV-DEP-20251224-5493', '2025-12-24 05:37:37', '2025-12-24 05:37:37', 1),
(5, 0, 7, 'rent', 2000.00, '2025-12-24', 'cash', 'Monthly Rent - Agreement: AGR-S-20251224-7126', 'paid', 'INV-RENT-20251224-4765', '2025-12-24 05:37:37', '2025-12-24 05:50:48', 1),
(6, 0, 7, 'other', 2000.00, '2025-12-24', 'cash', 'Remaining Balance - Agreement: AGR-S-20251224-7126', 'paid', 'INV-BAL-20251224-1823', '2025-12-24 05:37:37', '2025-12-24 05:50:54', 1),
(8, 0, 8, 'rent', 50000.00, '2025-12-24', 'cash', 'Monthly Rent - Agreement: AGR-S-20251224-9414', 'paid', 'INV-RENT-20251224-4213', '2025-12-24 06:16:20', '2025-12-24 06:17:13', 1),
(9, 0, 8, 'other', 10000.00, '2025-12-24', 'cash', 'Remaining Balance - Agreement: AGR-S-20251224-9414', 'paid', 'INV-BAL-20251224-4772', '2025-12-24 06:16:20', '2025-12-24 06:17:13', 1),
(10, 0, 6, 'refund', 500.00, '2025-12-24', '', '', 'paid', '', '2025-12-24 06:39:25', '2025-12-24 06:39:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `space_type` enum('shop','room','basement') NOT NULL,
  `space_id` int(11) NOT NULL,
  `issue_type` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('payment','lease','maintenance','general') DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `agreement_id` int(11) DEFAULT NULL,
  `ledger_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','online','check') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('completed','pending','failed') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `tenant_id`, `agreement_id`, `ledger_id`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `receipt_file`, `status`, `notes`, `created_at`, `updated_at`, `customer_id`) VALUES
(1, 0, 7, 4, 500.00, '2025-12-24', 'cash', NULL, NULL, 'completed', 'Initial payment for security deposit', '2025-12-24 05:37:37', '2025-12-24 05:37:37', 1),
(2, 0, 5, 3, 5000.00, '2025-12-24', 'cash', '', NULL, 'completed', '', '2025-12-24 05:49:53', '2025-12-24 05:49:53', 1),
(3, 0, 7, 5, 2000.00, '2025-12-24', 'cash', '', NULL, 'completed', '', '2025-12-24 05:50:48', '2025-12-24 05:50:48', 1),
(4, 0, 7, 6, 2000.00, '2025-12-24', 'cash', '', NULL, 'completed', '', '2025-12-24 05:50:54', '2025-12-24 05:50:54', 1),
(5, 0, 8, 8, 40000.00, '2025-12-24', 'cash', NULL, NULL, 'completed', 'Initial payment for first month rent', '2025-12-24 06:16:20', '2025-12-24 06:16:20', 1),
(6, 0, 8, 8, 60000.00, '2025-12-24', 'cash', '', NULL, 'completed', 'Ledger IDs: 8,9', '2025-12-24 06:17:13', '2025-12-24 06:17:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `area_sqft` decimal(10,2) DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `description` text DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `shop_id` int(11) NOT NULL,
  `shop_number` varchar(20) NOT NULL,
  `shop_name` varchar(100) DEFAULT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `area_sqft` decimal(10,2) DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `description` text DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`shop_id`, `shop_number`, `shop_name`, `floor_number`, `area_sqft`, `monthly_rent`, `status`, `description`, `tenant_id`, `created_at`, `updated_at`, `customer_id`) VALUES
(1, '615', 'Gemma Dominguez', 691, 55.00, 50000.00, 'available', 'Mollit sed dolor har', NULL, '2025-12-23 07:01:44', '2025-12-24 06:37:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_type` enum('admin','tenant') NOT NULL DEFAULT 'tenant',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `user_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@plaza.com', '$2y$10$vJZnEEm3N8og8L8FiG1LO.43sRr3np7vwFwcHIFHou83LeALpDaW.', 'System Administrator', '1234567890', NULL, 'admin', 'active', '2025-12-19 07:24:04', '2025-12-19 07:24:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agreements`
--
ALTER TABLE `agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD UNIQUE KEY `agreement_number` (`agreement_number`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `basements`
--
ALTER TABLE `basements`
  ADD PRIMARY KEY (`basement_id`),
  ADD UNIQUE KEY `basement_number` (`basement_number`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `cnic` (`cnic`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`ledger_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `agreement_id` (`agreement_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `agreement_id` (`agreement_id`),
  ADD KEY `ledger_id` (`ledger_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shop_id`),
  ADD UNIQUE KEY `shop_number` (`shop_number`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agreements`
--
ALTER TABLE `agreements`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `basements`
--
ALTER TABLE `basements`
  MODIFY `basement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `ledger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `shop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agreements`
--
ALTER TABLE `agreements`
  ADD CONSTRAINT `agreements_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agreements_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `basements`
--
ALTER TABLE `basements`
  ADD CONSTRAINT `basements_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `basements_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;

--
-- Constraints for table `ledger`
--
ALTER TABLE `ledger`
  ADD CONSTRAINT `ledger_ibfk_2` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`agreement_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ledger_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ledger_ibfk_4` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_requests_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`agreement_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`ledger_id`) REFERENCES `ledger` (`ledger_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_5` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rooms_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shops_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
