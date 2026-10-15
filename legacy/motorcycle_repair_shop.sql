-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 12:29 PM
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
-- Database: `motorcycle_repair_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `motorcycle_model` varchar(100) NOT NULL,
  `problem_description` text DEFAULT NULL,
  `service_type` varchar(50) DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `queue_no` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','in_progress','completed','cancelled') DEFAULT 'pending',
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_id`, `user_id`, `full_name`, `contact_number`, `motorcycle_model`, `problem_description`, `service_type`, `preferred_date`, `queue_no`, `status`, `estimated_cost`, `created_at`, `updated_at`) VALUES
(1, 'BK202412010001', NULL, 'John Doe', '09123456789', 'Honda Wave 100', 'Engine making strange noise and hard to start', 'Engine Repair', '2026-05-01', 1, 'in_progress', 0.00, '2026-05-01 14:33:29', '2026-07-20 03:28:20'),
(2, 'BK202412010002', NULL, 'Jane Smith', '09123456790', 'Yamaha Mio i125', 'Brakes not working properly', 'Brake Repair', '2026-05-01', 2, 'accepted', 0.00, '2026-05-01 14:33:29', '2026-05-01 14:33:29'),
(3, 'BK202412010003', NULL, 'Mike Johnson', '09123456791', 'Kawasaki Barako', 'Oil leak and unusual vibration', 'General Maintenance', '2026-05-01', 3, 'in_progress', 0.00, '2026-05-01 14:33:29', '2026-05-01 14:33:29'),
(4, 'BK202605019419', NULL, 'John Zedrick Macasiray', '099 226 01046', 'honda wave 100', 'may usok', 'Engine Repair', '2026-05-05', 4, 'completed', 0.00, '2026-05-01 15:18:56', '2026-05-04 14:47:23'),
(5, 'BK202605041120', NULL, 'John Zedrick Macasiray', '099 226 01046', 'honda wave 100', 'mausok po', 'Engine Repair', '2026-05-05', 1, 'completed', 128.99, '2026-05-04 13:45:49', '2026-05-04 15:01:08');

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `id` int(11) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`id`, `part_name`, `description`, `stock_quantity`, `unit_price`, `created_at`) VALUES
(1, 'Engine Oil', '4-stroke motorcycle oil 1L', 50, 8.99, '2026-05-01 14:33:29'),
(2, 'Spark Plug', 'Standard spark plug', 100, 3.50, '2026-05-01 14:33:29'),
(3, 'Brake Pads', 'Front brake pads set', 30, 15.99, '2026-05-01 14:33:29'),
(4, 'Air Filter', 'Air filter element', 38, 12.50, '2026-05-01 14:33:29'),
(5, 'Battery', '12V 5Ah motorcycle battery', 19, 45.00, '2026-05-01 14:33:29'),
(6, 'Chain Set', 'Drive chain and sprockets', 15, 35.00, '2026-05-01 14:33:29'),
(7, 'Clutch Cable', 'Standard clutch cable', 25, 8.00, '2026-05-01 14:33:29'),
(8, 'Headlight Bulb', '12V 35/35W halogen bulb', 60, 5.50, '2026-05-01 14:33:29'),
(9, 'Tire Tube', 'Standard inner tube', 44, 12.00, '2026-05-01 14:33:29'),
(10, 'Brake Fluid', 'DOT 3 brake fluid 500ml', 34, 6.99, '2026-05-01 14:33:29');

-- --------------------------------------------------------

--
-- Table structure for table `repairs`
--

