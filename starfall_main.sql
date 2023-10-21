-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 09. Aug, 2023 00:02 AM
-- Tjener-versjon: 10.5.19-MariaDB-0+deb11u2
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `starfall_main`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `attach_queue`
--

CREATE TABLE `attach_queue` (
  `id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL COMMENT 'uuid of person trying to attach',
  `timeout` varchar(255) NOT NULL,
  `attachments` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `web_accounts`
--

CREATE TABLE `web_accounts` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(500) NOT NULL,
  `staff_level` int(11) NOT NULL DEFAULT 0 COMMENT '0 usr, 1 guide, 2 gm, 3 admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `web_accounts`
--

INSERT INTO `web_accounts` (`id`, `username`, `password`, `staff_level`) VALUES
(2, 'Test', '$2y$14$e6HyQB2l48a3S3TDV2/XHeyWC0JyOFX7HSuTQOIFPU3Wf8qT3MxDG', 0),
(3, 'Thesd', '$2y$14$E1FiZunMUTLo6Ccrl9Z74eUB4ouavezbkPfdxkqx.gJavBa5d1h9a', 0),
(4, 'BRRRRRRRRRRRRRRRRRRRR', '$2y$14$BIOVWFxn3BCxtm5XblhtWO0o/./IOmAeNrHCHXIZU2fe4UvlNEeIW', 0),
(5, 'Rotate', '$2y$14$1kjmpZo8N11Yg/vIKg76.eJRXaOxrjVIQAuVWsAKW.SD.fEdarwg2', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attach_queue`
--
ALTER TABLE `attach_queue`
  ADD PRIMARY KEY (`uuid`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `web_accounts`
--
ALTER TABLE `web_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attach_queue`
--
ALTER TABLE `attach_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `web_accounts`
--
ALTER TABLE `web_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
