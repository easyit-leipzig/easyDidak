-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 10. Apr 2026 um 12:18
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
-- Stellvertreter-Struktur des Views `ausw_type_0`
-- (Siehe unten für die tatsächliche Ansicht)
--
CREATE TABLE `ausw_type_0` (
`id` int(11)
,`wert_id` int(11)
,`type` int(11)
,`reihe` int(11)
,`x_kognition` float
,`x_sozial` float
,`x_affektiv` float
,`x_motivation` float
,`x_methodik` float
,`x_performanz` float
,`x_regulation` float
);

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `ausw_type_2`
-- (Siehe unten für die tatsächliche Ansicht)
--
CREATE TABLE `ausw_type_2` (
`id` int(11)
,`wert_id` int(11)
,`type` int(11)
,`reihe` int(11)
,`x_kognition` float
,`x_sozial` float
,`x_affektiv` float
,`x_motivation` float
,`x_methodik` float
,`x_performanz` float
,`x_regulation` float
);

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `ausw_type_3`
-- (Siehe unten für die tatsächliche Ansicht)
--
CREATE TABLE `ausw_type_3` (
`id` int(11)
,`wert_id` int(11)
,`type` int(11)
,`reihe` int(11)
,`x_kognition` float
,`x_sozial` float
,`x_affektiv` float
,`x_motivation` float
,`x_methodik` float
,`x_performanz` float
,`x_regulation` float
);

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `ausw_werte_reihe_1`
-- (Siehe unten für die tatsächliche Ansicht)
--
CREATE TABLE `ausw_werte_reihe_1` (
`id` int(11)
,`wert_id` int(11)
,`reihe` int(11)
,`x_kognition` float
,`x_sozial` float
,`x_affektiv` float
,`x_motivation` float
,`x_methodik` float
,`x_performanz` float
,`x_regulation` float
);

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `ausw_werte_type_4`
-- (Siehe unten für die tatsächliche Ansicht)
--
CREATE TABLE `ausw_werte_type_4` (
`id` int(11)
,`wert_id` int(11)
,`type` int(11)
,`reihe` int(11)
,`x_kognition` float
,`x_sozial` float
,`x_affektiv` float
,`x_motivation` float
,`x_methodik` float
,`x_performanz` float
,`x_regulation` float
);

-- --------------------------------------------------------

--
-- Struktur des Views `ausw_type_0`
--
DROP TABLE IF EXISTS `ausw_type_0`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ausw_type_0`  AS SELECT `ausw_werte`.`id` AS `id`, `ausw_werte`.`wert_id` AS `wert_id`, `ausw_werte`.`type` AS `type`, `ausw_werte`.`reihe` AS `reihe`, `ausw_werte`.`x_kognition` AS `x_kognition`, `ausw_werte`.`x_sozial` AS `x_sozial`, `ausw_werte`.`x_affektiv` AS `x_affektiv`, `ausw_werte`.`x_motivation` AS `x_motivation`, `ausw_werte`.`x_methodik` AS `x_methodik`, `ausw_werte`.`x_performanz` AS `x_performanz`, `ausw_werte`.`x_regulation` AS `x_regulation` FROM `ausw_werte` WHERE `ausw_werte`.`type` = 0 ;

-- --------------------------------------------------------

--
-- Struktur des Views `ausw_type_2`
--
DROP TABLE IF EXISTS `ausw_type_2`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ausw_type_2`  AS SELECT `ausw_werte`.`id` AS `id`, `ausw_werte`.`wert_id` AS `wert_id`, `ausw_werte`.`type` AS `type`, `ausw_werte`.`reihe` AS `reihe`, `ausw_werte`.`x_kognition` AS `x_kognition`, `ausw_werte`.`x_sozial` AS `x_sozial`, `ausw_werte`.`x_affektiv` AS `x_affektiv`, `ausw_werte`.`x_motivation` AS `x_motivation`, `ausw_werte`.`x_methodik` AS `x_methodik`, `ausw_werte`.`x_performanz` AS `x_performanz`, `ausw_werte`.`x_regulation` AS `x_regulation` FROM `ausw_werte` WHERE `ausw_werte`.`type` = 2 ;

-- --------------------------------------------------------

--
-- Struktur des Views `ausw_type_3`
--
DROP TABLE IF EXISTS `ausw_type_3`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ausw_type_3`  AS SELECT `ausw_werte`.`id` AS `id`, `ausw_werte`.`wert_id` AS `wert_id`, `ausw_werte`.`type` AS `type`, `ausw_werte`.`reihe` AS `reihe`, `ausw_werte`.`x_kognition` AS `x_kognition`, `ausw_werte`.`x_sozial` AS `x_sozial`, `ausw_werte`.`x_affektiv` AS `x_affektiv`, `ausw_werte`.`x_motivation` AS `x_motivation`, `ausw_werte`.`x_methodik` AS `x_methodik`, `ausw_werte`.`x_performanz` AS `x_performanz`, `ausw_werte`.`x_regulation` AS `x_regulation` FROM `ausw_werte` WHERE `ausw_werte`.`type` = 3 ;

-- --------------------------------------------------------

--
-- Struktur des Views `ausw_werte_reihe_1`
--
DROP TABLE IF EXISTS `ausw_werte_reihe_1`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ausw_werte_reihe_1`  AS SELECT `ausw_werte`.`id` AS `id`, `ausw_werte`.`wert_id` AS `wert_id`, `ausw_werte`.`reihe` AS `reihe`, `ausw_werte`.`x_kognition` AS `x_kognition`, `ausw_werte`.`x_sozial` AS `x_sozial`, `ausw_werte`.`x_affektiv` AS `x_affektiv`, `ausw_werte`.`x_motivation` AS `x_motivation`, `ausw_werte`.`x_methodik` AS `x_methodik`, `ausw_werte`.`x_performanz` AS `x_performanz`, `ausw_werte`.`x_regulation` AS `x_regulation` FROM `ausw_werte` WHERE `ausw_werte`.`reihe` = 1 ;

-- --------------------------------------------------------

--
-- Struktur des Views `ausw_werte_type_4`
--
DROP TABLE IF EXISTS `ausw_werte_type_4`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ausw_werte_type_4`  AS SELECT `ausw_werte`.`id` AS `id`, `ausw_werte`.`wert_id` AS `wert_id`, `ausw_werte`.`type` AS `type`, `ausw_werte`.`reihe` AS `reihe`, `ausw_werte`.`x_kognition` AS `x_kognition`, `ausw_werte`.`x_sozial` AS `x_sozial`, `ausw_werte`.`x_affektiv` AS `x_affektiv`, `ausw_werte`.`x_motivation` AS `x_motivation`, `ausw_werte`.`x_methodik` AS `x_methodik`, `ausw_werte`.`x_performanz` AS `x_performanz`, `ausw_werte`.`x_regulation` AS `x_regulation` FROM `ausw_werte` WHERE `ausw_werte`.`type` = 4 ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