CREATE TABLE `repairs` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `mechanic_id` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repairs`
--

INSERT INTO `repairs` (`id`, `booking_id`, `mechanic_id`, `status`, `labor_cost`, `start_date`, `completion_date`, `notes`, `created_at`) VALUES
(1, 2, 2, 'completed', 25.00, '2026-05-01', NULL, NULL, '2026-05-01 14:33:29'),
(2, 3, 2, 'in_progress', 40.00, '2026-05-01', NULL, NULL, '2026-05-01 14:33:29'),
(4, 5, 2, 'completed', 40.00, '2026-05-04', '2026-05-04', NULL, '2026-05-04 13:52:11'),
(5, 1, 7, 'in_progress', 0.00, '2026-07-20', NULL, NULL, '2026-05-04 14:04:26'),
(6, 5, NULL, 'completed', 0.00, '2026-05-04', '2026-05-04', NULL, '2026-05-04 14:47:00'),
(7, 1, NULL, 'in_progress', 0.00, '2026-07-20', NULL, NULL, '2026-07-20 03:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `repair_parts`
--

CREATE TABLE `repair_parts` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_parts`
--

INSERT INTO `repair_parts` (`id`, `repair_id`, `part_id`, `quantity`, `price_at_time`, `created_at`) VALUES
(1, 1, 3, 1, 15.99, '2026-05-01 14:33:29'),
(2, 2, 1, 2, 8.99, '2026-05-01 14:33:29'),
(3, 4, 4, 1, 12.50, '2026-05-04 13:52:48'),
(5, 4, 5, 1, 45.00, '2026-05-04 14:49:12'),
(6, 4, 4, 1, 12.50, '2026-05-04 15:00:44'),
(7, 4, 10, 1, 6.99, '2026-05-04 15:00:44'),
(8, 4, 9, 1, 12.00, '2026-05-04 15:00:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin','mechanic') DEFAULT 'customer',
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `specialization` varchar(100) DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `contact_number`, `address`, `role`, `remember_token`, `reset_token`, `reset_expiry`, `created_at`, `specialization`) VALUES
(1, 'Admin User', 'admin@repairshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456789', NULL, 'admin', NULL, NULL, NULL, '2026-05-01 14:33:29', 'General'),
(2, 'John Mechanic', 'mechanic@repairshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456788', NULL, 'mechanic', NULL, NULL, NULL, '2026-05-01 14:33:29', 'General'),
(6, 'John Zedrick Macasiray', 'zedmacasiray@gmail.com', '$2y$10$uxD2iF0w72/ZfbwszAIsfeZOptE2S3eWRXkd59AcOMMVfRNPD5TSK', '09922601046', NULL, 'customer', '8ff780abf641acb6c25b5be3bbf06966d545024502a2e08adc5ddc19c9b0a6e9', NULL, NULL, '2026-07-20 03:25:18', 'General'),
(7, 'albrix mechanic', 'albrix@gmail.com', '$2y$10$rl12cJMZGDDzFRVedjlFe.UU56BoB0Nn/Esgi.z59bqkozDNqZOva', '0123456789', 'libtong tagudin school', 'mechanic', NULL, NULL, NULL, '2026-07-19 21:27:47', 'Engine'),
(8, 'Jea', 'mike@gmail.com', '123', NULL, NULL, 'customer', NULL, NULL, NULL, '2026-07-20 04:34:04', 'General'),
(9, 'Jane Doe', 'jane@example.com', '$2y$10$examplehashedpassword', '09123456780', 'Tagudin, Ilocos Sur', 'customer', NULL, NULL, NULL, '2026-07-20 04:35:59', 'General');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `repairs`
--
ALTER TABLE `repairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `mechanic_id` (`mechanic_id`);

--
-- Indexes for table `repair_parts`
--
ALTER TABLE `repair_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repair_id` (`repair_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `repairs`
--
ALTER TABLE `repairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `repair_parts`
--
ALTER TABLE `repair_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `repairs`
--
ALTER TABLE `repairs`
  ADD CONSTRAINT `repairs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repairs_ibfk_2` FOREIGN KEY (`mechanic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `repair_parts`
--
ALTER TABLE `repair_parts`
  ADD CONSTRAINT `repair_parts_ibfk_1` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repair_parts_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
