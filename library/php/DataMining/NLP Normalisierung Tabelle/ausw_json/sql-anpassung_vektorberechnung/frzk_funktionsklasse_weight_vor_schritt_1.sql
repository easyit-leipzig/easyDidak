-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 29. Mrz 2026 um 07:17
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `icas`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_funktionsklasse_weight`
--

CREATE TABLE `frzk_funktionsklasse_weight` (
  `funktionsklasse_id` int(11) NOT NULL,
  `kognition` decimal(4,3) DEFAULT NULL,
  `sozial` decimal(4,3) NOT NULL DEFAULT 0.000,
  `affektiv` decimal(4,3) NOT NULL DEFAULT 0.000,
  `motivation` decimal(4,3) DEFAULT NULL,
  `methodik` decimal(4,3) DEFAULT NULL,
  `performanz` decimal(4,3) DEFAULT NULL,
  `regulation` decimal(4,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `frzk_funktionsklasse_weight`
--

INSERT INTO `frzk_funktionsklasse_weight` (`funktionsklasse_id`, `kognition`, `sozial`, `affektiv`, `motivation`, `methodik`, `performanz`, `regulation`) VALUES
(0, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000),
(1, 0.600, 0.000, 0.000, 0.400, 0.400, 0.900, 0.600),
(2, -0.600, 0.000, 0.000, -0.400, -0.400, -0.900, -0.600),
(3, 0.600, 0.000, 0.000, 0.400, 0.600, 0.500, 0.900),
(4, 0.900, 0.000, 0.000, 0.400, 0.500, 0.600, 0.600),
(5, 0.400, 0.000, 0.600, 0.900, 0.300, 0.500, 0.500),
(6, 0.400, 0.000, 0.600, 0.600, 0.300, 0.700, 0.400),
(7, 0.600, 0.000, 0.000, 0.500, 0.900, 0.600, 0.700),
(8, 0.500, 0.000, 0.000, 0.400, 0.400, 0.500, 0.800),
(9, 0.400, 0.750, 0.000, 0.700, 0.500, 0.500, 0.500),
(10, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000, 0.000),
(11, 0.600, 0.000, 0.000, 0.300, 0.300, 0.500, 0.400),
(12, 0.800, 0.000, 0.000, 0.400, 0.400, 0.600, 0.600),
(13, 0.700, 0.000, 0.000, 0.500, 0.600, 0.700, 0.600),
(14, 0.900, 0.000, 0.000, 0.500, 0.600, 0.600, 0.700),
(15, 0.900, 0.000, 0.000, 0.600, 0.600, 0.600, 0.800),
(16, 1.000, 0.000, 0.000, 0.700, 0.700, 0.800, 0.800),
(17, 0.400, 0.400, 1.000, 0.900, 0.400, 0.500, 0.600),
(18, 0.500, 0.750, 0.000, 0.700, 0.600, 0.500, 0.600),
(200, 0.800, 0.000, 0.200, 0.000, 0.400, 0.200, 0.000),
(201, -0.800, 0.000, -0.200, 0.000, -0.400, -0.200, 0.000),
(202, 0.000, 0.400, 0.850, 0.900, 0.000, 0.400, 0.200),
(203, 0.000, -0.400, -0.850, -0.900, 0.000, -0.400, -0.200),
(204, 0.200, 0.200, 0.400, 0.300, 0.300, 0.300, 0.900),
(205, -0.200, -0.200, -0.400, -0.300, -0.300, -0.300, -0.900),
(206, 0.300, 0.000, 0.300, 0.300, 0.200, 0.900, 0.200),
(207, 0.100, 0.000, 0.000, 0.100, 0.100, 0.300, 0.100),
(208, -0.300, 0.000, -0.300, -0.300, -0.200, -0.900, -0.200),
(209, 0.200, 1.000, 0.000, 0.300, 0.000, 0.200, 0.100),
(210, -0.200, -1.000, 0.000, -0.300, 0.000, -0.200, -0.100),
(211, 0.200, 0.000, 0.000, 0.200, 0.000, 0.200, 0.100),
(212, -0.200, 0.000, 0.000, -0.200, 0.000, -0.200, -0.100);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `frzk_funktionsklasse_weight`
--
ALTER TABLE `frzk_funktionsklasse_weight`
  ADD PRIMARY KEY (`funktionsklasse_id`);

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `frzk_funktionsklasse_weight`
--
ALTER TABLE `frzk_funktionsklasse_weight`
  ADD CONSTRAINT `frzk_funktionsklasse_weight_ibfk_1` FOREIGN KEY (`funktionsklasse_id`) REFERENCES `frzk_funktionsklasse` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
