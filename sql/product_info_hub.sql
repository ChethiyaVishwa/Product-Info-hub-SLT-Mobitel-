-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 05:18 PM
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
-- Database: `product_info_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `objects`
--

CREATE TABLE `objects` (
  `object_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `object_name` varchar(255) NOT NULL,
  `created_dtm` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_dtm` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `objects`
--

INSERT INTO `objects` (`object_id`, `template_id`, `object_name`, `created_dtm`, `end_dtm`) VALUES
(11, 51, 'Unlimited Family (Plan 1)', '2025-04-22 06:02:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `object_field_values`
--

CREATE TABLE `object_field_values` (
  `id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_dtm` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_dtm` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `object_field_values`
--

INSERT INTO `object_field_values` (`id`, `object_id`, `field_id`, `field_value`, `created_dtm`, `end_dtm`) VALUES
(317, 11, 68, 'Unlimited', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(318, 11, 69, '1000* remaining month', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(319, 11, 70, '12,500', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(320, 11, 61, '1212', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(321, 11, 65, '1 Year', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(322, 11, 66, 'Yes', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(323, 11, 68, 'Unlimited', '2025-04-22 06:02:49', '2025-05-06 14:58:32'),
(324, 11, 69, '1000* remaining month', '2025-04-22 06:02:49', '2025-05-06 14:58:32'),
(325, 11, 70, '12,500', '2025-04-22 06:02:49', '2025-05-06 14:58:32'),
(326, 11, 71, 'Unlimited Family', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(327, 11, 72, '7,900', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(328, 11, 73, 'N/A', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(329, 11, 74, '1000', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(330, 11, 75, '100/50 ', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(331, 11, 76, '2 Mbps', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(332, 11, 77, '1 Mbps', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(333, 11, 78, 'Family', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(334, 11, 79, 'Free', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(335, 11, 80, '5,000', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(336, 11, 81, '01/01/2025', '2025-04-22 06:02:49', '2025-05-06 14:57:07'),
(337, 11, 65, '1 Year', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(338, 11, 69, '1000* remaining month', '2025-05-06 14:57:07', NULL),
(339, 11, 66, 'Yes', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(340, 11, 70, '12,500', '2025-05-06 14:57:07', NULL),
(341, 11, 61, '1212', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(342, 11, 68, 'Unlimited', '2025-05-06 14:57:07', NULL),
(343, 11, 81, '01/01/2025', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(344, 11, 74, '1000', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(345, 11, 73, 'N/A', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(346, 11, 75, '100/50 ', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(347, 11, 79, 'Free', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(348, 11, 80, '5,000', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(349, 11, 72, '7,900', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(350, 11, 71, 'Unlimited Family', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(351, 11, 76, '2 Mbps', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(352, 11, 77, '1 Mbps', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(353, 11, 78, 'Family', '2025-05-06 14:57:07', '2025-05-06 14:58:32'),
(354, 11, 65, '1 Year', '2025-05-06 14:58:32', NULL),
(355, 11, 69, '1000* remaining month', '2025-05-06 14:58:32', NULL),
(356, 11, 66, 'Yes', '2025-05-06 14:58:32', NULL),
(357, 11, 70, '12,500', '2025-05-06 14:58:32', NULL),
(358, 11, 61, '1212', '2025-05-06 14:58:32', NULL),
(359, 11, 68, 'Unlimited', '2025-05-06 14:58:32', NULL),
(360, 11, 81, '01/01/2025', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(361, 11, 74, '1000', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(362, 11, 73, 'N/A', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(363, 11, 75, '100/50 ', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(364, 11, 79, 'Free', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(365, 11, 80, '5,000', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(366, 11, 72, '7,900', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(367, 11, 71, 'Unlimited Family', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(368, 11, 76, '2 Mbps', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(369, 11, 77, '1 Mbps', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(370, 11, 78, 'Family', '2025-05-06 14:58:32', '2025-05-06 15:01:17'),
(371, 11, 81, '01/01/2025', '2025-05-06 15:01:17', NULL),
(372, 11, 74, '1000', '2025-05-06 15:01:17', NULL),
(373, 11, 73, 'N/A', '2025-05-06 15:01:17', NULL),
(374, 11, 75, '100/50 ', '2025-05-06 15:01:17', NULL),
(375, 11, 79, 'Free', '2025-05-06 15:01:17', NULL),
(376, 11, 80, '5,000', '2025-05-06 15:01:17', NULL),
(377, 11, 72, '7,900', '2025-05-06 15:01:17', NULL),
(378, 11, 71, 'Unlimited Family', '2025-05-06 15:01:17', NULL),
(379, 11, 76, '2 Mbps', '2025-05-06 15:01:17', NULL),
(380, 11, 77, '1 Mbps', '2025-05-06 15:01:17', NULL),
(381, 11, 78, 'Family', '2025-05-06 15:01:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_superadmin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `superadmins`
--

CREATE TABLE `superadmins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `superadmins`
--

INSERT INTO `superadmins` (`id`, `username`, `password`, `email`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'admin', '$2y$10$eAmbdjc8GAKwpXzepmZO8O3LJZXq96wAPRWQMlGEIvZ0o7BRtlTme', 'admin@productinfohub.com', '2025-04-01 15:44:31', '2025-04-01 15:57:40', 1),
(11, 'admin2', '$2y$10$8iuB8eyIJ0y0LbiUvPppJulEN8/owB3XwkHz3eXsGa4JqDo6qSx6W', 'chethiyavishwa717@gmail.com', '2025-04-01 16:15:19', '2025-05-06 14:56:47', 1),
(12, 'admin3', '$2y$10$X73OSg2eA5VMMsffQcHsJebfRiijZnMjUYbprIkPmzEYS.PK.NYJK', 'chethiyavishwa06@gmail.com', '2025-04-02 20:39:33', '2025-04-03 16:25:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `has_parent` tinyint(1) DEFAULT 0,
  `root_template_id` int(11) DEFAULT NULL,
  `has_child` tinyint(1) DEFAULT 0,
  `created_dtm` datetime DEFAULT current_timestamp(),
  `created_by` varchar(100) NOT NULL,
  `end_dtm` datetime DEFAULT NULL,
  `ended_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`template_id`, `template_name`, `has_parent`, `root_template_id`, `has_child`, `created_dtm`, `created_by`, `end_dtm`, `ended_by`) VALUES
(40, 'Digital Product', 0, NULL, 1, '2025-04-09 10:59:23', 'system', NULL, NULL),
(41, 'Digital Product_Eazy Storage', 1, 40, 0, '2025-04-09 11:06:34', 'system', NULL, NULL),
(42, 'Digital Product_SLT Lynked', 1, 40, 0, '2025-04-09 11:07:50', 'system', NULL, NULL),
(43, 'Broad Brand', 0, NULL, 1, '2025-04-09 11:12:33', 'system', NULL, NULL),
(44, 'Broad Brand_FTTH', 1, 43, 1, '2025-04-09 11:13:10', 'system', NULL, NULL),
(45, 'Broad Brand_ADSL', 1, 43, 0, '2025-04-09 11:13:29', 'system', NULL, NULL),
(46, 'Broad Brand_4G LTE', 1, 43, 0, '2025-04-09 11:13:52', 'system', NULL, NULL),
(47, 'Broad Brand_FTTH_Volume Based', 1, 44, 0, '2025-04-09 11:14:24', 'system', '2025-04-09 11:14:45', 'system'),
(48, 'Broad Brand_FTTH_Volume Based', 1, 44, 0, '2025-04-09 11:18:00', 'system', NULL, NULL),
(49, 'Broad Brand_FTTH_Any Time', 1, 44, 0, '2025-04-09 11:18:28', 'system', NULL, NULL),
(50, 'Broad Brand_FTTH_Unlimited', 1, 44, 0, '2025-04-09 11:18:50', 'system', NULL, NULL),
(51, 'Broad Brand_FTTH_Speed based  unlimited', 1, 44, 0, '2025-04-09 11:19:11', 'system', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `template_fields`
--

CREATE TABLE `template_fields` (
  `field_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_fixed` tinyint(1) DEFAULT 0,
  `created_dtm` datetime DEFAULT current_timestamp(),
  `created_by` varchar(100) NOT NULL,
  `end_dtm` datetime DEFAULT NULL,
  `ended_by` varchar(100) DEFAULT NULL,
  `field_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_fields`
--

INSERT INTO `template_fields` (`field_id`, `template_id`, `field_name`, `field_type`, `description`, `is_fixed`, `created_dtm`, `created_by`, `end_dtm`, `ended_by`, `field_value`) VALUES
(61, 43, 'Support Contact', 'number', '1212', 1, '2025-04-09 11:12:33', 'system', '2025-05-06 17:03:16', 'system', '1212'),
(64, 48, 'Special note', 'text', 'From 01/01/2025 time based packages not be available for new FTTH Customers', 1, '2025-04-09 11:18:00', 'system', NULL, NULL, NULL),
(65, 43, 'Commitment period', 'text', 'test', 1, '2025-04-22 11:02:13', 'system', NULL, NULL, '1 Year'),
(66, 43, 'FUP Policy', 'text', 'fair user policy', 1, '2025-04-22 11:05:51', 'system', NULL, NULL, 'Yes'),
(68, 51, 'Voice (On net/Off net)', 'text', 'test', 1, '2025-04-22 11:10:27', 'system', NULL, NULL, 'Unlimited'),
(69, 51, 'Early Termination fee', 'text', '', 1, '2025-04-22 11:12:26', 'system', NULL, NULL, '1000* remaining month'),
(70, 51, 'New connection fee', 'number', '', 1, '2025-04-22 11:14:18', 'system', NULL, NULL, '12,500'),
(71, 51, 'Package Name', 'text', '', 0, '2025-04-22 11:16:15', 'system', NULL, NULL, NULL),
(72, 51, 'Monthly Rental', 'number', '', 0, '2025-04-22 11:16:47', 'system', NULL, NULL, NULL),
(73, 51, 'Line Rental', 'text', '', 0, '2025-04-22 11:18:36', 'system', NULL, NULL, NULL),
(74, 51, 'Internet FOP (GB)', 'number', '', 0, '2025-04-22 11:19:25', 'system', NULL, NULL, NULL),
(75, 51, 'Maximum Bandwidth (D/U Mbps)', 'text', '', 0, '2025-04-22 11:21:31', 'system', NULL, NULL, NULL),
(76, 51, 'Speed at FUP Download', 'text', '', 0, '2025-04-22 11:22:56', 'system', NULL, NULL, NULL),
(77, 51, 'Speed at FUP Upload', 'text', '', 0, '2025-04-22 11:23:26', 'system', NULL, NULL, NULL),
(78, 51, 'Target Group', 'text', '', 0, '2025-04-22 11:24:05', 'system', NULL, NULL, NULL),
(79, 51, 'Migration fee (Existing User)', 'text', '', 0, '2025-04-22 11:25:18', 'system', NULL, NULL, NULL),
(80, 51, 'Migration fee (From Megaline)', 'number', '', 0, '2025-04-22 11:25:53', 'system', NULL, NULL, NULL),
(81, 51, 'Effective Date', 'date', '', 0, '2025-04-22 11:27:18', 'system', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `otp`, `otp_expiry`, `is_verified`) VALUES
(4, 'Chethiya', 'chethiyavishwa06@gmail.com', '$2y$10$oHo2pGJaFYcPcCtUY1aDJ.ZKBuveaZsdVZthJC3pihuQPdOds9AEq', '2025-03-23 16:14:54', '2025-03-23 16:15:29', NULL, NULL, 1),
(5, 'Vishwa', 'vishworld177@gmail.com', '$2y$10$W2AUcL3B4ie1OdBCwTZ7se19lhLxf5vXEg6iz6kjxoVeSo0T2N7q6', '2025-03-23 22:59:52', '2025-03-23 23:01:16', NULL, NULL, 1),
(10, 'harshila', 'hashidewmini0@gmail.com', '$2y$10$9vy3I0CissqpIuR3mV09L.VS0B8Okxgutg.1ZGor4qs1SIH0MoC6K', '2025-03-26 06:07:35', '2025-03-26 06:08:43', NULL, NULL, 1),
(13, 'Vishwa', 'chethiyavishwa717@gmail.com', '$2y$10$Ume/Ypz0Oesc22XbTIjkV.2QokBJxe2Rpzb18rCcGulK6Hi8seAe2', '2025-04-09 06:21:04', '2025-04-09 06:24:28', NULL, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `objects`
--
ALTER TABLE `objects`
  ADD PRIMARY KEY (`object_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `object_field_values`
--
ALTER TABLE `object_field_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `object_id` (`object_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `superadmins`
--
ALTER TABLE `superadmins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `template_fields`
--
ALTER TABLE `template_fields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `template_id` (`template_id`);

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
-- AUTO_INCREMENT for table `objects`
--
ALTER TABLE `objects`
  MODIFY `object_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `object_field_values`
--
ALTER TABLE `object_field_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `superadmins`
--
ALTER TABLE `superadmins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `template_fields`
--
ALTER TABLE `template_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `objects`
--
ALTER TABLE `objects`
  ADD CONSTRAINT `objects_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`);

--
-- Constraints for table `object_field_values`
--
ALTER TABLE `object_field_values`
  ADD CONSTRAINT `object_field_values_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `objects` (`object_id`),
  ADD CONSTRAINT `object_field_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `template_fields` (`field_id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_fields`
--
ALTER TABLE `template_fields`
  ADD CONSTRAINT `template_fields_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
