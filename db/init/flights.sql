-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Jul 07, 2024 at 09:06 PM
-- Server version: 11.3.2-MariaDB-1:11.3.2+maria~ubu2204
-- PHP Version: 8.2.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flights`
--

-- --------------------------------------------------------

--
-- Table structure for table `airports`
--

CREATE TABLE `airports` (
  `code` text NOT NULL,
  `name` text NOT NULL,
  `country` text NOT NULL,
  `elevation` double DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apicalls`
--

CREATE TABLE `apicalls` (
  `id` int(11) NOT NULL,
  `url` text NOT NULL,
  `status` int(11) NOT NULL,
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`request`)),
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`response`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `apicalls`
--
DELIMITER $$
CREATE TRIGGER `insert_lowestfaredestination` AFTER INSERT ON `apicalls` FOR EACH ROW INSERT INTO 
lowestfaredestination
SELECT `apicalls`.`id` AS `id`, `apicalls`.`updated` AS `requested`, cast(`apicalls`.`updated` as date) AS `requesteddate`, cast(json_unquote(json_extract(`apicalls`.`request`,'$.fromDate')) as date) AS `windowstart`, cast(json_unquote(json_extract(`apicalls`.`request`,'$.untilDate')) as date) AS `windowend`, `cities`.`CODE` AS `CODE`, `cities`.`departureDate` AS `departureDate`, `cities`.`returnDate` AS `returnDate`, `cities`.`price` AS `price`, `cities`.`duration` AS `duration` FROM (`apicalls` join JSON_TABLE(`apicalls`.`response`, '$.destinationCities[*]' COLUMNS (`CODE` text PATH '$.code', NESTED PATH '$.flightProducts[*]' COLUMNS (`departureDate` date PATH '$.departureDate', `returnDate` date PATH '$.returnDate', `price` double PATH '$.price.totalPrice', `duration` int(11) PATH '$.duration'))) `cities`) WHERE `apicalls`.`url` = 'https://api.airfranceklm.com/opendata/offers/v3/lowest-fares-by-destination' AND id = NEW.id
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lowestfaredestination`
--

CREATE TABLE `lowestfaredestination` (
  `id` int(11) NOT NULL,
  `requested` datetime NOT NULL,
  `requesteddate` date NOT NULL,
  `windowstart` date NOT NULL,
  `windowend` date NOT NULL,
  `code` text NOT NULL,
  `departureDate` date NOT NULL,
  `returnDate` date NOT NULL,
  `price` double NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trackeddestinations`
--

CREATE TABLE `trackeddestinations` (
  `code` char(3) NOT NULL,
  `showinreport` tinyint(1) NOT NULL DEFAULT 1,
  `defaultselected` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`code`(3));

--
-- Indexes for table `apicalls`
--
ALTER TABLE `apicalls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trackeddestinations`
--
ALTER TABLE `trackeddestinations`
  ADD PRIMARY KEY (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apicalls`
--
ALTER TABLE `apicalls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
