-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2023 at 12:12 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `imcustom2`
--

-- --------------------------------------------------------

--
-- Table structure for table `dt_network_reports_logs`
--

CREATE TABLE `dt_network_reports_logs` (
  `id` int(10) NOT NULL,
  `date` date DEFAULT NULL,
  `site_id` int(10) DEFAULT NULL,
  `network_id` int(10) DEFAULT NULL,
  `size_id` int(10) DEFAULT NULL,
  `impressions` int(10) DEFAULT NULL,
  `revenue` decimal(10,2) DEFAULT NULL,
  `clicks` int(5) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dt_network_reports_logs`
--

INSERT INTO `dt_network_reports_logs` (`id`, `date`, `site_id`, `network_id`, `size_id`, `impressions`, `revenue`, `clicks`, `type`, `status`, `created_at`, `updated_at`) VALUES
(7, '2023-04-04', 1001, 158, 14, 12345, '1.05', 0, 'Next Millennium', 1, '2023-04-12 11:18:59', '2023-04-12 11:18:59'),
(8, '2023-04-04', 1003, 158, 14, 123456, '1.05', 0, 'Next Millennium1', 1, '2023-04-12 11:19:27', '2023-04-12 11:19:27'),
(9, '2023-04-04', 1004, 155, 14, 123454, '1.05', 0, 'Next Millennium2', 1, '2023-04-12 11:20:16', '2023-04-12 11:20:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dt_network_reports_logs`
--
ALTER TABLE `dt_network_reports_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `date` (`date`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `network_id` (`network_id`),
  ADD KEY `size_id` (`size_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dt_network_reports_logs`
--
ALTER TABLE `dt_network_reports_logs`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
