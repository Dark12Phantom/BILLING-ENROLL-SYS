-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 18, 2025 at 06:09 PM
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
-- Database: `enrollment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_schedule`
--

CREATE TABLE `billing_schedule` (
  `id` int(11) NOT NULL,
  `expense_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `frequency` enum('monthly','weekly','yearly') DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `last_run` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_schedule`
--

INSERT INTO `billing_schedule` (`id`, `expense_name`, `amount`, `category`, `frequency`, `next_due_date`, `last_run`, `status`) VALUES
(1, 'Teachers\' Salary', 126000.00, 'Operational', 'monthly', '2025-11-18', '2025-10-18', 'active'),
(2, 'Social Security System', 50790.00, 'Compliance', 'monthly', '2025-11-18', '2025-10-18', 'active'),
(3, 'PhilHealth', 5800.00, 'Compliance', 'monthly', '2025-11-18', '2025-10-18', 'active'),
(4, 'Pag-IBIG', 18058.00, 'Compliance', 'monthly', '2025-11-18', '2025-10-18', 'active'),
(5, 'Depreciation', 9000.00, 'Operational', 'monthly', '2025-11-18', '2025-10-18', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `compliance_expenses`
--

CREATE TABLE `compliance_expenses` (
  `id` int(11) NOT NULL,
  `type` enum('Social Security System','Pag-IBIG','PhilHealth','Permit','Registration') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `period_covered` varchar(100) DEFAULT NULL,
  `paid_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `compliance_expenses`
--

INSERT INTO `compliance_expenses` (`id`, `type`, `amount`, `payment_date`, `reference_number`, `period_covered`, `paid_by`) VALUES
(1, 'Social Security System', 50790.00, '2025-10-18', 'REF-20251018-0001', '1 Month', 0),
(2, 'PhilHealth', 5800.00, '2025-10-18', 'REF-20251018-0002', '1 Month', 0),
(3, 'Pag-IBIG', 18058.00, '2025-10-18', 'REF-20251018-0003', '1 Month', 0);

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `discount_types` text NOT NULL,
  `total_amount` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fees_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `discount_types`, `total_amount`, `student_id`, `fees_id`) VALUES
(1, '[\"referral\",\"earlybird\",\"sibling\",\"fullpayment\"]', 2500, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `frequency` enum('One-time','Monthly','Quarterly','Annual') DEFAULT 'One-time'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `name`, `description`, `amount`, `is_recurring`, `frequency`) VALUES
(1, 'Tuition Fee', 'Sample description', 5000.00, 1, 'Monthly'),
(3, 'Book Fee', 'Fee for books', 1000.00, 0, 'One-time');

-- --------------------------------------------------------

--
-- Table structure for table `operational_expenses`
--

CREATE TABLE `operational_expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `particular` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `evidence` varchar(255) DEFAULT NULL,
  `date_incurred` date NOT NULL,
  `approved_by` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `operational_expenses`
--

INSERT INTO `operational_expenses` (`id`, `category`, `particular`, `amount`, `evidence`, `date_incurred`, `approved_by`) VALUES
(1, 'Operational', 'Teachers\' Salary', 126000.00, 'Auto-Billed', '2025-10-18', 0),
(2, 'Operational', 'Depreciation', 9000.00, 'Auto-Billed', '2025-10-18', 0);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `idParentPicturePath` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `student_id`, `first_name`, `last_name`, `relationship`, `mobile_number`, `email`, `address`, `idParentPicturePath`) VALUES
(1, 1, 'Anabel', 'Ramos', 'Guardian', '098765654', 'anabel@gmail.com', 'Quezon City', '');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_items`
--

CREATE TABLE `payment_items` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `compliance_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `expense_id`, `receipt_no`, `date_issued`, `amount`, `description`, `compliance_id`) VALUES
(1, 1, 'REF-20251018-0001', '2025-10-18', 126000.00, 'Teachers\' Salary', NULL),
(2, 2, 'REF-20251018-0002', '2025-10-18', 9000.00, 'Depreciation', NULL),
(3, NULL, 'REF-20251018-0001', '2025-10-18', 50790.00, 'Social Security System', 1),
(4, NULL, 'REF-20251018-0002', '2025-10-18', 5800.00, 'PhilHealth', 2),
(5, NULL, 'REF-20251018-0003', '2025-10-18', 18058.00, 'Pag-IBIG', 3);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `idPicturePath` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `address`, `mobile_number`, `grade_level`, `section`, `status`, `created_at`, `idPicturePath`) VALUES
(1, '3123232', 'Juan', 'Dela Cruz', '1999-06-08', 'Male', 'Quezon City', '098765434565', 'Grade 7', 'B', 'Active', '2025-08-04 05:08:10', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','Paid','Overdue') DEFAULT 'Pending',
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student_fees`
--

INSERT INTO `student_fees` (`id`, `student_id`, `fee_id`, `due_date`, `status`, `amount`) VALUES
(14, 1, 1, '2025-09-08', 'Pending', 208.33),
(15, 1, 1, '2025-10-08', 'Pending', 208.33),
(16, 1, 1, '2025-11-08', 'Pending', 208.33),
(17, 1, 1, '2025-12-08', 'Pending', 208.33),
(18, 1, 1, '2026-01-08', 'Pending', 208.33),
(19, 1, 1, '2026-02-08', 'Pending', 208.33),
(20, 1, 1, '2026-03-08', 'Pending', 208.33),
(21, 1, 1, '2026-04-08', 'Pending', 208.33),
(22, 1, 1, '2026-05-08', 'Pending', 208.33),
(23, 1, 1, '2026-06-08', 'Pending', 208.33),
(24, 1, 1, '2026-07-08', 'Pending', 208.33),
(25, 1, 1, '2026-08-08', 'Pending', 208.37);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff','system') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`) VALUES
(0, 'System', NULL, NULL, 'system', NULL, NULL),
(1, 'admin', 'admin@school.edu', '$2y$10$c9/A3vnd9BvYwr6ALRcjhO4LvYZmRdsNc.PGsGxYZxyu5cEHxbQKG', 'admin', '2025-08-04 05:02:49', '2025-10-18 23:58:04'),
(3, 'staff1', 'gacetaerick124@gmail.com', '$2y$10$bszBYBKTJLZY8sbNFoL0pOtFCNiaIvI/D7V9xSKpzsLIsHj94W8/q', 'staff', '2025-09-14 09:36:44', '2025-10-09 18:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_tables`
--

CREATE TABLE `user_tables` (
  `id` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `first_name` text NOT NULL,
  `last_name` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `user_type` text NOT NULL,
  `status` text DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mobile_number` varchar(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `idPicturePath` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tables`
--

INSERT INTO `user_tables` (`id`, `userID`, `first_name`, `last_name`, `staff_id`, `user_type`, `status`, `age`, `gender`, `address`, `mobile_number`, `date_of_birth`, `idPicturePath`) VALUES
(0, 0, 'System', NULL, NULL, 'System Auto-Billing', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 1, 'School', 'Admin', 1234565, 'admin', 'Active', 0, 'Male', 'Ed Sidi, Asi Ed Sina, Paeey Sidi', '09123456789', '1987-06-10', ''),
(13, 3, 'Erick', 'Gaceta', 3131412, 'staff', '', 0, 'Female', 'Buguias, Benguet\r\nBaguio, Benguet', '09201555544', '2001-02-08', 'STA_3131412PFP.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_schedule`
--
ALTER TABLE `billing_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compliance_expenses`
--
ALTER TABLE `compliance_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `fees_id` (`fees_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operational_expenses`
--
ALTER TABLE `operational_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `payment_items`
--
ALTER TABLE `payment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `student_fee_id` (`student_fee_id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`),
  ADD KEY `compliance_id` (`compliance_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fee_id` (`fee_id`),
  ADD KEY `idx_student_fees_installments` (`student_id`,`fee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_tables`
--
ALTER TABLE `user_tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billing_schedule`
--
ALTER TABLE `billing_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `compliance_expenses`
--
ALTER TABLE `compliance_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `operational_expenses`
--
ALTER TABLE `operational_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_items`
--
ALTER TABLE `payment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_tables`
--
ALTER TABLE `user_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `compliance_expenses`
--
ALTER TABLE `compliance_expenses`
  ADD CONSTRAINT `compliance_expenses_ibfk_1` FOREIGN KEY (`paid_by`) REFERENCES `user_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`fees_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `discounts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `operational_expenses`
--
ALTER TABLE `operational_expenses`
  ADD CONSTRAINT `operational_expenses_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `user_tables` (`id`);

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `payment_items`
--
ALTER TABLE `payment_items`
  ADD CONSTRAINT `payment_items_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_items_ibfk_2` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `operational_expenses` (`id`),
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`compliance_id`) REFERENCES `compliance_expenses` (`id`);

--
-- Constraints for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_tables`
--
ALTER TABLE `user_tables`
  ADD CONSTRAINT `user_tables_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
