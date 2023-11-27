-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 17, 2023 at 08:46 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `publir`
--

-- --------------------------------------------------------

--
-- Table structure for table `dt_create_prebid_js`
--

CREATE TABLE `dt_create_prebid_js` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `status` enum('Y','N') NOT NULL DEFAULT 'N',
  `prebid_version` varchar(255) NOT NULL,
  `across` enum('Y','N') NOT NULL DEFAULT 'N',
  `media_online` enum('Y','N') NOT NULL DEFAULT 'N',
  `dot_media` enum('Y','N') NOT NULL DEFAULT 'N',
  `a4g` enum('Y','N') NOT NULL DEFAULT 'N',
  `aax` enum('Y','N') NOT NULL DEFAULT 'N',
  `ablida` enum('Y','N') NOT NULL DEFAULT 'N',
  `acuity_ads` enum('Y','N') NOT NULL DEFAULT 'N',
  `adwmg` enum('Y','N') NOT NULL DEFAULT 'N',
  `adgaio` enum('Y','N') NOT NULL DEFAULT 'N',
  `adasta_media` enum('Y','N') NOT NULL DEFAULT 'N',
  `adbite` enum('Y','N') NOT NULL DEFAULT 'N',
  `adblender` enum('Y','N') NOT NULL DEFAULT 'N',
  `adbookpsp` enum('Y','N') NOT NULL DEFAULT 'N',
  `addefend` enum('Y','N') NOT NULL DEFAULT 'N',
  `adformopen_rtb` enum('Y','N') NOT NULL DEFAULT 'N',
  `gdpr` enum('Y','N') NOT NULL DEFAULT 'N',
  `gpp` enum('Y','N') NOT NULL DEFAULT 'N',
  `us_privacy` enum('Y','N') NOT NULL DEFAULT 'N',
  `first_party_data_enrichment` enum('Y','N') NOT NULL DEFAULT 'N',
  `gdpr_enforcement` enum('Y','N') NOT NULL DEFAULT 'N',
  `gpt_pre_auction` enum('Y','N') NOT NULL DEFAULT 'N',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dt_create_prebid_js`
--
ALTER TABLE `dt_create_prebid_js`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dt_create_prebid_js`
--
ALTER TABLE `dt_create_prebid_js`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
