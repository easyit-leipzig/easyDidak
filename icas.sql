-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 25. Okt 2025 um 18:01
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
-- Tabellenstruktur für Tabelle `frzk_interdependenz`
--

CREATE TABLE `frzk_interdependenz` (
  `interdependenz_id` int(11) NOT NULL,
  `zeitpunkt` datetime NOT NULL COMMENT 'Zeitpunkt der Berechnung',
  `x_kognition` decimal(5,2) DEFAULT NULL COMMENT 'Mittelwert / Messwert kognitive Dimension',
  `y_sozial` decimal(5,2) DEFAULT NULL COMMENT 'Mittelwert / Messwert soziale Dimension',
  `z_affektiv` decimal(5,2) DEFAULT NULL COMMENT 'Mittelwert / Messwert affektive Dimension',
  `h_bedeutung` decimal(5,2) DEFAULT NULL COMMENT 'Dichtewert aus semantischer Funktion',
  `korrelationsscore` decimal(5,2) DEFAULT NULL COMMENT 'Berechneter Zusammenhang (z. B. Pearson-Korrelation)',
  `methode` varchar(100) DEFAULT NULL COMMENT 'Angabe der Berechnungsmethode (z. B. Korrelationsanalyse, Regressionsmodell)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_loops`
--

CREATE TABLE `frzk_loops` (
  `loop_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL COMMENT 'Referenz auf Teilnehmer',
  `startzeit` datetime NOT NULL COMMENT 'Beginn des Loop-Prozesses',
  `endzeit` datetime DEFAULT NULL COMMENT 'Ende des Loop-Prozesses (NULL = noch offen)',
  `verdichtungsgrad` decimal(5,2) DEFAULT NULL COMMENT 'Maß für die semantische Verdichtung in diesem Loop (z. B. aus σ-Werten berechnet)',
  `pausenmarker` tinyint(1) DEFAULT 0 COMMENT 'Kennzeichnung, ob es sich um eine Pause/Stillstand-Phase handelt',
  `anmerkung` text DEFAULT NULL COMMENT 'Freitext für Beobachtungen zum Loop (z. B. Wechsel von stabil → instabil)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_operatoren`
--

CREATE TABLE `frzk_operatoren` (
  `operator_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL COMMENT 'Referenz auf Teilnehmer (Verknüpfung zu ue_zuweisung_teilnehmer)',
  `zeitpunkt` datetime NOT NULL COMMENT 'Zeitpunkt der Beobachtung / Messung',
  `sigma_level` decimal(5,2) DEFAULT NULL COMMENT 'Stärke der σ-Operatoren (Semantisierung, Bedeutungserzeugung)',
  `m_level` decimal(5,2) DEFAULT NULL COMMENT 'Stärke der M-Operatoren (Meta-Reflexion, Abstraktion)',
  `r_level` decimal(5,2) DEFAULT NULL COMMENT 'Stärke der R-Operatoren (Resonanz, Rückkopplung)',
  `e_level` decimal(5,2) DEFAULT NULL COMMENT 'Stärke der E-Operatoren (Emergenz, neues Auftreten)',
  `bemerkung` text DEFAULT NULL COMMENT 'Freitextfeld für qualitative Notizen oder Kodierungen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_reflexion`
--

CREATE TABLE `frzk_reflexion` (
  `reflexion_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) DEFAULT NULL,
  `beobachter_id` int(11) DEFAULT NULL COMMENT 'ID des Beobachters (z. B. Lehrkraft, Forscher)',
  `ebene` enum('Selbst','Gruppe','Lehrkraft','Forscher') DEFAULT NULL COMMENT 'Ebene der Reflexion',
  `datum` datetime NOT NULL COMMENT 'Zeitpunkt der Reflexion',
  `reflexionstext` text DEFAULT NULL COMMENT 'Inhaltliche Reflexion (z. B. Metakommentar, Tagebuch, Lehrkraftprotokoll)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_semantische_dichte`
--

CREATE TABLE `frzk_semantische_dichte` (
  `id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `zeitpunkt` datetime NOT NULL,
  `x_kognition` float NOT NULL,
  `y_sozial` float NOT NULL,
  `z_affektiv` float NOT NULL,
  `h_bedeutung` float NOT NULL,
  `dh_dt` float DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `stabilitaet_score` float DEFAULT NULL,
  `transitions_marker` varchar(50) DEFAULT NULL,
  `bemerkung` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_setze_hub`
--

CREATE TABLE `frzk_setze_hub` (
  `id` int(11) NOT NULL,
  `datum` datetime NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `frzk_transitions`
--

CREATE TABLE `frzk_transitions` (
  `transition_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL COMMENT 'Referenz auf semantische Cluster (aus frzk_semantische_dichte)',
  `teilnehmer_id` int(11) DEFAULT NULL COMMENT 'Beteiligter Akteur',
  `zeitpunkt` datetime NOT NULL COMMENT 'Zeitpunkt des Übergangs',
  `typ` varchar(50) DEFAULT NULL COMMENT 'Art des Übergangs: ''Neue Struktur'', ''Stabilisierung'', ''Irritation'', ''Bedeutungswechsel''',
  `indikator_score` decimal(5,2) DEFAULT NULL COMMENT 'Quantitatives Maß für die Übergangsstärke (z. B. σ-Sprung)',
  `kommentar` text DEFAULT NULL COMMENT 'Qualitative Notizen zum Kontext'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_didaktik`
--

CREATE TABLE `mtr_didaktik` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `datum` datetime NOT NULL,
  `themenauswahl` int(1) DEFAULT 4,
  `methodenvielfalt` int(1) DEFAULT 4,
  `individualisierung` int(1) DEFAULT 4,
  `aufforderung` int(1) DEFAULT 4,
  `materialien` int(1) DEFAULT 4,
  `zielgruppen` int(1) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mtr_didaktik`
--

INSERT INTO `mtr_didaktik` (`id`, `ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `datum`, `themenauswahl`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `materialien`, `zielgruppen`) VALUES
(1, 47, 2, '2025-09-22 15:35:00', 1, 2, 2, 1, 2, 2),
(2, 48, 16, '2025-09-22 17:10:00', 1, 1, 2, 2, 1, 1),
(3, 49, 13, '2025-09-22 17:10:00', 1, 1, 1, 1, 1, 1),
(4, 50, 21, '2025-09-22 17:10:00', 1, 1, 2, 1, 1, 1),
(5, 51, 20, '2025-09-22 17:10:00', 2, 1, 1, 1, 2, 2),
(6, 52, 23, '2025-09-22 17:10:00', 2, 2, 1, 2, 3, 3),
(7, 53, 16, '2025-09-23 15:35:00', 1, 1, 1, 1, 1, 1),
(8, 54, 9, '2025-09-23 17:10:00', 2, 2, 1, 1, 1, 1),
(9, 55, 8, '2025-09-23 17:10:00', 1, 2, 3, 3, 2, 2),
(10, 56, 24, '2025-09-23 17:10:00', 1, 1, 1, 1, 1, 1),
(11, 58, 6, '2025-09-24 15:35:00', 2, 2, 2, 2, 2, 2),
(12, 59, 4, '2025-09-24 15:35:00', 2, 3, 3, 2, 3, 3),
(13, 60, 7, '2025-09-24 17:10:00', 2, 2, 3, 3, 2, 2),
(14, 61, 14, '2025-09-24 17:10:00', 1, 1, 1, 1, 1, 1),
(15, 62, 22, '2025-09-24 17:10:00', 1, 2, 1, 1, 1, 1),
(16, 63, 13, '2025-09-24 17:10:00', 2, 2, 2, 2, 1, 1),
(17, 75, 2, '2025-09-29 15:35:00', 2, 1, 2, 1, 2, 2),
(18, 76, 21, '2025-09-29 17:10:00', 1, 1, 2, 1, 1, 1),
(19, 77, 20, '2025-09-29 17:10:00', 1, 1, 1, 1, 1, 1),
(20, 78, 16, '2025-09-29 17:10:00', 1, 1, 1, 1, 1, 1),
(21, 79, 23, '2025-09-29 17:10:00', 2, 2, 2, 2, 2, 2),
(22, 80, 3, '2025-09-30 15:35:00', 1, 1, 1, 1, 2, 2),
(23, 81, 7, '2025-09-30 17:10:00', 1, 1, 2, 2, 2, 2),
(24, 82, 9, '2025-09-30 17:10:00', 1, 2, 2, 6, 5, 5),
(25, 83, 8, '2025-09-30 17:10:00', 3, 2, 3, 2, 1, 1),
(26, 84, 24, '2025-09-30 17:10:00', 2, 1, 1, 2, 3, 3),
(27, 85, 5, '2025-09-30 17:10:00', 0, 2, 2, 2, 0, 0),
(28, 86, 6, '2025-10-01 15:35:00', 2, 2, 2, 2, 2, 2),
(29, 87, 11, '2025-10-01 15:35:00', 2, 2, 2, 2, 1, 1),
(30, 88, 2, '2025-10-01 15:35:00', 2, 2, 2, 2, 1, 1),
(31, 90, 12, '2025-10-01 17:10:00', 1, 1, 2, 1, 1, 1),
(32, 91, 14, '2025-10-01 17:10:00', 1, 1, 1, 2, 1, 1),
(33, 92, 22, '2025-10-01 17:10:00', 1, 1, 1, 1, 1, 1),
(34, 93, 9, '2025-10-02 15:35:00', 1, 2, 1, 2, 2, 2),
(35, 95, 18, '2025-10-02 17:10:00', 1, 2, 2, 2, 2, 2),
(36, 96, 16, '2025-10-02 17:10:00', 1, 1, 1, 1, 1, 1),
(37, 97, 19, '2025-10-02 17:10:00', 1, 2, 2, 2, 2, 2),
(38, 98, 17, '2025-10-02 17:10:00', 1, 1, 1, 1, 2, 2),
(39, 99, 22, '2025-10-07 17:10:00', 1, 1, 1, 1, 1, 1),
(40, 100, 8, '2025-10-07 17:10:00', 2, 2, 2, 2, 1, 1),
(41, 101, 13, '2025-10-07 17:10:00', 2, 2, 2, 1, 2, 2),
(42, 102, 2, '2025-10-20 15:35:00', 2, 2, 2, 2, 1, 1),
(43, 104, 21, '2025-10-20 17:10:00', 2, 1, 1, 1, 2, 2),
(44, 105, 21, '2025-10-20 17:10:00', 2, 1, 1, 1, 2, 2),
(45, 106, 20, '2025-10-20 17:10:00', 1, 1, 1, 1, 1, 1),
(46, 107, 23, '2025-10-20 17:10:00', 1, 2, 2, 2, 1, 1),
(47, 108, 3, '2025-10-21 15:35:00', 1, 1, 1, 1, 1, 1),
(48, 109, 7, '2025-10-21 17:10:00', 1, 3, 2, 2, 2, 2),
(49, 110, 8, '2025-10-21 17:10:00', 2, 2, 2, 2, 2, 2),
(50, 111, 5, '2025-10-21 17:10:00', 2, 2, 2, 1, 2, 2),
(51, 112, 6, '2025-10-22 15:35:00', 2, 2, 2, 2, 2, 2),
(52, 113, 22, '2025-10-22 17:10:00', 1, 1, 1, 2, 1, 1),
(53, 114, 7, '2025-10-22 17:10:00', 1, 2, 1, 3, 1, 1),
(54, 115, 14, '2025-10-22 17:10:00', 2, 2, 2, 1, 2, 2),
(55, 116, 16, '2025-10-23 15:35:00', 1, 1, 1, 1, 1, 1),
(56, 117, 15, '2025-10-23 15:35:00', 3, 1, 2, 4, 2, 2),
(57, 118, 18, '2025-10-23 15:35:00', 2, 2, 1, 1, 2, 2),
(58, 119, 18, '2025-10-23 17:10:00', 1, 2, 1, 2, 2, 2),
(59, 120, 16, '2025-10-23 17:10:00', 1, 1, 2, 1, 1, 1),
(60, 121, 17, '2025-10-23 17:10:00', 3, 2, 1, 1, 1, 1),
(61, 122, 20, '2025-10-24 15:35:00', 1, 1, 1, 1, 1, 1),
(62, 123, 6, '2025-10-24 15:35:00', 2, 2, 2, 2, 2, 2),
(63, 124, 4, '2025-10-24 15:35:00', 3, 2, 3, 4, 4, 4);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_emotions`
--

CREATE TABLE `mtr_emotions` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  `emotions` varchar(100) NOT NULL,
  `freude` tinyint(1) NOT NULL DEFAULT 0,
  `zufriedenheit` tinyint(1) NOT NULL DEFAULT 0,
  `erfuellung` tinyint(1) NOT NULL DEFAULT 0,
  `motivation` tinyint(1) NOT NULL DEFAULT 0,
  `dankbarkeit` tinyint(1) NOT NULL DEFAULT 0,
  `hoffnung` tinyint(1) NOT NULL DEFAULT 0,
  `stolz` tinyint(1) NOT NULL DEFAULT 0,
  `selbstvertrauen` tinyint(1) NOT NULL DEFAULT 0,
  `neugier` tinyint(1) NOT NULL DEFAULT 0,
  `inspiration` tinyint(1) NOT NULL DEFAULT 0,
  `zugehoerigkeit` tinyint(1) NOT NULL DEFAULT 0,
  `vertrauen` tinyint(1) NOT NULL DEFAULT 0,
  `spass` tinyint(1) NOT NULL DEFAULT 0,
  `sicherheit` tinyint(1) NOT NULL DEFAULT 0,
  `frustration` tinyint(1) NOT NULL DEFAULT 0,
  `ueberforderung` tinyint(1) NOT NULL DEFAULT 0,
  `angst` tinyint(1) NOT NULL DEFAULT 0,
  `langeweile` tinyint(1) NOT NULL DEFAULT 0,
  `scham` tinyint(1) NOT NULL DEFAULT 0,
  `zweifel` tinyint(1) NOT NULL DEFAULT 0,
  `resignation` tinyint(1) NOT NULL DEFAULT 0,
  `erschoepfung` tinyint(1) NOT NULL DEFAULT 0,
  `interesse` tinyint(1) NOT NULL DEFAULT 0,
  `verwirrung` tinyint(1) NOT NULL DEFAULT 0,
  `unsicherheit` tinyint(1) NOT NULL DEFAULT 0,
  `ueberraschung` tinyint(1) NOT NULL DEFAULT 0,
  `erwartung` tinyint(1) NOT NULL DEFAULT 0,
  `erleichterung` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mtr_emotions`
--

INSERT INTO `mtr_emotions` (`id`, `ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `datum`, `emotions`, `freude`, `zufriedenheit`, `erfuellung`, `motivation`, `dankbarkeit`, `hoffnung`, `stolz`, `selbstvertrauen`, `neugier`, `inspiration`, `zugehoerigkeit`, `vertrauen`, `spass`, `sicherheit`, `frustration`, `ueberforderung`, `angst`, `langeweile`, `scham`, `zweifel`, `resignation`, `erschoepfung`, `interesse`, `verwirrung`, `unsicherheit`, `ueberraschung`, `erwartung`, `erleichterung`) VALUES
(1, 34, 21, '2025-09-08 18:29:34', '3,28,1', 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(2, 27, 12, '2025-09-08 18:30:22', '1', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3, 36, 20, '2025-09-08 18:32:37', '6,23,2', 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(4, 14, 16, '2025-09-08 18:32:52', '23', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(5, 19, 3, '2025-09-09 17:01:01', '6,25,24,2', 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0),
(6, 3, 9, '2025-09-09 18:29:48', '22', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(7, 14, 16, '2025-09-09 18:33:49', '22', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(8, 6, 5, '2025-09-09 18:37:51', '22,1,15,6,23,4,25,20', 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1, 0, 0, 0),
(9, 25, 11, '2025-09-10 16:58:02', '1,13,2', 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(10, 24, 6, '2025-09-10 16:58:08', '3,28,1', 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(11, 26, 7, '2025-09-10 18:29:44', '22,4,25', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0),
(12, 28, 14, '2025-09-10 18:32:02', '15,25,20', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0),
(13, 30, 22, '2025-09-10 18:33:32', '5,4,7,25', 0, 0, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(14, 15, 9, '2025-09-11 16:52:16', '5,22,27,1,6,10,23,18,4,9,14,13,7,16,26', 1, 0, 0, 1, 1, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0, 1, 0, 1, 0, 0, 0, 1, 1, 0, 0, 1, 1, 0),
(15, 12, 15, '2025-09-11 16:56:12', '17,5,22,4,16,25,24,20', 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 1, 1, 0, 0, 0),
(16, 13, 10, '2025-09-11 16:56:20', '28,1,6,23,8,13,16,25,2', 1, 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 1),
(17, 16, 18, '2025-09-11 18:24:24', '2', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(18, 13, 10, '2025-09-11 18:25:26', '28', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(19, 14, 16, '2025-09-11 18:27:38', '27,23,24', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, 0),
(20, 17, 17, '2025-09-11 18:30:17', '1,6,14,13', 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(21, 33, 2, '2025-09-15 16:58:04', '22,25,12,24,11,20', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 1, 1, 0, 0, 0),
(22, 34, 21, '2025-09-15 18:29:26', '2', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(23, 35, 16, '2025-09-15 18:30:06', '27,6,23', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0),
(24, 36, 20, '2025-09-15 18:32:03', '4,24', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0),
(25, 37, 23, '2025-09-15 18:35:16', '5,23,13,25,2', 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0),
(26, 38, 10, '2025-09-17 16:53:46', '28,22,6,23,18,9,8,14,26,25,12,24,2,11,20', 0, 1, 0, 0, 0, 1, 0, 1, 1, 0, 1, 1, 0, 1, 0, 0, 0, 1, 0, 1, 0, 1, 1, 1, 1, 1, 0, 1),
(27, 39, 2, '2025-09-17 16:57:01', '3,28,1,23,4,8,13', 1, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(28, 27, 12, '2025-09-17 18:30:55', '5,3,28,1,6', 1, 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(29, 28, 14, '2025-09-17 18:33:20', '23,8,13,2', 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(30, 15, 9, '2025-09-18 16:45:27', '5,28,22,27,1,6,18,4,14,13', 1, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1),
(31, 38, 10, '2025-09-18 16:51:06', '22,27,1,6,10,23,18,8,14,13,26,25,24,2', 1, 1, 0, 0, 0, 1, 0, 1, 0, 1, 0, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0),
(32, 17, 17, '2025-09-18 18:29:09', '1,6,23,13', 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(33, 18, 19, '2025-09-18 18:32:30', '5,28,2', 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(34, 42, 6, '2025-09-19 17:00:32', '3,6,10', 0, 0, 1, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(35, 43, 20, '2025-09-19 17:01:16', '4,2', 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(36, 44, 11, '2025-09-19 17:04:08', '1,23', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(37, 45, 24, '2025-09-19 17:05:04', '5,28,6,10,23,14', 0, 0, 0, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(38, 46, 4, '2025-09-19 17:09:25', '17,5,15,6', 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(39, 47, 2, '2025-09-22 16:58:05', '3,28,1,23,4,8,13,2', 1, 1, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(40, 48, 16, '2025-09-22 18:25:20', '23', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(41, 49, 13, '2025-09-22 18:30:56', '5,1,7,2', 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(42, 50, 21, '2025-09-22 18:32:56', '23', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(43, 51, 20, '2025-09-22 18:35:46', '2', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(44, 52, 23, '2025-09-22 18:38:00', '5,23,4,14,13,2', 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(45, 53, 16, '2025-09-23 16:59:02', '1,23,4,9,14,13,7', 1, 0, 0, 1, 0, 0, 1, 0, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(46, 54, 9, '2025-09-23 18:32:13', '5,3,28,22,1,6,23,4,8,14,13', 1, 0, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(47, 55, 8, '2025-09-23 18:34:56', '22,1,18', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(48, 56, 24, '2025-09-23 18:36:24', '28,1,10,4,14', 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(49, 58, 6, '2025-09-24 15:35:00', '', 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(50, 59, 4, '2025-09-24 15:35:00', '', 1, 0, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(51, 60, 7, '2025-09-24 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0),
(52, 61, 14, '2025-09-24 17:10:00', '', 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(53, 62, 22, '2025-09-24 17:10:00', '', 1, 0, 0, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(54, 63, 13, '2025-09-24 17:10:00', '', 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(55, 75, 2, '2025-09-29 15:35:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0),
(56, 76, 21, '2025-09-29 17:10:00', '', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(57, 77, 20, '2025-09-29 17:10:00', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(58, 78, 16, '2025-09-29 17:10:00', '', 0, 0, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1),
(59, 79, 23, '2025-09-29 17:10:00', '', 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(60, 80, 3, '2025-09-30 15:35:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0),
(61, 81, 7, '2025-09-30 17:10:00', '', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(62, 82, 9, '2025-09-30 17:10:00', '', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1, 1, 0, 1, 0, 1, 1, 0, 0, 0),
(63, 83, 8, '2025-09-30 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(64, 84, 24, '2025-09-30 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(65, 85, 5, '2025-09-30 17:10:00', '', 1, 0, 0, 1, 1, 0, 1, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(66, 86, 6, '2025-10-01 15:35:00', '', 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(67, 87, 11, '2025-10-01 15:35:00', '', 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(68, 88, 2, '2025-10-01 15:35:00', '', 0, 1, 0, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(69, 90, 12, '2025-10-01 17:10:00', '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(70, 91, 14, '2025-10-01 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(71, 92, 22, '2025-10-01 17:10:00', '', 0, 1, 0, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(72, 93, 9, '2025-10-02 15:35:00', '', 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1, 0, 1),
(73, 95, 18, '2025-10-02 17:10:00', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(74, 96, 16, '2025-10-02 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0),
(75, 97, 19, '2025-10-02 17:10:00', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(76, 98, 17, '2025-10-02 17:10:00', '', 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(77, 99, 22, '2025-10-07 17:10:00', '', 0, 1, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(78, 100, 8, '2025-10-07 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(79, 101, 13, '2025-10-07 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(80, 102, 2, '2025-10-20 15:35:00', '', 0, 1, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(81, 104, 21, '2025-10-20 17:10:00', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(82, 105, 21, '2025-10-20 17:10:00', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(83, 106, 20, '2025-10-20 17:10:00', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(84, 107, 23, '2025-10-20 17:10:00', '', 1, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(85, 108, 3, '2025-10-21 15:35:00', '', 1, 1, 0, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(86, 109, 7, '2025-10-21 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0),
(87, 110, 8, '2025-10-21 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(88, 111, 5, '2025-10-21 17:10:00', '', 1, 1, 0, 1, 1, 1, 1, 1, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0),
(89, 112, 6, '2025-10-22 15:35:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(90, 113, 22, '2025-10-22 17:10:00', '', 1, 1, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0),
(91, 114, 7, '2025-10-22 17:10:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(92, 115, 14, '2025-10-22 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0),
(93, 116, 16, '2025-10-23 15:35:00', '', 1, 1, 0, 1, 0, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1, 0, 1, 0, 1, 0, 1, 1, 0, 1, 1, 1, 1),
(94, 117, 15, '2025-10-23 15:35:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(95, 118, 18, '2025-10-23 15:35:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(96, 119, 18, '2025-10-23 17:10:00', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(97, 120, 16, '2025-10-23 17:10:00', '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(98, 121, 17, '2025-10-23 17:10:00', '', 1, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(99, 122, 20, '2025-10-24 15:35:00', '', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(100, 123, 6, '2025-10-24 15:35:00', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(101, 124, 4, '2025-10-24 15:35:00', '', 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_ethik`
--

CREATE TABLE `mtr_ethik` (
  `belastung_score` decimal(2,1) NOT NULL DEFAULT 0.0,
  `autonomie_empfinden` decimal(2,1) NOT NULL DEFAULT 0.0,
  `gerechtigkeit_empfinden` decimal(2,1) NOT NULL DEFAULT 0.0,
  `vertrauen_in_lehrkraft` decimal(2,1) NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_leistung`
--

CREATE TABLE `mtr_leistung` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  `teilnehmer_id` int(11) NOT NULL,
  `lernfortschritt` decimal(2,1) DEFAULT 4.0,
  `beherrscht_thema` decimal(2,1) DEFAULT 4.0,
  `transferdenken` decimal(2,1) DEFAULT 4.0,
  `basiswissen` decimal(2,1) DEFAULT 4.0,
  `vorbereitet` decimal(2,1) DEFAULT 4.0,
  `belastbarkeit` decimal(2,1) NOT NULL DEFAULT 4.0,
  `note` decimal(2,1) NOT NULL DEFAULT 4.0,
  `verhaltensbeurteilung_code` varchar(255) DEFAULT '' COMMENT 'Zusätzlicher Code zur Bewertung z.B. Chips-Auswahl',
  `reflexionshinweis` text DEFAULT NULL COMMENT 'Freitext für didaktische oder diagnostische Reflexion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mtr_leistung`
--

INSERT INTO `mtr_leistung` (`id`, `ue_zuweisung_teilnehmer_id`, `datum`, `teilnehmer_id`, `lernfortschritt`, `beherrscht_thema`, `transferdenken`, `basiswissen`, `vorbereitet`, `belastbarkeit`, `note`, `verhaltensbeurteilung_code`, `reflexionshinweis`) VALUES
(1, 3, '2025-09-09 17:10:00', 9, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(2, 4, '2025-09-09 17:10:00', 7, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(3, 5, '2025-09-09 17:10:00', 8, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(4, 6, '2025-09-09 17:10:00', 5, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(5, 11, '2025-09-11 15:35:00', 9, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(6, 12, '2025-09-11 15:35:00', 15, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(7, 13, '2025-09-11 15:35:00', 10, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(8, 14, '2025-09-11 17:10:00', 16, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(9, 15, '2025-09-11 17:10:00', 9, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(10, 16, '2025-09-11 17:10:00', 18, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(11, 17, '2025-09-11 17:10:00', 17, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(12, 18, '2025-09-11 17:10:00', 19, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(13, 19, '2025-09-09 15:35:00', 3, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(14, 24, '2025-09-10 15:35:00', 6, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(15, 25, '2025-09-10 15:35:00', 11, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(16, 26, '2025-09-10 17:10:00', 7, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(17, 27, '2025-09-10 17:10:00', 12, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(18, 28, '2025-09-10 17:10:00', 14, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(19, 29, '2025-09-10 17:10:00', 13, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(20, 30, '2025-09-10 17:10:00', 22, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(21, 33, '2025-09-15 15:35:00', 2, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(22, 34, '2025-09-15 17:10:00', 21, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(23, 35, '2025-09-15 17:10:00', 16, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(24, 36, '2025-09-15 17:10:00', 20, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(25, 37, '2025-09-15 17:10:00', 23, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(26, 38, '2025-09-17 15:35:00', 10, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(27, 39, '2025-09-17 15:35:00', 2, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(28, 40, '2025-09-10 17:10:00', 12, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(29, 41, '2025-09-10 17:10:00', 14, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(30, 42, '2025-09-19 15:35:00', 6, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(31, 43, '2025-09-19 15:35:00', 20, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(32, 44, '2025-09-19 15:35:00', 11, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(33, 45, '2025-09-19 15:35:00', 24, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(34, 46, '2025-09-19 15:35:00', 4, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(35, 47, '2025-09-22 15:35:00', 2, 1.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.5, '', NULL),
(36, 48, '2025-09-22 17:10:00', 16, 2.0, 3.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(37, 49, '2025-09-22 17:10:00', 13, 1.0, 2.0, 3.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(38, 50, '2025-09-22 17:10:00', 21, 1.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(39, 51, '2025-09-22 17:10:00', 20, 2.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(40, 52, '2025-09-22 17:10:00', 23, 2.0, 2.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(41, 53, '2025-09-23 15:35:00', 16, 1.0, 1.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(42, 54, '2025-09-23 17:10:00', 9, 3.0, 3.0, 2.0, 4.0, 4.0, 4.0, 3.7, '', NULL),
(43, 55, '2025-09-23 17:10:00', 8, 3.0, 3.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(44, 56, '2025-09-23 17:10:00', 24, 1.0, 2.0, 2.0, 4.0, 3.0, 4.0, 4.0, '', NULL),
(45, 58, '2025-09-24 15:35:00', 6, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.5, '', NULL),
(46, 59, '2025-09-24 15:35:00', 4, 1.0, 4.0, 3.0, 4.0, 3.0, 4.0, 4.0, '', NULL),
(47, 60, '2025-09-24 17:10:00', 7, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.9, '', NULL),
(48, 61, '2025-09-24 17:10:00', 14, 1.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(49, 62, '2025-09-24 17:10:00', 22, 3.0, 4.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(50, 63, '2025-09-24 17:10:00', 13, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(51, 64, '2025-09-25 15:35:00', 9, 0.0, 1.0, 2.0, 4.0, 3.0, 4.0, 3.7, '', NULL),
(52, 65, '2025-09-25 15:35:00', 15, 2.0, 3.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(53, 67, '2025-09-25 17:10:00', 18, 1.0, 1.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(54, 68, '2025-09-25 17:10:00', 16, 1.0, 2.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(55, 69, '2025-09-25 17:10:00', 19, 1.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(56, 70, '2025-09-25 17:10:00', 17, 3.0, 4.0, 4.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(57, 71, '2025-09-26 15:35:00', 4, 2.0, 4.0, 2.0, 4.0, 4.0, 4.0, 4.0, '', NULL),
(58, 72, '2025-09-26 15:35:00', 20, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(59, 73, '2025-09-26 15:35:00', 24, 1.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(61, 75, '2025-09-29 15:35:00', 2, 3.0, 3.0, 3.0, 4.0, 2.0, 4.0, 3.0, '', NULL),
(62, 76, '2025-09-29 17:10:00', 21, 1.0, 2.0, 2.0, 4.0, 3.0, 4.0, 4.0, '', NULL),
(63, 77, '2025-09-29 17:10:00', 20, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(64, 78, '2025-09-29 17:10:00', 16, 1.0, 2.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(65, 79, '2025-09-29 17:10:00', 23, 3.0, 3.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(66, 80, '2025-09-30 15:35:00', 3, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(67, 81, '2025-09-30 17:10:00', 7, 3.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.9, '', NULL),
(68, 82, '2025-09-30 17:10:00', 9, 2.0, 5.0, 1.0, 4.0, 3.0, 4.0, 3.7, '', NULL),
(69, 83, '2025-09-30 17:10:00', 8, 3.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(70, 84, '2025-09-30 17:10:00', 24, 1.0, 2.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(71, 85, '2025-09-30 17:10:00', 5, 1.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.3, '', NULL),
(72, 86, '2025-10-01 15:35:00', 6, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.5, '', NULL),
(73, 87, '2025-10-01 15:35:00', 11, 1.0, 2.0, 2.0, 4.0, 2.0, 4.0, 5.0, '', NULL),
(74, 88, '2025-10-01 15:35:00', 2, 1.0, 2.0, 1.0, 4.0, 1.0, 4.0, 3.0, '', NULL),
(75, 90, '2025-10-01 17:10:00', 12, 1.0, 1.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(76, 91, '2025-10-01 17:10:00', 14, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(77, 92, '2025-10-01 17:10:00', 22, 2.0, 3.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(78, 93, '2025-10-02 15:35:00', 9, 2.0, 1.0, 2.0, 4.0, 2.0, 4.0, 3.7, '', NULL),
(79, 95, '2025-10-02 17:10:00', 18, 2.0, 2.0, 1.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(80, 96, '2025-10-02 17:10:00', 16, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(81, 97, '2025-10-02 17:10:00', 19, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(82, 98, '2025-10-02 17:10:00', 17, 3.0, 3.0, 3.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(83, 99, '2025-10-07 17:10:00', 22, 2.0, 3.0, 3.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(84, 100, '2025-10-07 17:10:00', 8, 2.0, 2.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(85, 101, '2025-10-07 17:10:00', 13, 1.0, 1.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(86, 102, '2025-10-20 15:35:00', 2, 1.0, 1.0, 2.0, 4.0, 2.0, 4.0, 3.0, '', NULL),
(87, 104, '2025-10-20 17:10:00', 21, 3.0, 3.0, 3.0, 4.0, 3.0, 4.0, 4.0, '', NULL),
(88, 105, '2025-10-20 17:10:00', 21, 3.0, 3.0, 3.0, 4.0, 3.0, 4.0, 4.0, '', NULL),
(89, 106, '2025-10-20 17:10:00', 20, 1.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(90, 107, '2025-10-20 17:10:00', 23, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(91, 108, '2025-10-21 15:35:00', 3, 1.0, 1.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(92, 109, '2025-10-21 17:10:00', 7, 3.0, 3.0, 3.0, 4.0, 1.0, 4.0, 4.9, '', NULL),
(93, 110, '2025-10-21 17:10:00', 8, 3.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(94, 111, '2025-10-21 17:10:00', 5, 2.0, 2.0, 3.0, 4.0, 1.0, 4.0, 4.3, '', NULL),
(95, 112, '2025-10-22 15:35:00', 6, 3.0, 3.0, 3.0, 4.0, 3.0, 4.0, 4.5, '', NULL),
(96, 113, '2025-10-22 17:10:00', 22, 2.0, 3.0, 3.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(97, 114, '2025-10-22 17:10:00', 7, 2.0, 3.0, 3.0, 4.0, 2.0, 4.0, 4.9, '', NULL),
(98, 115, '2025-10-22 17:10:00', 14, 2.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(99, 116, '2025-10-23 15:35:00', 16, 1.0, 1.0, 1.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(100, 117, '2025-10-23 15:35:00', 15, 2.0, 4.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(101, 118, '2025-10-23 15:35:00', 18, 2.0, 1.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(102, 119, '2025-10-23 17:10:00', 18, 3.0, 3.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(103, 120, '2025-10-23 17:10:00', 16, 3.0, 3.0, 2.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(104, 121, '2025-10-23 17:10:00', 17, 1.0, 3.0, 3.0, 4.0, 1.0, 4.0, 4.0, '', NULL),
(105, 122, '2025-10-24 15:35:00', 20, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.0, '', NULL),
(106, 123, '2025-10-24 15:35:00', 6, 2.0, 2.0, 2.0, 4.0, 2.0, 4.0, 4.5, '', NULL),
(107, 124, '2025-10-24 15:35:00', 4, 2.0, 3.0, 4.0, 4.0, 4.0, 4.0, 4.0, '', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_persoenlichkeit`
--

CREATE TABLE `mtr_persoenlichkeit` (
  `id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `offenheit_erfahrungen` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Sucht aktiv nach neuen "Feldzuständen" (Lerninhalten, Situationen), experimentiert mit verschiedenen "Akteur-Funktionen" (Lernstrategien, Verhaltensweisen), ist offen für die Transformation symbolischer Meta-Strukturen.\r\nNiedrige Ausprägung: Bevorzugt bekannte "Feldzustände", vermeidet neue Verhaltensmuster, hält an etablierten symbolischen Ordnungen fest.',
  `gewissenhaftigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Richtet seine "Akteur-Funktion" auf die systematische Verfolgung definierter Ziele aus, zeigt Ausdauer bei der Bearbeitung von Aufgaben im "Feld", reguliert seine "Meta-Funktion" zur Selbstüberwachung und -korrektur im Lernprozess.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Zielverfolgung, geringe Ausdauer, impulsive oder wenig geplante "Akteur-Funktionen".',
  `Extraversion` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Zeigt eine starke Tendenz zur Bildung gekoppelter "Akteur-Funktionen" in sozialen Feldern, sucht aktiv soziale "Feldzustände" auf, zeigt eine hohe "Handlungsdichte" im sozialen Kontext.\r\nNiedrige Ausprägung: Geringere Tendenz zu gekoppelten "Akteur-Funktionen", vermeidet soziale "Feldzustände", zeigt weniger "Handlungen" im sozialen Kontext.',
  `vertraeglichkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Passt seine "Akteur-Funktion" an die der Ko-Akteure in sozialen Feldern an, zeigt Kooperationsbereitschaft, ist empfänglich für die semantischen Attraktoren gemeinsamer sozialer Narrative.\r\nNiedrige Ausprägung: Zeigt weniger Anpassung, ist weniger kooperativ, neigt zu Konflikten in gekoppelten "Akteur-Funktionen".',
  `zielorientierung` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Setzt sich aktiv Lernziele, verfolgt Aufgaben beharrlich, zeigt Eigeninitiative.\r\nNiedrige Ausprägung: Schwierigkeiten, Ziele zu formulieren oder zu verfolgen, geringe Eigenmotivation.',
  `lernfaehigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler.\r\n\r\nLernprozesse verändern die Struktur der Akteur-Funktionen. Ausprägungen zeigen sich in der Geschwindigkeit und Effizienz dieser funktionalen Anpassungen [citation: 6, 8].',
  `anpassungsfaehigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler.',
  `soziale_interaktion` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Kooperativ, kommuniziert offen, integriert sich gut in Gruppen, zeigt Empathie.\r\nNiedrige Ausprägung: Schwierigkeiten in der Zusammenarbeit, vermeidet Interaktion, soziale Konflikte.',
  `metakognition` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Denkt über eigenes Lernen nach, plant Lernprozesse, überwacht Verständnis, bewertet eigene Leistung realistisch.\r\nNiedrige Ausprägung: Wenig Bewusstsein für eigene Lernprozesse, Schwierigkeiten bei der Selbstbewertung.',
  `stressbewaeltigung` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Reagiert ängstlich auf Prüfungen oder neue Situationen, ist besorgt über Leistung, zeigt emotionale Labilität.\r\nNiedrige Ausprägung: Bleibt ruhig unter Druck, geht gelassen mit Unsicherheit um.\r\n\r\nKönnte sich in der erhöhten Reaktivität der Akteur-Funktion auf als bedrohlich interpretierte Feldzustände (z.B. Prüfungsdruck) oder in negativen Mustern der Meta-Funktion (z.B. negative Selbstbewertung) äußern [citation: 6, 7, 9, 11].',
  `bedeutungsbildung` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Hohe Ausprägung: Konstruiert kohärente Bedeutungen aus Lerninhalten, vernetzt Wissen, entwickelt eigene Interpretationen, findet Sinn im Gelernten.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Sinnstiftung, isolierte Wissensfragmente, wenig eigene Interpretationen.\r\n\r\nIntegrale Funktionalität (Kohärenzbildung, Kontextualisierung, Narrativierung, Wertschöpfung) synthetisiert lokale Beobachtungen zu globalen Bedeutungen. Ausprägungen zeigen sich in der Qualität und Struktur der konstruierten semantischen Felder und Narrative [citation: 1, 16, 11].',
  `belastbarkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Stress- und Drucktoleranz',
  `problemlösefähigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Transferleistung, Umgang mit Komplexität',
  `kreativität_innovation` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Verknüpfung, Entwicklung neuer Ansätze',
  `ko-kreationsfähigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Fähigkeit, mit anderen gemeinsam Neues zu schaffen',
  `resonanzfähigkeit` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Fähigkeit, auf Impulse aus der Umgebung zu antworten und sie produktiv aufzunehmen',
  `handlungsdichte` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'wie konzentriert und intensiv eine Person in einem bestimmten Zeitraum handelt',
  `performanz_effizienz` decimal(2,1) NOT NULL DEFAULT 4.0 COMMENT 'Output im Verhältnis zu eingesetztem Aufwand',
  `basiswissen` decimal(2,1) NOT NULL DEFAULT 4.0,
  `note` decimal(2,1) NOT NULL DEFAULT 4.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Offenheit für Erfahrungen, Gewissenhaftigkeit, Extraversion,';

--
-- Daten für Tabelle `mtr_persoenlichkeit`
--

INSERT INTO `mtr_persoenlichkeit` (`id`, `teilnehmer_id`, `datum`, `offenheit_erfahrungen`, `gewissenhaftigkeit`, `Extraversion`, `vertraeglichkeit`, `zielorientierung`, `lernfaehigkeit`, `anpassungsfaehigkeit`, `soziale_interaktion`, `metakognition`, `stressbewaeltigung`, `bedeutungsbildung`, `belastbarkeit`, `problemlösefähigkeit`, `kreativität_innovation`, `ko-kreationsfähigkeit`, `resonanzfähigkeit`, `handlungsdichte`, `performanz_effizienz`, `basiswissen`, `note`) VALUES
(1, 2, '2025-09-09', 3.9, 3.0, 4.2, 3.5, 4.5, 4.5, 4.0, 4.5, 4.5, 4.5, 4.5, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 3.0),
(2, 3, '2025-09-09', 3.5, 3.5, 4.0, 3.0, 3.5, 3.5, 4.5, 4.0, 4.0, 5.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(3, 4, '2025-09-09', 0.9, 4.0, 3.0, 3.0, 4.0, 4.0, 4.0, 4.0, 4.0, 0.0, 0.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(4, 5, '2025-09-09', 4.5, 3.5, 3.5, 3.2, 4.5, 4.5, 4.2, 3.5, 4.5, 5.0, 4.3, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.3),
(5, 6, '2025-09-09', 3.8, 3.5, 4.3, 3.5, 4.5, 4.5, 3.9, 4.0, 4.9, 4.4, 4.8, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.5),
(6, 7, '2025-09-09', 5.5, 5.5, 4.5, 5.0, 5.5, 5.5, 5.0, 4.5, 5.5, 5.0, 5.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.9),
(7, 8, '2025-09-09', 3.5, 4.0, 3.5, 3.5, 3.5, 3.5, 4.0, 3.5, 4.0, 5.0, 4.6, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(8, 9, '2025-09-09', 3.5, 3.4, 3.1, 3.0, 3.5, 4.0, 3.5, 3.5, 4.0, 3.7, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 3.7),
(9, 11, '2025-09-09', 5.0, 5.5, 4.5, 4.0, 5.0, 5.0, 5.0, 4.0, 4.5, 5.0, 5.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 5.0),
(10, 12, '2025-09-09', 3.5, 3.5, 3.0, 3.5, 3.5, 3.5, 3.5, 3.0, 3.5, 3.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(11, 13, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(12, 14, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(13, 15, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(14, 16, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(15, 17, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(16, 18, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(17, 19, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(18, 20, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(19, 21, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(20, 22, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(21, 23, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0),
(22, 24, '2025-09-09', 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0, 4.0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--

CREATE TABLE `mtr_rueckkopplung_lehrkraft_lesson` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_id` int(11) DEFAULT NULL,
  `erfasst_am` datetime DEFAULT current_timestamp(),
  `mitarbeit` decimal(2,1) DEFAULT 0.0,
  `absprachen` decimal(2,1) DEFAULT 0.0,
  `selbststaendigkeit` decimal(2,1) DEFAULT 0.0,
  `konzentration` decimal(2,1) DEFAULT 0.0,
  `fleiss` decimal(2,1) DEFAULT 0.0,
  `lernfortschritt` decimal(2,1) DEFAULT 0.0,
  `beherrscht_thema` decimal(2,1) DEFAULT 0.0,
  `transferdenken` decimal(2,1) DEFAULT 0.0,
  `basiswissen` decimal(2,1) DEFAULT 0.0,
  `vorbereitet` decimal(2,1) DEFAULT 0.0,
  `themenauswahl` decimal(2,1) DEFAULT 0.0,
  `materialien` decimal(2,1) DEFAULT 0.0,
  `methodenvielfalt` decimal(2,1) DEFAULT 0.0,
  `individualisierung` decimal(2,1) DEFAULT 0.0,
  `aufforderung` decimal(2,1) DEFAULT 0.0,
  `zielgruppen` decimal(2,1) DEFAULT 0.0,
  `note` decimal(2,1) DEFAULT 0.0,
  `emotions` varchar(100) NOT NULL,
  `bemerkungen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--

INSERT INTO `mtr_rueckkopplung_lehrkraft_lesson` (`id`, `ue_unterrichtseinheit_id`, `erfasst_am`, `mitarbeit`, `absprachen`, `selbststaendigkeit`, `konzentration`, `fleiss`, `lernfortschritt`, `beherrscht_thema`, `transferdenken`, `basiswissen`, `vorbereitet`, `themenauswahl`, `materialien`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `zielgruppen`, `note`, `emotions`, `bemerkungen`) VALUES
(1, 2, '2025-09-09 00:00:00', 2.0, 2.0, 3.2, 2.2, 1.6, 2.4, 1.7, 2.1, 4.0, 2.9, 1.9, 2.5, 0.0, 1.9, 2.4, 1.9, 4.0, '', ''),
(2, 3, '2025-09-09 00:00:00', 2.9, 2.6, 2.8, 3.2, 3.8, 3.5, 3.8, 3.4, 4.0, 2.4, 2.5, 2.3, 0.0, 2.8, 3.1, 3.4, 4.2, '', ''),
(3, 4, '2025-09-10 15:35:00', 3.7, 3.2, 3.4, 3.6, 2.7, 2.6, 3.6, 3.9, 4.0, 3.2, 3.3, 3.2, 0.0, 3.8, 3.6, 3.0, 4.8, '', ''),
(4, 5, '2025-09-10 17:10:00', 2.9, 2.8, 3.0, 2.3, 2.6, 2.1, 3.1, 2.8, 4.0, 2.6, 2.3, 1.8, 0.0, 2.0, 2.6, 2.2, 4.2, '', ''),
(5, 7, '2025-09-11 17:10:00', 2.4, 2.6, 3.5, 2.6, 2.7, 2.8, 3.6, 3.5, 4.0, 2.2, 2.0, 2.3, 0.0, 2.7, 2.8, 2.3, 3.9, '', ''),
(6, 10, '2025-09-15 15:35:00', 2.8, 3.4, 3.6, 3.5, 3.6, 2.4, 3.4, 3.3, 4.0, 2.7, 2.5, 3.0, 0.0, 2.5, 2.3, 2.9, 4.5, '', ''),
(7, 11, '2025-09-15 17:10:00', 2.9, 2.7, 2.9, 3.0, 2.8, 3.2, 2.6, 2.7, 4.0, 2.9, 2.5, 2.1, 0.0, 2.2, 1.9, 1.8, 4.0, '', ''),
(8, 12, '2025-09-17 15:35:00', 3.2, 2.8, 3.0, 3.9, 2.7, 2.5, 3.1, 3.1, 4.0, 2.5, 2.5, 2.3, 0.0, 2.8, 2.2, 2.1, 4.5, '', ''),
(9, 13, '2025-09-17 17:10:00', 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 0.0, 3.0, 3.0, 3.0, 3.0, '', ''),
(10, 14, '2025-09-18 15:35:00', 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 0.0, 3.0, 3.0, 3.0, 3.0, '', ''),
(11, 15, '2025-09-18 17:10:00', 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 0.0, 3.0, 3.0, 3.0, 3.0, '', ''),
(12, 18, '2025-09-22 17:10:00', 3.1, 2.8, 3.3, 3.0, 2.9, 2.5, 3.3, 3.0, 4.0, 2.5, 2.0, 1.9, 0.0, 2.6, 2.3, 2.1, 4.0, '', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_lehrkraft_tn`
--

CREATE TABLE `mtr_rueckkopplung_lehrkraft_tn` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) DEFAULT NULL,
  `erfasst_am` datetime DEFAULT current_timestamp(),
  `val_mitarbeit` int(11) DEFAULT NULL,
  `val_absprachen` int(11) DEFAULT NULL,
  `val_selbststaendigkeit` int(11) DEFAULT NULL,
  `val_konzentration` int(11) DEFAULT NULL,
  `val_fleiss` int(11) DEFAULT NULL,
  `val_lernfortschritt` int(11) DEFAULT NULL,
  `val_beherrscht_thema` int(11) DEFAULT NULL,
  `val_transferdenken` int(11) DEFAULT NULL,
  `val_basiswissen` int(11) DEFAULT NULL,
  `val_vorbereitet` int(11) DEFAULT NULL,
  `val_themenauswahl` int(11) DEFAULT NULL,
  `val_materialien` int(11) DEFAULT NULL,
  `val_methodenvielfalt` int(11) DEFAULT NULL,
  `val_individualisierung` int(11) DEFAULT NULL,
  `val_aufforderung` int(11) DEFAULT NULL,
  `val_emotions` varchar(100) NOT NULL,
  `bemerkungen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_teilnehmer`
--

CREATE TABLE `mtr_rueckkopplung_teilnehmer` (
  `id` int(11) NOT NULL COMMENT 'neu',
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) DEFAULT NULL,
  `gruppe_id` tinyint(4) NOT NULL,
  `einrichtung_id` int(11) NOT NULL DEFAULT 1,
  `erfasst_am` datetime DEFAULT current_timestamp(),
  `mitarbeit` int(1) DEFAULT NULL,
  `absprachen` int(1) DEFAULT NULL,
  `selbststaendigkeit` int(1) DEFAULT NULL,
  `konzentration` int(1) DEFAULT NULL,
  `fleiss` int(1) DEFAULT NULL,
  `lernfortschritt` int(1) DEFAULT NULL,
  `beherrscht_thema` int(1) DEFAULT NULL,
  `transferdenken` int(1) DEFAULT NULL,
  `basiswissen` int(1) DEFAULT NULL,
  `vorbereitet` int(1) DEFAULT NULL,
  `themenauswahl` int(1) DEFAULT NULL,
  `materialien` int(1) DEFAULT NULL,
  `methodenvielfalt` int(1) DEFAULT NULL,
  `individualisierung` int(1) DEFAULT NULL,
  `aufforderung` int(1) DEFAULT NULL,
  `zielgruppen` int(1) NOT NULL,
  `emotions` varchar(100) NOT NULL,
  `bemerkungen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mtr_rueckkopplung_teilnehmer`
--

INSERT INTO `mtr_rueckkopplung_teilnehmer` (`id`, `ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `gruppe_id`, `einrichtung_id`, `erfasst_am`, `mitarbeit`, `absprachen`, `selbststaendigkeit`, `konzentration`, `fleiss`, `lernfortschritt`, `beherrscht_thema`, `transferdenken`, `basiswissen`, `vorbereitet`, `themenauswahl`, `materialien`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `zielgruppen`, `emotions`, `bemerkungen`) VALUES
(1, 34, 21, 2, 1, '2025-09-08 17:10:00', 3, 1, 1, 2, 2, 2, 2, 1, 2, 3, 1, 1, 1, 1, 1, 1, '3,28,1', ''),
(2, 27, 12, 2, 1, '2025-09-08 17:10:00', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '1', ''),
(3, 36, 20, 2, 1, '2025-09-08 17:10:00', 2, 2, 2, 2, 2, 1, 2, 2, 2, 1, 1, 1, 1, 1, 1, 2, '6,23,2', ''),
(4, 14, 16, 2, 1, '2025-09-08 17:10:00', 2, 1, 4, 2, 1, 2, 2, 2, 3, 1, 1, 1, 1, 2, 2, 1, '23', ''),
(5, 19, 3, 3, 1, '2025-09-09 15:35:00', 1, 1, 2, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, 1, '6,25,24,2', ''),
(6, 3, 9, 4, 1, '2025-09-09 17:10:00', 1, 2, 1, 1, 5, 3, 3, 1, 3, 3, 2, 2, 1, 1, 1, 3, '22', ''),
(7, 14, 16, 4, 1, '2025-09-09 17:10:00', 2, 3, 5, 3, 2, 2, 2, 2, 2, 2, 1, 3, 1, 2, 1, 2, '22', ''),
(8, 5, 8, 4, 1, '2025-09-09 17:10:00', 3, 2, 3, 3, 3, 3, 4, 2, 3, 2, 2, 2, 3, 3, 3, 3, '', ''),
(9, 6, 5, 4, 1, '2025-09-09 17:10:00', 2, 2, 3, 2, 2, 3, 3, 2, 3, 1, 2, 2, 2, 0, 2, 2, '22,1,15,6,23,4,25,20', ''),
(10, 25, 11, 5, 1, '2025-09-10 15:35:00', 2, 1, 2, 2, 1, 2, 2, 2, 2, 1, 2, 2, 1, 2, 2, 2, '1,13,2', ''),
(11, 24, 6, 5, 1, '2025-09-10 15:35:00', 2, 3, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, '3,28,1', ''),
(12, 26, 7, 6, 1, '2025-09-10 17:10:00', 3, 1, 4, 4, 2, 2, 3, 3, 1, 1, 1, 1, 1, 2, 2, 2, '22,4,25', ''),
(13, 28, 14, 6, 1, '2025-09-10 17:10:00', 1, 2, 2, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, '15,25,20', ''),
(14, 40, 12, 6, 1, '2025-09-10 17:10:00', 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, 1, '', ''),
(15, 40, 12, 6, 1, '2025-09-10 17:10:00', 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, 1, '', ''),
(16, 30, 22, 6, 1, '2025-09-10 17:10:00', 2, 2, 2, 1, 2, 3, 4, 3, 3, 2, 1, 1, 1, 1, 1, 2, '5,4,7,25', ''),
(17, 29, 13, 6, 1, '2025-09-10 17:10:00', 2, 2, 2, 2, 1, 2, 2, 3, 2, 2, 2, 2, 2, 2, 2, 3, '', ''),
(18, 15, 9, 7, 1, '2025-09-11 15:35:00', 2, 1, 1, 2, 2, 1, 2, 2, 2, 3, 2, 2, 1, 2, 2, 2, '5,22,27,1,6,10,23,18,4,9,14,13,7,16,26', ''),
(19, 12, 15, 7, 1, '2025-09-11 15:35:00', 1, 2, 4, 5, 4, 2, 3, 2, 4, 2, 1, 2, 2, 1, 1, 2, '17,5,22,4,16,25,24,20', ''),
(20, 13, 10, 7, 1, '2025-09-11 15:35:00', 1, 1, 2, 2, 2, 1, 4, 3, 3, 1, 1, 2, 1, 1, 1, 1, '28,1,6,23,8,13,16,25,2', ''),
(21, 16, 18, 8, 1, '2025-09-11 17:10:00', 1, 3, 1, 2, 2, 1, 1, 1, 1, 1, 1, 3, 3, 2, 2, 2, '2', ''),
(22, 13, 10, 8, 1, '2025-09-11 17:10:00', 3, 3, 2, 3, 4, 2, 2, 2, 2, 3, 2, 2, 2, 1, 1, 2, '28', ''),
(23, 14, 16, 8, 1, '2025-09-11 17:10:00', 1, 1, 4, 2, 2, 2, 4, 2, 4, 1, 1, 1, 1, 1, 1, 1, '27,23,24', ''),
(24, 17, 17, 8, 1, '2025-09-11 17:10:00', 2, 1, 4, 2, 1, 1, 3, 3, 3, 1, 1, 1, 1, 1, 1, 1, '1,6,14,13', ''),
(32, 33, 2, 1, 1, '2025-09-15 15:35:00', 3, 3, 4, 4, 3, 2, 3, 3, 2, 2, 2, 2, 2, 2, 1, 2, '22,25,12,24,11,20', ''),
(33, 34, 21, 2, 1, '2025-09-15 17:10:00', 2, 1, 1, 1, 2, 2, 1, 2, 2, 4, 1, 1, 1, 1, 1, 1, '2', ''),
(34, 35, 16, 2, 1, '2025-09-15 17:10:00', 2, 1, 4, 3, 2, 2, 3, 2, 3, 1, 1, 1, 2, 2, 1, 2, '27,6,23', ''),
(35, 36, 20, 2, 1, '2025-09-15 17:10:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, '4,24', ''),
(36, 37, 23, 2, 1, '2025-09-15 17:10:00', 3, 2, 2, 2, 2, 2, 2, 3, 3, 2, 1, 2, 2, 2, 1, 1, '5,23,13,25,2', ''),
(37, 38, 10, 5, 1, '2025-09-17 15:35:00', 1, 1, 2, 3, 1, 1, 3, 2, 1, 1, 1, 1, 2, 1, 1, 1, '28,22,6,23,18,9,8,14,26,25,12,24,2,11,20', ''),
(38, 39, 2, 5, 1, '2025-09-17 15:35:00', 1, 2, 1, 1, 2, 1, 1, 1, 1, 2, 2, 1, 2, 2, 1, 2, '3,28,1,23,4,8,13', ''),
(39, 27, 12, 6, 1, '2025-09-17 17:10:00', 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 2, 1, 1, 1, 1, 1, '5,3,28,1,6', ''),
(40, 28, 14, 6, 1, '2025-09-17 17:10:00', 1, 2, 1, 2, 1, 1, 1, 2, 2, 1, 2, 1, 1, 2, 1, 1, '23,8,13,2', ''),
(41, 15, 9, 7, 1, '2025-09-18 15:35:00', 2, 1, 1, 1, 2, 2, 2, 2, 2, 1, 1, 1, 2, 1, 1, 1, '5,28,22,27,1,6,18,4,14,13', ''),
(42, 12, 15, 7, 1, '2025-09-18 15:35:00', 2, 2, 4, 4, 3, 1, 2, 3, 4, 2, 2, 2, 1, 1, 2, 2, '', ''),
(43, 38, 10, 7, 1, '2025-09-18 15:35:00', 1, 1, 1, 2, 1, 1, 3, 1, 1, 1, 2, 2, 1, 1, 1, 1, '22,27,1,6,10,23,18,8,14,13,26,25,24,2', ''),
(44, 17, 17, 8, 1, '2025-09-18 17:10:00', 2, 1, 4, 2, 2, 1, 3, 3, 3, 1, 1, 1, 1, 1, 1, 1, '1,6,23,13', ''),
(45, 18, 19, 8, 1, '2025-09-18 17:10:00', 3, 3, 2, 3, 3, 2, 3, 2, 3, 2, 2, 2, 2, 2, 2, 2, '5,28,2', ''),
(46, 42, 6, 9, 1, '2025-09-19 15:35:00', 3, 3, 2, 3, 3, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, '3,6,10', ''),
(47, 43, 20, 9, 1, '2025-09-19 15:35:00', 2, 2, 1, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, '4,2', ''),
(48, 44, 11, 9, 1, '2025-09-19 15:35:00', 2, 2, 3, 2, 2, 2, 2, 3, 3, 2, 2, 3, 2, 3, 2, 2, '1,23', ''),
(49, 45, 24, 9, 1, '2025-09-19 15:35:00', 3, 1, 2, 2, 2, 2, 3, 2, 3, 2, 1, 1, 2, 2, 3, 1, '5,28,6,10,23,14', ''),
(50, 46, 4, 9, 1, '2025-09-19 15:35:00', 3, 3, 4, 3, 3, 3, 4, 4, 4, 2, 2, 1, 2, 3, 4, 3, '17,5,15,6', ''),
(51, 47, 2, 1, 1, '2025-09-22 15:35:00', 2, 2, 2, 3, 2, 1, 2, 2, 3, 1, 1, 2, 2, 2, 1, 2, '3,28,1,23,4,8,13,2', ''),
(52, 48, 16, 2, 1, '2025-09-22 17:10:00', 2, 1, 1, 2, 2, 2, 3, 3, 3, 2, 1, 1, 1, 2, 2, 1, '23', ''),
(53, 49, 13, 2, 1, '2025-09-22 17:10:00', 1, 3, 1, 1, 2, 1, 2, 3, 2, 1, 1, 1, 1, 1, 1, 2, '5,1,7,2', ''),
(54, 50, 21, 2, 1, '2025-09-22 17:10:00', 2, 1, 1, 1, 1, 1, 1, 2, 2, 2, 1, 1, 1, 2, 1, 1, '23', ''),
(55, 51, 20, 2, 1, '2025-09-22 17:10:00', 2, 1, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 1, 1, 1, 1, '2', ''),
(56, 52, 23, 2, 1, '2025-09-22 17:10:00', 2, 2, 3, 2, 2, 2, 2, 3, 1, 2, 2, 3, 2, 1, 2, 2, '5,23,4,14,13,2', ''),
(57, 53, 16, 3, 1, '2025-09-23 15:35:00', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '1,23,4,9,14,13,7', ''),
(58, 54, 9, 4, 1, '2025-09-23 17:10:00', 3, 2, 3, 2, 3, 3, 3, 2, 1, 4, 2, 1, 2, 1, 1, 1, '5,3,28,22,1,6,23,4,8,14,13', ''),
(59, 55, 8, 4, 1, '2025-09-23 17:10:00', 4, 3, 3, 4, 4, 3, 3, 2, 2, 2, 1, 2, 2, 3, 3, 3, '22,1,18', ''),
(60, 56, 24, 4, 1, '2025-09-23 17:10:00', 2, 1, 2, 1, 2, 1, 2, 2, 1, 3, 1, 1, 1, 1, 1, 2, '28,1,10,4,14', ''),
(61, 0, 10, 5, 1, '2025-09-24 15:35:00', 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '28,22,27,1,6,10,23,18,4,8,14,13,26,25,12,24,2,20', ''),
(62, 58, 6, 5, 1, '2025-09-24 15:35:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, '3,28,1,4', ''),
(63, 59, 4, 5, 1, '2025-09-24 15:35:00', 2, 2, 3, 3, 2, 1, 4, 3, 4, 3, 2, 3, 3, 3, 2, 3, '5,3,28,1,6', ''),
(64, 60, 7, 6, 1, '2025-09-24 17:10:00', 1, 1, 2, 3, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, 3, 2, '22,4,25', ''),
(65, 61, 14, 6, 1, '2025-09-24 17:10:00', 1, 1, 2, 1, 1, 1, 2, 2, 2, 1, 1, 1, 1, 1, 1, 1, '6,23,4,14,2', ''),
(66, 62, 22, 6, 1, '2025-09-24 17:10:00', 2, 2, 4, 3, 3, 3, 4, 3, 3, 2, 1, 1, 2, 1, 1, 2, '5,28,1,23,4,9,8', ''),
(67, 63, 13, 6, 1, '2025-09-24 17:10:00', 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, '6,8,7,11', ''),
(68, 64, 9, 7, 1, '2025-09-25 15:35:00', 3, 1, 2, 5, 2, 0, 1, 2, 1, 3, 0, 0, 2, 2, 1, 2, '17,5,3,28,22,15,23,18,19,8,16,26,25,12,24,11,20', ''),
(69, 65, 15, 7, 1, '2025-09-25 15:35:00', 2, 1, 3, 2, 1, 2, 3, 3, 3, 2, 1, 2, 2, 1, 1, 1, '5,28,22,6,23', ''),
(70, 66, 10, 7, 1, '2025-09-25 15:35:00', 1, 1, 1, 3, 1, 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, '28,22,27,1,23,18,14,13,25,2,20', ''),
(71, 67, 18, 8, 1, '2025-09-25 17:10:00', 1, 2, 1, 2, 2, 1, 1, 2, 1, 1, 1, 2, 2, 3, 2, 1, '3', ''),
(72, 68, 16, 8, 1, '2025-09-25 17:10:00', 1, 1, 2, 1, 1, 1, 2, 1, 2, 1, 1, 1, 1, 1, 1, 1, '5,6,10,23,9,8,14,13,7,12,2', ''),
(73, 69, 19, 8, 1, '2025-09-25 17:10:00', 2, 2, 2, 2, 2, 1, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, '5,28,2', ''),
(74, 70, 17, 8, 1, '2025-09-25 17:10:00', 3, 1, 5, 3, 3, 3, 4, 4, 4, 1, 1, 1, 1, 1, 1, 3, '6,10,23,4,9,12', ''),
(75, 71, 4, 9, 1, '2025-09-26 15:35:00', 2, 3, 3, 2, 3, 2, 4, 2, 3, 4, 3, 3, 2, 3, 3, 1, '5', ''),
(76, 72, 20, 9, 1, '2025-09-26 15:35:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, 1, '23,24', ''),
(77, 23, 24, 9, 1, '2025-09-26 17:30:26', 1, 1, 1, 1, 2, 1, 2, 2, 2, 2, 1, 1, 2, 2, 1, 2, '5,27,1,2', ''),
(79, 75, 2, 1, 1, '2025-09-29 15:35:00', 3, 2, 3, 2, 1, 3, 3, 3, 2, 2, 2, 2, 1, 2, 1, 2, '9,24', ''),
(80, 76, 21, 2, 1, '2025-09-29 17:10:00', 1, 1, 1, 2, 2, 1, 2, 2, 2, 3, 1, 1, 1, 2, 1, 1, '1', ''),
(81, 77, 20, 2, 1, '2025-09-29 17:10:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, 1, '14,2', ''),
(82, 78, 16, 2, 1, '2025-09-29 17:10:00', 1, 1, 2, 1, 1, 1, 2, 1, 2, 1, 1, 1, 1, 1, 1, 1, '5,3,28,27,6,10,4,14,7', ''),
(83, 79, 23, 2, 1, '2025-09-29 17:10:00', 2, 2, 2, 2, 2, 3, 3, 2, 2, 2, 2, 2, 2, 2, 2, 2, '5,1,23,4', ''),
(84, 80, 3, 3, 1, '2025-09-30 15:35:00', 2, 2, 2, 2, 2, 2, 2, 2, 3, 1, 1, 2, 1, 1, 1, 1, '15,21,16,20', ''),
(85, 81, 7, 4, 1, '2025-09-30 17:10:00', 4, 2, 2, 5, 3, 3, 1, 2, 1, 2, 1, 2, 1, 2, 2, 2, '22,1,13', ''),
(86, 82, 9, 4, 1, '2025-09-30 17:10:00', 4, 2, 3, 6, 2, 2, 5, 1, 2, 3, 1, 5, 2, 2, 6, 2, '17,22,1,15,19,13,16,25,24,20', ''),
(87, 83, 8, 4, 1, '2025-09-30 17:10:00', 3, 2, 2, 3, 3, 3, 1, 2, 2, 2, 3, 1, 2, 3, 2, 3, '23', ''),
(88, 84, 24, 4, 1, '2025-09-30 17:10:00', 1, 2, 2, 2, 2, 1, 2, 3, 2, 2, 2, 3, 1, 1, 2, 2, '23,12', ''),
(89, 85, 5, 4, 1, '2025-09-30 17:10:00', 2, 2, 2, 0, 2, 1, 2, 2, 2, 1, 0, 0, 2, 2, 2, 2, '5,1,4,9,8,13,7', ''),
(90, 86, 6, 5, 1, '2025-10-01 15:35:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, '3,28,1', ''),
(91, 87, 11, 5, 1, '2025-10-01 15:35:00', 2, 3, 1, 2, 1, 1, 2, 2, 1, 2, 2, 1, 2, 2, 2, 2, '4,14,13,2', ''),
(92, 88, 2, 5, 1, '2025-10-01 15:35:00', 2, 1, 2, 2, 2, 1, 2, 1, 2, 1, 2, 1, 2, 2, 2, 2, '23,4,9,8,2', ''),
(93, 0, 10, 5, 1, '2025-10-01 16:53:43', 1, 1, 1, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '3,28,22,27,1,10,23,18,9,14,16,26,25,2', ''),
(94, 90, 12, 6, 1, '2025-10-01 17:10:00', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, '6', ''),
(95, 91, 14, 6, 1, '2025-10-01 17:10:00', 1, 1, 2, 1, 1, 2, 2, 2, 2, 1, 1, 1, 1, 1, 2, 1, '28,23,4', ''),
(96, 92, 22, 6, 1, '2025-10-01 17:10:00', 2, 2, 3, 2, 2, 2, 3, 3, 4, 2, 1, 1, 1, 1, 1, 1, '23,4,8,7,2', ''),
(97, 93, 9, 7, 1, '2025-10-02 15:35:00', 2, 2, 1, 2, 2, 2, 1, 2, 2, 2, 1, 2, 2, 1, 2, 1, '5,28,22,15,6,10,23,4,9,21,19,8,14,13,7,16,26,25,12,24', ''),
(98, 0, 10, 7, 1, '2025-10-02 16:53:16', 1, 1, 3, 3, 1, 1, 1, 1, 3, 2, 1, 1, 1, 1, 1, 1, '5,22,27,1,10,23,18,9,8,14,13,25,2,20', ''),
(99, 95, 18, 8, 1, '2025-10-02 17:10:00', 1, 2, 1, 2, 2, 2, 2, 1, 1, 2, 1, 2, 2, 2, 2, 2, '3', ''),
(100, 96, 16, 8, 1, '2025-10-02 17:10:00', 2, 1, 3, 2, 2, 2, 2, 2, 3, 1, 1, 1, 1, 1, 1, 1, '23,4,26', ''),
(101, 97, 19, 8, 1, '2025-10-02 17:10:00', 2, 2, 2, 3, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, '28,2', ''),
(102, 98, 17, 8, 1, '2025-10-02 17:10:00', 3, 1, 4, 3, 2, 3, 3, 3, 3, 1, 1, 2, 1, 1, 1, 2, '1,6,10,9,14', ''),
(103, 99, 22, 4, 1, '2025-10-07 17:10:00', 2, 2, 3, 1, 2, 2, 3, 3, 3, 2, 1, 1, 1, 1, 1, 1, '5,28,23,4,7,2', ''),
(104, 100, 8, 4, 1, '2025-10-07 17:10:00', 1, 1, 2, 1, 2, 2, 2, 2, 2, 1, 2, 1, 2, 2, 2, 2, '14', ''),
(105, 101, 13, 4, 1, '2025-10-07 17:10:00', 1, 2, 1, 1, 1, 1, 1, 1, 2, 1, 2, 2, 2, 2, 1, 1, '', ''),
(106, 102, 2, 1, 1, '2025-10-20 15:35:00', 2, 1, 2, 2, 2, 1, 1, 2, 1, 2, 2, 1, 2, 2, 2, 2, '22,9,8,14,2', ''),
(107, 0, 40, 1, 1, '2025-10-20 16:43:26', 2, 2, 1, 0, 2, 3, 3, 3, 2, 1, 0, 3, 2, 1, 2, 3, '5,23,11', ''),
(108, 104, 21, 2, 1, '2025-10-20 17:10:00', 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 2, 2, 1, 1, 1, 1, '2', ''),
(109, 105, 21, 2, 1, '2025-10-20 17:10:00', 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 2, 2, 1, 1, 1, 1, '2', ''),
(110, 106, 20, 2, 1, '2025-10-20 17:10:00', 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, '2', ''),
(111, 107, 23, 2, 1, '2025-10-20 17:10:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 1, 1, 2, 2, 2, 2, '1,23,4,8,13', ''),
(112, 108, 3, 3, 1, '2025-10-21 15:35:00', 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '5,28,1,4,8,14,7,12,2,11', ''),
(113, 109, 7, 4, 1, '2025-10-21 17:10:00', 1, 1, 3, 2, 2, 3, 3, 3, 2, 1, 1, 2, 3, 2, 2, 2, '23,4,25', ''),
(114, 110, 8, 4, 1, '2025-10-21 17:10:00', 2, 2, 3, 3, 3, 3, 2, 2, 2, 2, 2, 2, 2, 2, 2, 3, '4', ''),
(115, 111, 5, 4, 1, '2025-10-21 17:10:00', 1, 0, 3, 1, 1, 2, 2, 3, 3, 1, 2, 2, 2, 2, 1, 2, '5,1,6,10,23,4,8,13,7,24,2', ''),
(116, 112, 6, 5, 1, '2025-10-22 15:35:00', 3, 2, 3, 3, 3, 3, 3, 3, 3, 3, 2, 2, 2, 2, 2, 2, '16', ''),
(117, 113, 22, 6, 1, '2025-10-22 17:10:00', 2, 2, 1, 2, 2, 2, 3, 3, 2, 1, 1, 1, 1, 1, 2, 1, '5,1,23,4,8,13,7,2', ''),
(118, 114, 7, 6, 1, '2025-10-22 17:10:00', 1, 1, 2, 2, 2, 2, 3, 3, 2, 2, 1, 1, 2, 1, 3, 2, '4', ''),
(119, 115, 14, 6, 1, '2025-10-22 17:10:00', 3, 1, 3, 3, 3, 2, 1, 2, 2, 2, 2, 2, 2, 2, 1, 2, '22,23', ''),
(120, 0, 10, 0, 1, '2025-10-23 15:31:28', 1, 1, 2, 1, 1, 1, 2, 1, 2, 1, 1, 1, 1, 1, 1, 1, '28,22,27,1,6,10,23,18,4,14,13,16', ''),
(121, 116, 16, 7, 1, '2025-10-23 15:35:00', 1, 1, 2, 2, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 1, '28,22,27,1,15,6,10,23,18,4,14,13,16,26,25,2,11,20', ''),
(122, 117, 15, 7, 1, '2025-10-23 15:35:00', 2, 3, 2, 2, 3, 2, 4, 2, 2, 2, 3, 2, 1, 2, 4, 4, '', ''),
(123, 118, 18, 7, 1, '2025-10-23 15:35:00', 2, 2, 1, 3, 2, 2, 1, 2, 1, 2, 2, 2, 2, 1, 1, 1, '14', ''),
(124, 119, 18, 8, 1, '2025-10-23 17:10:00', 3, 2, 4, 3, 3, 3, 3, 2, 3, 2, 1, 2, 2, 1, 2, 2, '22', ''),
(125, 120, 16, 8, 1, '2025-10-23 17:10:00', 3, 1, 4, 3, 2, 3, 3, 2, 3, 1, 1, 1, 1, 2, 1, 1, '6', ''),
(126, 121, 17, 8, 1, '2025-10-23 17:10:00', 3, 1, 4, 3, 2, 1, 3, 3, 3, 1, 3, 1, 2, 1, 1, 1, '1,6,10,13', ''),
(127, 122, 20, 9, 1, '2025-10-24 15:35:00', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 1, 1, 1, 1, 1, 1, '4,13', ''),
(128, 123, 6, 9, 1, '2025-10-24 15:35:00', 2, 2, 3, 2, 2, 2, 2, 2, 3, 2, 2, 2, 2, 2, 2, 2, '3,10', ''),
(129, 124, 4, 9, 1, '2025-10-24 15:35:00', 2, 2, 3, 3, 4, 2, 3, 4, 2, 4, 3, 4, 2, 3, 4, 2, '5,28,6', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_sozial`
--

CREATE TABLE `mtr_sozial` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `mitarbeit` int(11) DEFAULT NULL,
  `absprachen` int(11) DEFAULT NULL,
  `selbststaendigkeit` int(11) DEFAULT NULL,
  `konzentration` int(11) DEFAULT NULL,
  `fleiss` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_soziale_beziehungen`
--

CREATE TABLE `mtr_soziale_beziehungen` (
  `id` int(11) NOT NULL,
  `from_tn` int(11) NOT NULL,
  `to_tn` int(11) NOT NULL,
  `wert` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `rooms`
--

INSERT INTO `rooms` (`room_id`, `name`) VALUES
(1, 'Rm. 3 SH Leipzig-Lausen');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `sessions`
--

INSERT INTO `sessions` (`session_id`, `session_date`, `start_time`, `end_time`, `subject_id`, `room_id`) VALUES
(1, '2025-09-01', '15:35:00', '17:05:00', 1, 1),
(2, '2025-09-01', '17:10:00', '18:40:00', 1, 1),
(3, '2025-09-02', '15:35:00', '17:05:00', 1, 1),
(4, '2025-09-02', '17:10:00', '18:40:00', 1, 1),
(5, '2025-09-03', '15:35:00', '17:05:00', 1, 1),
(6, '2025-09-03', '17:10:00', '18:40:00', 1, 1),
(7, '2025-09-04', '15:35:00', '17:05:00', 1, 1),
(8, '2025-09-04', '17:10:00', '18:40:00', 2, 1),
(9, '2025-09-05', '15:35:00', '17:05:00', 1, 1),
(1001, '2025-09-08', '15:35:00', '17:05:00', 1, 1),
(1002, '2025-09-08', '17:10:00', '18:40:00', 1, 1),
(1003, '2025-09-09', '15:35:00', '17:05:00', 1, 1),
(1004, '2025-09-09', '17:10:00', '18:40:00', 1, 1),
(1005, '2025-09-10', '15:35:00', '17:05:00', 1, 1),
(1006, '2025-09-10', '17:10:00', '18:40:00', 1, 1),
(1007, '2025-09-11', '15:35:00', '17:05:00', 1, 1),
(1008, '2025-09-11', '17:10:00', '18:40:00', 2, 1),
(1009, '2025-09-12', '15:35:00', '17:05:00', 1, 1),
(1010, '2025-09-15', '15:35:00', '17:05:00', 1, 1),
(1011, '2025-09-15', '17:10:00', '18:40:00', 1, 1),
(1012, '2025-09-16', '15:35:00', '17:05:00', 1, 1),
(1013, '2025-09-16', '17:10:00', '18:40:00', 1, 1),
(1014, '2025-09-17', '15:35:00', '17:05:00', 1, 1),
(1015, '2025-09-17', '17:10:00', '18:40:00', 1, 1),
(1016, '2025-09-18', '15:35:00', '17:05:00', 1, 1),
(1017, '2025-09-18', '17:10:00', '18:40:00', 2, 1),
(1018, '2025-09-19', '15:35:00', '17:05:00', 1, 1),
(1019, '2025-09-22', '15:35:00', '17:05:00', 1, 1),
(1020, '2025-09-22', '17:10:00', '18:40:00', 1, 1),
(1021, '2025-09-23', '15:35:00', '17:05:00', 1, 1),
(1022, '2025-09-23', '17:10:00', '18:40:00', 1, 1),
(1023, '2025-09-24', '15:35:00', '17:05:00', 1, 1),
(1024, '2025-09-24', '17:10:00', '18:40:00', 1, 1),
(1025, '2025-09-25', '15:35:00', '17:05:00', 1, 1),
(1026, '2025-09-25', '17:10:00', '18:40:00', 2, 1),
(1027, '2025-09-26', '15:35:00', '17:05:00', 1, 1),
(2001, '2025-09-29', '15:35:00', '17:05:00', 1, 1),
(2002, '2025-09-29', '17:10:00', '18:40:00', 1, 1),
(2003, '2025-09-30', '15:35:00', '17:05:00', 1, 1),
(2004, '2025-09-30', '17:10:00', '18:40:00', 1, 1),
(2005, '2025-10-01', '15:35:00', '17:05:00', 1, 1),
(2006, '2025-10-01', '17:10:00', '18:40:00', 1, 1),
(2007, '2025-10-02', '15:35:00', '17:05:00', 1, 1),
(2008, '2025-10-02', '17:10:00', '18:40:00', 2, 1),
(2009, '2025-10-06', '15:35:00', '17:05:00', 1, 1),
(2010, '2025-10-06', '17:10:00', '18:40:00', 1, 1),
(2011, '2025-10-07', '15:35:00', '17:05:00', 1, 1),
(2012, '2025-10-07', '17:10:00', '18:40:00', 1, 1),
(2013, '2025-10-08', '15:35:00', '17:05:00', 1, 1),
(2014, '2025-10-08', '17:10:00', '18:40:00', 1, 1),
(2015, '2025-10-09', '15:35:00', '17:05:00', 1, 1),
(2016, '2025-10-09', '17:10:00', '18:40:00', 2, 1),
(2017, '2025-10-10', '15:35:00', '17:05:00', 1, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session_students`
--

CREATE TABLE `session_students` (
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `session_students`
--

INSERT INTO `session_students` (`session_id`, `student_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 14),
(4, 15),
(4, 16),
(4, 17),
(4, 18),
(4, 19),
(5, 3),
(5, 16),
(5, 20),
(5, 21),
(6, 17),
(6, 22),
(6, 23),
(6, 24),
(6, 25),
(6, 26),
(7, 2),
(7, 19),
(7, 20),
(7, 27),
(7, 28),
(8, 6),
(8, 29),
(8, 30),
(8, 31),
(8, 32),
(9, 8),
(9, 10),
(9, 16),
(9, 21),
(1001, 2),
(1001, 3),
(1001, 4),
(1001, 33),
(1002, 6),
(1002, 7),
(1002, 8),
(1002, 9),
(1002, 24),
(1002, 34),
(1003, 11),
(1003, 12),
(1003, 13),
(1003, 14),
(1004, 15),
(1004, 17),
(1004, 18),
(1004, 19),
(1004, 35),
(1005, 3),
(1005, 16),
(1005, 20),
(1005, 21),
(1006, 17),
(1006, 22),
(1006, 23),
(1006, 24),
(1006, 25),
(1006, 26),
(1007, 19),
(1007, 20),
(1007, 27),
(1008, 6),
(1008, 29),
(1008, 30),
(1008, 31),
(1008, 32),
(1009, 8),
(1009, 16),
(1009, 21),
(1010, 3),
(1011, 6),
(1011, 7),
(1011, 8),
(1011, 9),
(1012, 5),
(1012, 11),
(1012, 12),
(1012, 13),
(1012, 14),
(1013, 15),
(1013, 17),
(1013, 18),
(1013, 19),
(1014, 3),
(1014, 16),
(1014, 20),
(1014, 21),
(1015, 17),
(1015, 22),
(1015, 23),
(1015, 24),
(1015, 25),
(1015, 26),
(1016, 19),
(1016, 20),
(1016, 27),
(1017, 6),
(1017, 29),
(1017, 30),
(1017, 31),
(1017, 32),
(1018, 8),
(1018, 10),
(1018, 16),
(1018, 21),
(1018, 36),
(1019, 2),
(1019, 3),
(1019, 33),
(1020, 6),
(1020, 7),
(1020, 8),
(1020, 9),
(2001, 3),
(2002, 6),
(2002, 7),
(2002, 8),
(2002, 9),
(2003, 5),
(2003, 11),
(2003, 12),
(2003, 13),
(2003, 14),
(2004, 15),
(2004, 17),
(2004, 18),
(2004, 19),
(2004, 36),
(2005, 3),
(2005, 10),
(2005, 16),
(2005, 20),
(2005, 21),
(2006, 17),
(2006, 22),
(2006, 24),
(2006, 26),
(2007, 19),
(2007, 20),
(2007, 27),
(2007, 37),
(2008, 6),
(2008, 29),
(2008, 30),
(2008, 31),
(2009, 3),
(2009, 7),
(2009, 8),
(2009, 9),
(2009, 34),
(2010, 6),
(2010, 7),
(2010, 8),
(2010, 9),
(2010, 34),
(2011, 5),
(2011, 11),
(2011, 12),
(2011, 13),
(2011, 14),
(2011, 38),
(2011, 39),
(2012, 15),
(2012, 17),
(2012, 18),
(2012, 19),
(2012, 22),
(2012, 24),
(2012, 25),
(2012, 40),
(2012, 41),
(2013, 3),
(2013, 10),
(2013, 16),
(2013, 20),
(2013, 21),
(2014, 17),
(2014, 22),
(2014, 24),
(2014, 25),
(2014, 26),
(2014, 41),
(2015, 19),
(2015, 20),
(2015, 27),
(2016, 6),
(2016, 29),
(2016, 30),
(2016, 31),
(2017, 8),
(2017, 10),
(2017, 16),
(2017, 21);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `std_lehrkraft`
--

CREATE TABLE `std_lehrkraft` (
  `std_lehrkraft` int(11) NOT NULL,
  `Vorname` varchar(50) NOT NULL,
  `Nachname` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `std_lehrkraft`
--

INSERT INTO `std_lehrkraft` (`std_lehrkraft`, `Vorname`, `Nachname`) VALUES
(1, 'Olaf', 'Thiele');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `std_lernthema`
--

CREATE TABLE `std_lernthema` (
  `id` int(11) NOT NULL,
  `quelle_id` int(11) NOT NULL,
  `fach_id` int(11) NOT NULL DEFAULT 1,
  `klassenstufe` int(11) DEFAULT NULL,
  `schulform` varchar(20) DEFAULT NULL,
  `lernthema` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `std_lernthema`
--

INSERT INTO `std_lernthema` (`id`, `quelle_id`, `fach_id`, `klassenstufe`, `schulform`, `lernthema`) VALUES
(1, 1, 1, 9, '', 'Kreis & Zylinder'),
(2, 1, 1, 9, '', 'Parabeln / Quadratfunktionen'),
(3, 1, 1, 9, '', 'Quadratische Gleichungen'),
(4, 1, 1, 9, '', 'Quadratwurzeln'),
(5, 1, 1, 9, '', 'Satz des Pythagoras'),
(6, 1, 1, 9, '', 'Wurzelfunktionen'),
(7, 1, 1, 9, '', 'Zinsen & Zinseszinsen'),
(8, 1, 1, 9, '', 'Ähnlichkeit & Strahlensatz'),
(9, 1, 1, 10, '', 'Daten'),
(10, 1, 1, 10, '', 'Exponentialfunktionen'),
(11, 1, 1, 10, '', 'Kombinatorik'),
(12, 1, 1, 10, '', 'Körper'),
(13, 1, 1, 10, '', 'Potenzen & Wurzeln'),
(14, 1, 1, 10, '', 'Trigonometrie im Dreieck'),
(15, 1, 1, 10, '', 'Trigonometrische Funktionen'),
(16, 2, 1, 9, 'RS', 'Zahlen und Rechnen'),
(17, 2, 1, 9, 'OS', 'Zahlen und Rechnen'),
(18, 2, 1, 9, 'GYM', 'Zahlen und Rechnen'),
(19, 2, 1, 9, 'RS', 'Geometrie'),
(20, 2, 1, 9, 'OS', 'Geometrie'),
(21, 2, 1, 9, 'GYM', 'Geometrie'),
(22, 2, 1, 9, 'RS', 'Größen und Messen'),
(23, 2, 1, 9, 'OS', 'Größen und Messen'),
(24, 2, 1, 9, 'GYM', 'Größen und Messen'),
(25, 2, 1, 9, 'OS', 'Funktionale Zusammenhänge'),
(26, 2, 1, 9, 'GYM', 'Funktionale Zusammenhänge'),
(27, 2, 1, 9, 'GYM', 'Algebra'),
(28, 2, 1, 9, 'GYM', 'Wahrscheinlichkeit'),
(29, 2, 1, 10, 'RS', 'Quadratische Funktionen und Gleichungen'),
(30, 2, 1, 10, 'OS', 'Quadratische Funktionen und Gleichungen'),
(31, 2, 1, 10, 'GYM', 'Quadratische Funktionen und Gleichungen'),
(32, 2, 1, 10, 'RS', 'Körperberechnung'),
(33, 2, 1, 10, 'OS', 'Körperberechnung'),
(34, 2, 1, 10, 'GYM', 'Körperberechnung'),
(35, 2, 1, 10, 'OS', 'Wahrscheinlichkeitsrechnung'),
(36, 2, 1, 10, 'GYM', 'Trigonometrie'),
(37, 2, 1, 10, 'GYM', 'Analytische Geometrie'),
(38, 2, 1, 4, 'RS', 'Zahlenraum bis 10000'),
(39, 2, 1, 4, 'OS', 'Zahlenraum bis 10000'),
(40, 2, 1, 4, 'GYM', 'Zahlenraum bis 10000'),
(41, 2, 1, 4, 'RS', 'Geometrische Formen'),
(42, 2, 1, 4, 'OS', 'Geometrische Formen'),
(43, 2, 1, 4, 'GYM', 'Geometrische Formen'),
(44, 2, 1, 4, 'RS', 'Größen und Sachrechnen'),
(45, 2, 1, 4, 'OS', 'Größen und Sachrechnen'),
(46, 2, 1, 4, 'GYM', 'Größen und Sachrechnen'),
(47, 0, 1, 0, 'frei', 'Vorbereitung BLF'),
(48, 1, 2, 7, 'GYM', 'Mechanik'),
(49, 1, 2, 8, 'GYM', 'Elektrizitätslehre'),
(50, 1, 2, 9, 'GYM', 'Wellen und Optik'),
(51, 1, 2, 10, 'GYM', 'Wärmelehre & Atomphysik'),
(52, 1, 2, 11, 'GYM', 'Vertiefte Mechanik & Thermodynamik'),
(53, 1, 2, 12, 'GYM', 'Elektrodynamik & Moderne Physik'),
(54, 1, 2, 7, 'RS', 'Mechanik'),
(55, 1, 2, 8, 'RS', 'Elektrizitätslehre'),
(56, 1, 2, 9, 'RS', 'Optik & Akustik'),
(57, 1, 2, 10, 'RS', 'Wärmelehre & Atomphysik'),
(58, 1, 2, 7, 'OS', 'Mechanik im Alltag'),
(59, 1, 2, 8, 'OS', 'Strom und Magnetismus'),
(60, 1, 2, 9, 'OS', 'Licht und Schall'),
(61, 1, 2, 10, 'OS', 'Wärme und Atomvorstellungen');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `std_teilnehmer`
--

CREATE TABLE `std_teilnehmer` (
  `id` int(11) NOT NULL,
  `einrichtung_id` int(11) NOT NULL DEFAULT 1,
  `teilnehmer_typ` int(1) NOT NULL DEFAULT 1 COMMENT '0 - Lehrkraft / 1 - Teilnehmer',
  `show_tn` int(1) NOT NULL DEFAULT 1,
  `Vorname` varchar(255) DEFAULT NULL,
  `Nachname` varchar(255) DEFAULT NULL,
  `geschlecht` char(1) NOT NULL,
  `geburtstag` date NOT NULL,
  `Nachstunde` tinyint(1) DEFAULT NULL,
  `Klassenstufe` int(11) DEFAULT NULL,
  `KlassentypID` int(11) DEFAULT NULL,
  `Bis` date DEFAULT NULL,
  `GruppenID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `std_teilnehmer`
--

INSERT INTO `std_teilnehmer` (`id`, `einrichtung_id`, `teilnehmer_typ`, `show_tn`, `Vorname`, `Nachname`, `geschlecht`, `geburtstag`, `Nachstunde`, `Klassenstufe`, `KlassentypID`, `Bis`, `GruppenID`) VALUES
(1, 1, 0, 1, 'Olaf', 'Thiele', '1', '0000-00-00', NULL, 23, 4, NULL, NULL),
(2, 1, 1, 1, 'Lukas', 'Hilpert', '1', '0000-00-00', NULL, 9, 3, NULL, NULL),
(3, 1, 1, 1, 'Sarah', 'Kahle', '2', '0000-00-00', NULL, 6, 2, NULL, NULL),
(4, 1, 1, 1, 'Simos', 'Giannakidis', '1', '0000-00-00', NULL, 10, 2, NULL, NULL),
(5, 1, 1, 1, 'Paula', 'Juraschek', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(6, 1, 1, 1, 'Karl', 'König', '1', '0000-00-00', NULL, 7, 2, NULL, NULL),
(7, 1, 1, 1, 'Carlotta', 'Körber', '2', '0000-00-00', NULL, 9, 2, NULL, NULL),
(8, 1, 1, 1, 'Selina', 'Möwes', '2', '0000-00-00', NULL, 9, 3, NULL, NULL),
(9, 1, 1, 1, 'Lia', 'Schubert', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(10, 1, 1, 1, 'Noah', 'Freiberg', '1', '0000-00-00', NULL, 12, 2, NULL, NULL),
(11, 1, 1, 1, 'Zoey', 'Schönherr', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(12, 1, 1, 1, 'Felix', 'Schölzel', '1', '0000-00-00', NULL, 8, 2, NULL, NULL),
(13, 1, 1, 1, 'Juni', 'Schölzel', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(14, 1, 1, 1, 'Jalia', 'Wagner', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(15, 1, 1, 1, 'Ida', 'Johnson', '2', '0000-00-00', NULL, 9, 2, NULL, NULL),
(16, 1, 1, 1, 'Anna-Sophie', 'Canitz', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(17, 1, 1, 1, 'Maja', 'Deutlich', '2', '0000-00-00', NULL, 7, 3, NULL, NULL),
(18, 1, 1, 1, 'Louis', 'Grobe', '1', '0000-00-00', NULL, 10, 2, NULL, NULL),
(19, 1, 1, 1, 'Pia', 'Ponader', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(20, 1, 1, 1, 'Luise', 'Schaff', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(21, 1, 1, 1, 'Gustav', 'Fleischer', '1', '0000-00-00', NULL, 11, 2, NULL, NULL),
(22, 1, 1, 1, 'Maruschka', 'Gottschlich', '2', '0000-00-00', NULL, 11, 2, NULL, NULL),
(23, 1, 1, 1, 'Lotte', 'Wicher', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(24, 1, 1, 1, 'Elias', 'Schaller', '1', '0000-00-00', NULL, 11, 2, NULL, NULL),
(25, 1, 1, 0, 'Lena', 'Schmidt', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(26, 1, 1, 0, 'Tim', 'Müller', '1', '0000-00-00', NULL, 9, 2, NULL, NULL),
(27, 1, 1, 0, 'Sophie', 'Weber', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(28, 1, 1, 0, 'Jonas', 'Schneider', '1', '0000-00-00', NULL, 11, 2, NULL, NULL),
(29, 1, 1, 0, 'Emma', 'Fischer', '2', '0000-00-00', NULL, 9, 2, NULL, NULL),
(30, 1, 1, 0, 'Paul', 'Meyer', '1', '0000-00-00', NULL, 7, 2, NULL, NULL),
(31, 1, 1, 0, 'Lea', 'Wagner', '2', '0000-00-00', NULL, 7, 3, NULL, NULL),
(32, 1, 1, 0, 'Ben', 'Becker', '1', '0000-00-00', NULL, 9, 2, NULL, NULL),
(33, 1, 1, 0, 'Marie', 'Hofmann', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(34, 1, 1, 0, 'Luca', 'Koch', '1', '0000-00-00', NULL, 10, 2, NULL, NULL),
(35, 1, 1, 0, 'Laura', 'Schulz', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(36, 1, 1, 0, 'Finn', 'Richter', '1', '0000-00-00', NULL, 6, 2, NULL, NULL),
(37, 1, 1, 0, 'Mia', 'Klein', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(38, 1, 1, 0, 'Tom', 'Wolf', '1', '0000-00-00', NULL, 7, 2, NULL, NULL),
(39, 1, 1, 0, 'Anna', 'Bauer', '2', '0000-00-00', NULL, 6, 2, NULL, NULL),
(40, 1, 1, 1, 'Dmytro', 'Filenkov', '1', '0000-00-00', NULL, 6, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `school_form` varchar(10) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `valid_until` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `school_form`, `grade`, `valid_until`) VALUES
(1, 'Romy', 'Domann', 'GR', 4, '2025-09-01'),
(2, 'Ari', 'Fischer', 'GR', 5, '2025-09-01'),
(3, 'Lukas', 'Hillpert', 'RS', 9, NULL),
(4, 'Anna', 'Lademann', 'GR', 5, '2025-09-01'),
(5, 'Sistine', 'Lauschke', 'GR', 4, '2025-09-01'),
(6, 'Anna-Sophie', 'Canitz', 'GYM', 10, NULL),
(7, 'Gustav', 'Fleischer', 'GYM', 11, NULL),
(8, 'Luise', 'Schaff', 'GYM', 10, NULL),
(9, 'Lotte Marie', 'Wicher', 'GYM', 10, NULL),
(10, 'Simos', 'Giannakidis', 'GYM', 10, '2025-09-02'),
(11, 'Sarah', 'Kahle', 'GYM', 6, NULL),
(12, 'Kiara', 'Kuznik', 'GR', 4, NULL),
(13, 'Mira', 'Moritz', 'GR', 5, '2025-11-30'),
(14, 'Laila', 'Stockmann', 'GR', 4, NULL),
(15, 'Paula', 'Juraschek', 'GYM', 8, NULL),
(16, 'Karl', 'König', 'GYM', 7, '2025-09-02'),
(17, 'Carlotta Erika', 'Körber', 'GYM', 9, '2025-10-31'),
(18, 'Selina', 'Möwes', 'RS', 9, NULL),
(19, 'Lia', 'Schubert', 'GYM', 8, NULL),
(20, 'Noah', 'Freiberg', 'GYM', 12, NULL),
(21, 'Zoey', 'Schönherr', 'GYM', 7, NULL),
(22, 'Maruschka', 'Gottschlich', 'GYM', 11, NULL),
(23, 'Helena', 'Mußdorf', 'GYM', 11, '2025-09-30'),
(24, 'Felix', 'Schölzel', 'GYM', 8, NULL),
(25, 'Juni Florentine', 'Schölzel', 'GYM', 7, NULL),
(26, 'Jalia', 'Wagner', 'GYM', 10, NULL),
(27, 'Ida', 'Johnsen', 'GYM', 9, NULL),
(28, 'Svea', 'Ziegler', 'GR', 3, '2025-09-04'),
(29, 'Maja', 'Deutlich', 'RS', 7, NULL),
(30, 'Louis', 'Grobe', 'GYM', 10, NULL),
(31, 'Pia', 'Ponader', 'GYM', 7, NULL),
(32, 'Felix', 'Scheithauer', 'GYM', 8, '2025-09-30'),
(33, 'Till', 'Herrlitz', 'GR', 5, '2025-09-08'),
(34, 'Leopold', 'Fleischer', 'GYM', 10, '2025-09-08'),
(35, 'Pauline', 'Römer', 'GYM', 9, '2025-09-09'),
(36, 'Elias', 'Schaller', 'GYM', 11, '2025-09-26'),
(37, 'Hannah V', 'Berghof', 'GR', 3, NULL),
(38, 'Marley', 'Findeisen', 'GR', 3, '2025-10-07'),
(39, 'Mathilda', 'Kind', 'GR', 4, '2025-10-07'),
(40, 'Christina', 'Avramidou', 'GYM', 12, '2025-12-31'),
(41, 'Lenny', 'Hilger', 'GYM', 10, '2025-10-07');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `subjects`
--

INSERT INTO `subjects` (`subject_id`, `code`, `name`) VALUES
(1, 'MAT', 'Mathematik'),
(2, 'PHY', 'Physik');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tmp_teilnehmer`
--

CREATE TABLE `tmp_teilnehmer` (
  `id` int(11) NOT NULL,
  `vorname` varchar(255) DEFAULT NULL,
  `nachname` varchar(255) DEFAULT NULL,
  `klassenstufe` int(11) DEFAULT NULL,
  `schultyp` varchar(50) DEFAULT NULL,
  `status` text DEFAULT NULL,
  `besondere_hinweise` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tmp_unterrichtseinheiten`
--

CREATE TABLE `tmp_unterrichtseinheiten` (
  `id` int(11) NOT NULL,
  `tn_id` int(11) NOT NULL,
  `datum` date DEFAULT NULL,
  `zeit` time DEFAULT NULL,
  `wochentag` int(11) NOT NULL,
  `dauer_min` int(11) DEFAULT NULL,
  `gruppen_id` int(11) DEFAULT NULL,
  `fach` varchar(255) DEFAULT NULL,
  `lehrkraft` varchar(255) DEFAULT NULL,
  `thema` varchar(255) DEFAULT NULL,
  `inhalt` varchar(512) NOT NULL,
  `absprachen` int(11) DEFAULT NULL,
  `mitarbeit` int(11) DEFAULT NULL,
  `fleissig` int(11) DEFAULT NULL,
  `selbststaendig` int(11) DEFAULT NULL,
  `vorbereitet` int(11) DEFAULT NULL,
  `konzentriert` int(11) DEFAULT NULL,
  `lernfortschritt` int(11) DEFAULT NULL,
  `beherrscht_thema` int(11) DEFAULT NULL,
  `transferdenken` int(11) DEFAULT NULL,
  `basiswissen` int(11) DEFAULT NULL,
  `freitext` text DEFAULT NULL,
  `desinteressiert_gleichgueltig` int(1) DEFAULT NULL,
  `unkonzentriert` int(1) DEFAULT NULL,
  `unverstaendnis` int(1) DEFAULT NULL,
  `benoetigt_aufforderung` int(1) DEFAULT NULL,
  `stoerend_blockierend_resignierend` int(1) DEFAULT NULL,
  `absprachen_nicht_einhaltend` int(1) DEFAULT NULL,
  `materialien_fehlen` int(1) DEFAULT NULL,
  `unpuenktlich` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_gruppen`
--

CREATE TABLE `ue_gruppen` (
  `id` int(11) NOT NULL,
  `day_number` tinyint(1) NOT NULL,
  `tag` varchar(3) NOT NULL,
  `uhrzeit_start` time NOT NULL,
  `uhrzeit_ende` time NOT NULL,
  `fach` varchar(10) NOT NULL,
  `raum` varchar(10) DEFAULT NULL,
  `standort` varchar(30) DEFAULT NULL,
  `kommentar` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `ue_gruppen`
--

INSERT INTO `ue_gruppen` (`id`, `day_number`, `tag`, `uhrzeit_start`, `uhrzeit_ende`, `fach`, `raum`, `standort`, `kommentar`) VALUES
(1, 2, 'Mo.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 25.08.25'),
(2, 2, 'Mo.', '17:10:00', '18:40:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 31.08.25'),
(3, 3, 'Di.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 30.09.25'),
(4, 3, 'Di.', '17:10:00', '18:40:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 31.10.25'),
(5, 4, 'Mi.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 30.09.25'),
(6, 4, 'Mi.', '17:10:00', '18:40:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 30.09.25'),
(7, 5, 'Do.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 31.08.25'),
(8, 5, 'Do.', '17:10:00', '18:40:00', 'PHY', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 30.09.25'),
(9, 6, 'Fr.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 29.08.25'),
(10, 2, '', '07:15:00', '13:30:00', '', 'dummy', NULL, NULL),
(11, 4, 'Mit', '14:15:00', '15:00:00', 'MAT', NULL, '2', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_unterrichtseinheit`
--

CREATE TABLE `ue_unterrichtseinheit` (
  `id` int(11) NOT NULL,
  `gruppe_id` int(11) DEFAULT NULL,
  `einrichtung_id` int(11) NOT NULL DEFAULT 1,
  `datum` date DEFAULT NULL,
  `zeit` time DEFAULT NULL,
  `dauer` int(2) NOT NULL DEFAULT 90,
  `beschreibung` text DEFAULT NULL COMMENT 'Beschreibung'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `ue_unterrichtseinheit`
--

INSERT INTO `ue_unterrichtseinheit` (`id`, `gruppe_id`, `einrichtung_id`, `datum`, `zeit`, `dauer`, `beschreibung`) VALUES
(1, 1, 1, NULL, NULL, 90, 'test'),
(2, 3, 1, '2025-09-09', '15:35:00', 90, 'Gruppenveranstaltung'),
(3, 4, 1, '2025-09-09', '17:10:00', 90, 'Gruppenveranstaltung'),
(4, 5, 1, '2025-09-10', '15:35:00', 90, 'Gruppenveranstaltung'),
(5, 6, 1, '2025-09-10', '17:10:00', 90, 'Gruppenveranstaltung'),
(6, 7, 1, '2025-09-11', '15:35:00', 90, 'Gruppenveranstaltung'),
(7, 8, 1, '2025-09-11', '17:10:00', 90, 'Gruppenveranstaltung'),
(10, 1, 1, '2025-09-15', '15:35:00', 90, 'Gruppenveranstaltung'),
(11, 2, 1, '2025-09-15', '17:10:00', 90, 'Gruppenveranstaltung'),
(12, 5, 1, '2025-09-17', '15:35:00', 90, 'Gruppenveranstaltung'),
(13, 6, 1, '2025-09-17', '17:10:00', 90, 'Gruppenveranstaltung'),
(14, 7, 1, '2025-09-18', '15:35:00', 90, 'Gruppenveranstaltung'),
(15, 8, 1, '2025-09-18', '17:10:00', 90, 'Gruppenveranstaltung'),
(16, 9, 1, '2025-09-19', '15:35:00', 90, 'Gruppenveranstaltung'),
(17, 1, 1, '2025-09-22', '15:35:00', 90, 'Gruppenveranstaltung'),
(18, 2, 1, '2025-09-22', '17:10:00', 90, 'Gruppenveranstaltung'),
(19, 3, 1, '2025-09-23', '15:35:00', 90, 'Gruppenveranstaltung'),
(20, 4, 1, '2025-09-23', '17:10:00', 90, 'Gruppenveranstaltung'),
(21, 5, 1, '2025-09-24', '15:35:00', 90, 'Gruppenveranstaltung'),
(22, 6, 1, '2025-09-24', '17:10:00', 90, 'Gruppenveranstaltung'),
(23, 7, 1, '2025-09-25', '15:35:00', 90, 'Gruppenveranstaltung'),
(24, 8, 1, '2025-09-25', '17:10:00', 90, 'Gruppenveranstaltung'),
(25, 9, 1, '2025-09-26', '15:35:00', 90, 'Gruppenveranstaltung'),
(26, 1, 1, '2025-09-29', '15:35:00', 90, 'Gruppenveranstaltung'),
(27, 2, 1, '2025-09-29', '17:10:00', 90, 'Gruppenveranstaltung'),
(28, 3, 1, '2025-09-30', '15:35:00', 90, 'Gruppenveranstaltung'),
(29, 4, 1, '2025-09-30', '17:10:00', 90, 'Gruppenveranstaltung'),
(30, 5, 1, '2025-10-01', '15:35:00', 90, 'Gruppenveranstaltung'),
(31, 6, 1, '2025-10-01', '17:10:00', 90, 'Gruppenveranstaltung'),
(32, 7, 1, '2025-10-02', '15:35:00', 90, 'Gruppenveranstaltung'),
(33, 8, 1, '2025-10-02', '17:10:00', 90, 'Gruppenveranstaltung'),
(34, 4, 1, '2025-10-07', '17:10:00', 90, 'Gruppenveranstaltung'),
(35, 1, 1, '2025-10-20', '15:35:00', 90, 'Gruppenveranstaltung'),
(36, 2, 1, '2025-10-20', '17:10:00', 90, 'Gruppenveranstaltung'),
(37, 3, 1, '2025-10-21', '15:35:00', 90, 'Gruppenveranstaltung'),
(38, 4, 1, '2025-10-21', '17:10:00', 90, 'Gruppenveranstaltung'),
(39, 5, 1, '2025-10-22', '15:35:00', 90, 'Gruppenveranstaltung'),
(40, 6, 1, '2025-10-22', '17:10:00', 90, 'Gruppenveranstaltung'),
(41, 7, 1, '2025-10-23', '15:35:00', 90, 'Gruppenveranstaltung'),
(42, 8, 1, '2025-10-23', '17:10:00', 90, 'Gruppenveranstaltung'),
(43, 9, 1, '2025-10-24', '15:35:00', 90, 'Gruppenveranstaltung');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_unterrichtseinheit_zw_thema`
--

CREATE TABLE `ue_unterrichtseinheit_zw_thema` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_id` int(11) NOT NULL,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  `lehrkraft_id` int(11) NOT NULL DEFAULT 1,
  `schulform_id` int(11) NOT NULL,
  `fach_id` int(11) NOT NULL DEFAULT 1,
  `zieltyp_id` int(11) NOT NULL,
  `lernmethode_id` int(11) NOT NULL,
  `std_lernthema_id` varchar(100) NOT NULL,
  `thema` varchar(255) NOT NULL,
  `dauer` int(11) NOT NULL,
  `teilnehmer_id` varchar(100) NOT NULL,
  `beschreibung` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `ue_unterrichtseinheit_zw_thema`
--

INSERT INTO `ue_unterrichtseinheit_zw_thema` (`id`, `ue_unterrichtseinheit_id`, `datum`, `lehrkraft_id`, `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `dauer`, `teilnehmer_id`, `beschreibung`) VALUES
(1, 1, '2025-09-19 18:56:24', 1, 2, 1, 3, 24, 'Vorbereitung BLF', 'Teil A', 15, '20', ''),
(2, 1, '2025-09-09 15:35:00', 1, 2, 1, 3, 24, 'Geometrie', 'Flächenberechnung', 30, '', ''),
(4, 3, '2025-09-09 17:10:00', 1, 0, 1, 1, 24, '', '', 15, '7,9,5,8', 'Gruppe 4'),
(5, 4, '2025-09-10 15:35:00', 1, 0, 1, 1, 24, '', '', 15, '6,11', 'Gruppe 5'),
(6, 5, '2025-09-10 17:10:00', 1, 0, 1, 1, 24, '', '', 15, '7,12,14,13,22', 'Gruppe 6'),
(7, 6, '2025-09-11 15:35:00', 1, 0, 1, 1, 24, '', '', 0, '10', 'Gruppe 7'),
(8, 7, '2025-09-11 17:10:00', 1, 0, 1, 3, 24, '', '', 90, '16,9,18,17,19', 'Gruppe 8'),
(9, 2, '2025-09-09 15:35:00', 1, 2, 1, 14, 24, 'Zahlen und Rechnen', 'Dezimalzahlen', 90, '3', ''),
(12, 10, '2025-09-15 15:35:00', 1, 0, 1, 1, 24, '', '', 90, '2', 'Gruppe 1'),
(13, 11, '2025-09-15 17:10:00', 1, 0, 1, 1, 24, '', '', 90, '23', 'Gruppe 2'),
(14, 12, '2025-09-17 15:35:00', 1, 0, 1, 1, 24, '', '', 90, '2', 'Gruppe 5'),
(15, 13, '2025-09-19 18:56:24', 1, 0, 1, 1, 24, '', '', 90, '14', 'Gruppe 6'),
(16, 14, '2025-09-19 17:10:00', 1, 0, 1, 1, 24, '', '', 90, '10', 'Gruppe 7'),
(17, 15, '2025-09-19 17:10:24', 1, 0, 1, 1, 24, '', '', 90, '17', 'Gruppe 8'),
(18, 16, '2025-09-19 15:35:00', 1, 0, 1, 1, 24, '', '', 90, '4', 'Gruppe 9'),
(19, 17, '2025-09-22 16:58:05', 1, 0, 1, 1, 24, '', '', 90, '2', 'Gruppe 1'),
(20, 18, '2025-09-22 18:25:20', 1, 0, 1, 1, 24, '', '', 90, '23', 'Gruppe 2'),
(21, 19, '2025-09-23 16:59:02', 1, 0, 1, 1, 24, '', '', 90, '16', 'Gruppe 3'),
(22, 20, '2025-09-23 18:32:13', 1, 0, 1, 1, 24, '', '', 90, '24', 'Gruppe 4'),
(23, 21, '2025-09-24 16:51:48', 1, 0, 1, 1, 24, '', '', 90, '4', 'Gruppe 5'),
(24, 22, '2025-09-24 18:30:32', 1, 0, 1, 1, 24, '', '', 90, '13', 'Gruppe 6'),
(25, 23, '2025-09-25 16:49:06', 1, 0, 1, 1, 24, '', '', 90, '10', 'Gruppe 7'),
(26, 24, '2025-09-25 18:21:42', 1, 0, 1, 1, 24, '', '', 90, '17', 'Gruppe 8'),
(27, 25, '2025-09-26 17:28:12', 1, 0, 1, 1, 24, '', '', 90, '24', 'Gruppe 9'),
(28, 26, '2025-09-29 17:02:45', 1, 0, 1, 1, 24, '', '', 90, '2', 'Gruppe 1'),
(29, 27, '2025-09-29 18:27:15', 1, 0, 1, 1, 24, '', '', 90, '23', 'Gruppe 2'),
(30, 28, '2025-09-30 16:52:03', 1, 0, 1, 1, 24, '', '', 90, '3', 'Gruppe 3'),
(31, 29, '2025-09-30 18:23:22', 1, 0, 1, 1, 24, '', '', 90, '5', 'Gruppe 4'),
(32, 30, '2025-10-01 16:49:23', 1, 0, 1, 1, 24, '', '', 90, '10', 'Gruppe 5'),
(33, 31, '2025-10-01 18:30:05', 1, 0, 1, 1, 24, '', '', 90, '22', 'Gruppe 6'),
(34, 32, '2025-10-02 16:52:28', 1, 0, 1, 1, 24, '', '', 90, '10', 'Gruppe 7'),
(35, 33, '2025-10-02 18:23:18', 1, 0, 1, 1, 24, '', '', 90, '17', 'Gruppe 8'),
(36, 34, '2025-10-07 18:22:34', 1, 0, 1, 1, 24, '', '', 90, '13', 'Gruppe 4'),
(37, 35, '2025-10-20 16:37:13', 1, 0, 1, 1, 24, '', '', 90, '40', 'Gruppe 1'),
(38, 36, '2025-10-20 18:18:30', 1, 0, 1, 1, 24, '', '', 90, '23', 'Gruppe 2'),
(39, 37, '2025-10-21 16:48:19', 1, 0, 1, 1, 24, '', '', 90, '3', 'Gruppe 3'),
(40, 38, '2025-10-21 18:30:36', 1, 0, 1, 1, 24, '', '', 90, '5', 'Gruppe 4'),
(41, 39, '2025-10-22 17:15:36', 1, 0, 1, 1, 24, '', '', 90, '6', 'Gruppe 5'),
(42, 40, '2025-10-22 18:28:38', 1, 0, 1, 1, 24, '', '', 90, '14', 'Gruppe 6'),
(43, 41, '2025-10-23 16:43:26', 1, 0, 1, 1, 24, '', '', 90, '18', 'Gruppe 7'),
(44, 42, '2025-10-23 18:28:24', 1, 0, 1, 1, 24, '', '', 90, '17', 'Gruppe 8'),
(45, 43, '2025-10-24 16:56:31', 1, 0, 1, 1, 24, '', '', 90, '4', 'Gruppe 9');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_zuweisung_teilnehmer`
--

CREATE TABLE `ue_zuweisung_teilnehmer` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_zw_thema_id` int(11) NOT NULL,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  `teilnehmer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `ue_zuweisung_teilnehmer`
--

INSERT INTO `ue_zuweisung_teilnehmer` (`id`, `ue_unterrichtseinheit_zw_thema_id`, `datum`, `teilnehmer_id`) VALUES
(1, 1, '2025-09-16 19:26:45', 20),
(3, 4, '2025-09-09 17:10:00', 9),
(4, 4, '2025-09-09 17:10:00', 7),
(5, 4, '2025-09-09 17:10:00', 8),
(6, 4, '2025-09-09 17:10:00', 5),
(11, 7, '2025-09-11 15:35:00', 9),
(12, 7, '2025-09-11 15:35:00', 15),
(13, 7, '2025-09-11 15:35:00', 10),
(14, 8, '2025-09-11 17:10:00', 16),
(15, 8, '2025-09-11 17:10:00', 9),
(16, 8, '2025-09-11 17:10:00', 18),
(17, 8, '2025-09-11 17:10:00', 17),
(18, 8, '2025-09-11 17:10:00', 19),
(19, 9, '2025-09-09 15:35:00', 3),
(24, 5, '2025-09-10 15:35:00', 6),
(25, 5, '2025-09-10 15:35:00', 11),
(26, 6, '2025-09-10 17:10:00', 7),
(27, 6, '2025-09-10 17:10:00', 12),
(28, 6, '2025-09-10 17:10:00', 14),
(29, 6, '2025-09-10 17:10:00', 13),
(30, 6, '2025-09-10 17:10:00', 22),
(33, 12, '2025-09-15 15:35:00', 2),
(34, 13, '2025-09-15 17:10:00', 21),
(35, 13, '2025-09-15 17:10:00', 16),
(36, 13, '2025-09-15 17:10:00', 20),
(37, 13, '2025-09-15 17:10:00', 23),
(38, 14, '2025-09-17 15:35:00', 10),
(39, 14, '2025-09-17 15:35:00', 2),
(40, 6, '2025-09-10 17:10:00', 12),
(41, 6, '2025-09-10 17:10:00', 14),
(42, 18, '2025-09-19 15:35:00', 6),
(43, 18, '2025-09-19 15:35:00', 20),
(44, 18, '2025-09-19 15:35:00', 11),
(45, 18, '2025-09-19 15:35:00', 24),
(46, 18, '2025-09-19 15:35:00', 4),
(47, 12, '2025-09-22 15:35:00', 2),
(48, 20, '2025-09-22 17:10:00', 16),
(49, 20, '2025-09-22 17:10:00', 13),
(50, 20, '2025-09-22 17:10:00', 21),
(51, 20, '2025-09-22 17:10:00', 20),
(52, 13, '2025-09-22 17:10:00', 23),
(53, 21, '2025-09-23 15:35:00', 16),
(54, 4, '2025-09-23 17:10:00', 9),
(55, 4, '2025-09-23 17:10:00', 8),
(56, 22, '2025-09-23 17:10:00', 24),
(57, 23, '2025-09-24 15:35:00', 10),
(58, 5, '2025-09-24 15:35:00', 6),
(59, 23, '2025-09-24 15:35:00', 4),
(60, 6, '2025-09-24 17:10:00', 7),
(61, 6, '2025-09-24 17:10:00', 14),
(62, 6, '2025-09-24 17:10:00', 22),
(63, 6, '2025-09-24 17:10:00', 13),
(64, 25, '2025-09-25 15:35:00', 9),
(65, 25, '2025-09-25 15:35:00', 15),
(66, 7, '2025-09-25 15:35:00', 10),
(67, 8, '2025-09-25 17:10:00', 18),
(68, 8, '2025-09-25 17:10:00', 16),
(69, 8, '2025-09-25 17:10:00', 19),
(70, 8, '2025-09-25 17:10:00', 17),
(71, 18, '2025-09-26 15:35:00', 4),
(72, 27, '2025-09-26 15:35:00', 20),
(73, 27, '2025-09-26 15:35:00', 24),
(75, 12, '2025-09-29 15:35:00', 2),
(76, 29, '2025-09-29 17:10:00', 21),
(77, 29, '2025-09-29 17:10:00', 20),
(78, 29, '2025-09-29 17:10:00', 16),
(79, 13, '2025-09-29 17:10:00', 23),
(80, 30, '2025-09-30 15:35:00', 3),
(81, 4, '2025-09-30 17:10:00', 7),
(82, 4, '2025-09-30 17:10:00', 9),
(83, 4, '2025-09-30 17:10:00', 8),
(84, 22, '2025-09-30 17:10:00', 24),
(85, 4, '2025-09-30 17:10:00', 5),
(86, 5, '2025-10-01 15:35:00', 6),
(87, 5, '2025-10-01 15:35:00', 11),
(88, 14, '2025-10-01 15:35:00', 2),
(89, 32, '2025-10-01 15:35:00', 10),
(90, 6, '2025-10-01 17:10:00', 12),
(91, 6, '2025-10-01 17:10:00', 14),
(92, 6, '2025-10-01 17:10:00', 22),
(93, 34, '2025-10-02 15:35:00', 9),
(94, 7, '2025-10-02 15:35:00', 10),
(95, 8, '2025-10-02 17:10:00', 18),
(96, 8, '2025-10-02 17:10:00', 16),
(97, 8, '2025-10-02 17:10:00', 19),
(98, 8, '2025-10-02 17:10:00', 17),
(99, 36, '2025-10-07 17:10:00', 22),
(100, 4, '2025-10-07 17:10:00', 8),
(101, 36, '2025-10-07 17:10:00', 13),
(102, 12, '2025-10-20 15:35:00', 2),
(103, 37, '2025-10-20 15:35:00', 40),
(104, 38, '2025-10-20 17:10:00', 21),
(105, 38, '2025-10-20 17:10:00', 21),
(106, 38, '2025-10-20 17:10:00', 20),
(107, 13, '2025-10-20 17:10:00', 23),
(108, 30, '2025-10-21 15:35:00', 3),
(109, 4, '2025-10-21 17:10:00', 7),
(110, 4, '2025-10-21 17:10:00', 8),
(111, 4, '2025-10-21 17:10:00', 5),
(112, 5, '2025-10-22 15:35:00', 6),
(113, 6, '2025-10-22 17:10:00', 22),
(114, 6, '2025-10-22 17:10:00', 7),
(115, 6, '2025-10-22 17:10:00', 14),
(116, 43, '2025-10-23 15:35:00', 16),
(117, 43, '2025-10-23 15:35:00', 15),
(118, 43, '2025-10-23 15:35:00', 18),
(119, 8, '2025-10-23 17:10:00', 18),
(120, 8, '2025-10-23 17:10:00', 16),
(121, 8, '2025-10-23 17:10:00', 17),
(122, 45, '2025-10-24 15:35:00', 20),
(123, 45, '2025-10-24 15:35:00', 6),
(124, 18, '2025-10-24 15:35:00', 4);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `verhaltens_mapping`
--

CREATE TABLE `verhaltens_mapping` (
  `id` int(11) NOT NULL,
  `flag_typ` int(11) NOT NULL,
  `flag_text` varchar(255) NOT NULL,
  `spaltenname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `verhaltens_mapping`
--

INSERT INTO `verhaltens_mapping` (`id`, `flag_typ`, `flag_text`, `spaltenname`) VALUES
(1, 1, 'Absprachen einhaltend', 'absprachen'),
(2, 6, 'Absprachen nicht einhaltend', 'absprachen_nicht_einhaltend'),
(3, 1, 'beteiligt sich / gute Mitarbeit', 'mitarbeit'),
(4, 6, 'störend / blockierend / resignierend', 'stoerend_blockierend_resignierend'),
(5, 1, 'fleißig / bemüht', 'fleissig'),
(6, 6, 'desinteressiert / gleichgültig', 'desinteressiert_gleichgueltig'),
(7, 1, 'arbeitet selbstständig', 'selbststaendig'),
(8, 6, 'benötigt Aufforderung', 'benoetigt_aufforderung'),
(9, 1, 'konzentriert', 'konzentriert'),
(10, 6, 'unkonzentriert', 'unkonzentriert'),
(11, 1, 'vorbereitet', 'vorbereitet'),
(12, 6, 'Materialien fehlen', 'materialien_fehlen');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_frzk_hubs`
--

CREATE TABLE `_frzk_hubs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `typ` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='_frzk_hubs';

--
-- Daten für Tabelle `_frzk_hubs`
--

INSERT INTO `_frzk_hubs` (`id`, `name`, `typ`) VALUES
(1, 'Schlüsselfrage', 'kognitiv'),
(2, 'Kernbegriff-Einführung', 'kognitiv'),
(3, 'Konzeptuelle Brücke', 'kognitiv'),
(4, 'Fehleranalyse', 'kognitiv'),
(5, 'Peer-Erklärung', 'sozial'),
(6, 'Diskussionsrunde', 'sozial'),
(7, 'Gruppenentscheidung', 'sozial'),
(8, 'Kooperationscheck', 'sozial'),
(9, 'Aha-Erlebnis', 'affektiv'),
(10, 'Resonanzmoment', 'affektiv'),
(11, 'Frustrationsauflösung', 'affektiv'),
(12, 'Erfolgserfahrung', 'affektiv'),
(13, 'Zwischenreflexion', 'metakognitiv'),
(14, 'Lernweg-Skizze', 'metakognitiv'),
(15, 'Selbsteinschätzung', 'metakognitiv'),
(16, 'Transferfrage', 'metakognitiv'),
(17, 'Methodenbruch', 'methodisch'),
(18, 'Material-Peak', 'methodisch'),
(19, 'Aufgabenwende', 'methodisch'),
(20, 'Projektgipfel', 'methodisch'),
(21, 'σ-Hub', 'datenbankgestützt'),
(22, 'M-Hub', 'datenbankgestützt'),
(23, 'R-Hub', 'datenbankgestützt'),
(24, 'E-Hub', 'datenbankgestützt');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_definition_lernmethode`
--

CREATE TABLE `_mtr_definition_lernmethode` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `beschreibung` text NOT NULL,
  `kategorie` varchar(100) DEFAULT NULL,
  `baut_auf_auf` int(11) DEFAULT NULL,
  `kurzbezeichnung` varchar(100) NOT NULL,
  `schwerpunkt` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `_mtr_definition_lernmethode`
--

INSERT INTO `_mtr_definition_lernmethode` (`id`, `bezeichnung`, `beschreibung`, `kategorie`, `baut_auf_auf`, `kurzbezeichnung`, `schwerpunkt`) VALUES
(1, 'Lehrerzentrierte Methoden (Kategorie)', 'Methoden, bei denen die Lehrperson die Hauptrolle in der Wissensvermittlung und Steuerung des Lernprozesses spielt.', 'Lehrerzentriert', NULL, 'Lehrerzentriert', 'Wissensvermittlung'),
(2, 'Frontalunterricht', 'Die Lehrperson präsentiert Inhalte vor der gesamten Klasse, oft in Form eines Vortrags oder einer Demonstration.', 'Lehrerzentriert', 1, 'Frontal', 'Wissensvermittlung'),
(3, 'Vortrag/Vorlesung', 'Ausführliche, strukturierte mündliche Darstellung von Lehrinhalten, primär zur Informationsvermittlung.', 'Lehrerzentriert', 2, 'Vortrag', 'Wissensvermittlung'),
(4, 'Demonstration', 'Vorführung eines Vorgangs, Experiments oder einer Fertigkeit durch die Lehrperson.', 'Lehrerzentriert', 2, 'Demonstration', 'Wissensvermittlung, Fertigkeit'),
(5, 'Lehrgespräch', 'Gelenktes Gespräch, bei dem die Lehrperson durch Fragen die Erkenntnisentwicklung der Lernenden steuert.', 'Lehrerzentriert', 2, 'Lehrgespräch', 'Wissensvermittlung, Verständnis'),
(6, 'Schülerzentrierte Methoden (Kategorie)', 'Methoden, bei denen die Lernenden aktiv, selbstgesteuert und eigenverantwortlich lernen.', 'Schülerzentriert', NULL, 'Schülerzentriert', 'Kompetenzerwerb, Selbstständigkeit'),
(7, 'Gruppenarbeit', 'Lernende arbeiten in kleinen Teams an einer gemeinsamen Aufgabe, fördern Kooperation und Kommunikation.', 'Schülerzentriert', 6, 'Gruppenarbeit', 'Kooperation, Problemlösung'),
(8, 'Partnerarbeit', 'Zwei Lernende arbeiten zusammen, um eine Aufgabe zu lösen oder sich gegenseitig zu unterrichten.', 'Schülerzentriert', 6, 'Partnerarbeit', 'Interaktion, Übung'),
(9, 'Projektarbeit', 'Lernende planen, realisieren und präsentieren ein komplexes Projekt über einen längeren Zeitraum.', 'Schülerzentriert', 6, 'Projektarbeit', 'Kompetenzerwerb, Anwendung'),
(10, 'Stationenlernen', 'Lernende bearbeiten Aufgaben an verschiedenen Stationen in ihrem eigenen Tempo.', 'Schülerzentriert', 6, 'Stationenlernen', 'Individualisierung, Aktivierung'),
(11, 'Lernen durch Lehren (LdL)', 'Lernende bereiten Inhalte auf, um sie ihren Mitschülern zu vermitteln, vertieftes Verständnis.', 'Schülerzentriert', 6, 'Lernen durch Lehren', 'Vertiefung, Präsentation'),
(12, 'Fallstudienarbeit', 'Analyse realer oder fiktiver Fälle zur Problemlösung und Entscheidungsfindung.', 'Schülerzentriert', 6, 'Fallstudie', 'Analyse, Problemlösung'),
(13, 'Rollenspiel/Simulation', 'Nachstellung von Situationen zur Einübung von Verhaltensweisen oder zur Perspektivübernahme.', 'Schülerzentriert', 6, 'Rollenspiel', 'Sozialkompetenz, Empathie'),
(14, 'Digitale & Blended-Learning Methoden (Kategorie)', 'Methoden, die digitale Medien und Technologien zur Unterstützung des Lernprozesses nutzen.', 'Digital/Blended', NULL, 'Digital', 'Flexibilität, Medienkompetenz'),
(15, 'E-Learning (asynchron)', 'Selbstgesteuertes Lernen über Online-Plattformen und digitale Materialien, zeitlich und räumlich flexibel.', 'Digital/Blended', 14, 'E-Learning', 'Selbstlernen, Flexibilität'),
(16, 'WebQuest', 'Geführte Internetrecherche zur Lösung einer komplexen Aufgabe.', 'Digital/Blended', 14, 'WebQuest', 'Recherche, Problemlösung'),
(17, 'Blended Learning', 'Kombination aus Präsenzunterricht und Online-Lernphasen.', 'Digital/Blended', 14, 'Blended Learning', 'Flexibilität, Interaktion'),
(18, 'Flipped Classroom', 'Inhalte werden zu Hause digital erarbeitet, die Präsenzzeit für Vertiefung und Anwendung genutzt.', 'Digital/Blended', 17, 'Flipped Classroom', 'Anwendung, Vertiefung'),
(19, 'Offene & Experimentelle Methoden (Kategorie)', 'Methoden, die Raum für individuelle Entdeckungen, kreatives Lernen und eigenständiges Forschen lassen.', 'Offen/Experimentell', NULL, 'Offen/Experimentell', 'Entdeckung, Forschung'),
(20, 'Werkstattunterricht', 'Angebot verschiedener Aufgaben, aus denen Lernende selbst wählen und in eigenem Tempo arbeiten.', 'Offen/Experimentell', 19, 'Werkstatt', 'Selbststeuerung, Individualisierung'),
(21, 'Experiment', 'Lernende führen Versuche durch, um Hypothesen zu überprüfen oder Phänomene zu beobachten.', 'Offen/Experimentell', 19, 'Experiment', 'Forschung, Erkenntnisgewinn'),
(22, 'Freie Arbeit', 'Lernende wählen Thema, Methode und Tempo selbst, oft mit individuellem Coaching durch die Lehrperson.', 'Offen/Experimentell', 19, 'Freie Arbeit', 'Autonomie, Vertiefung'),
(23, 'Grundlegende Lern- und Übungsformen (Kategorie)', 'Methoden, die auf die Festigung von Wissen und Fähigkeiten sowie auf die Automatisierung von Prozessen abzielen.', 'Grundlagen', NULL, 'GrundlagenLernen', 'Festigung, Automatisierung'),
(24, 'Üben', 'Systematisches Wiederholen von Aufgaben, Inhalten oder Bewegungsabläufen zur Festigung und Automatisierung von Kenntnissen und Fertigkeiten.', 'Grundlagen', 23, 'Üben', 'Festigung, Fertigkeit'),
(25, 'Wiederholen', 'Erneutes Durchgehen von Lerninhalten zur Verankerung im Langzeitgedächtnis und zur Überprüfung des Verständnisses.', 'Grundlagen', 23, 'Wiederholen', 'Gedächtnis, Verständnis'),
(26, 'Drill & Practice', 'Intensives, oft repetitives Training von grundlegenden Fertigkeiten oder Fakten, um Geschwindigkeit und Genauigkeit zu erhöhen.', 'Grundlagen', 24, 'Drill', 'Automatisierung, Genauigkeit'),
(27, 'Merkübungen', 'Gezielte Übungen zur Unterstützung des Auswendiglernens und des Abrufs von Fakten oder Definitionen.', 'Grundlagen', 25, 'Merkübungen', 'Gedächtnis, Abruf'),
(28, 'Anwendungsaufgaben lösen', 'Bearbeitung von Aufgaben, die die Anwendung erlernter Regeln, Formeln oder Konzepte in vorgegebenen Kontexten erfordern.', 'Grundlagen', 24, 'Anwendungsaufgaben', 'Anwendung, Transfer'),
(29, 'Fehleranalyse', 'Systematisches Untersuchen eigener Fehler, um Ursachen zu identifizieren und das Verständnis zu vertiefen.', 'Grundlagen', 24, 'Fehleranalyse', 'Metakognition, Verständnis');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_definition_zieltyp`
--

CREATE TABLE `_mtr_definition_zieltyp` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `beschreibung` text NOT NULL,
  `baut_auf_auf` int(11) DEFAULT NULL,
  `kurzbezeichnung` varchar(100) NOT NULL,
  `ebene` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `_mtr_definition_zieltyp`
--

INSERT INTO `_mtr_definition_zieltyp` (`id`, `bezeichnung`, `beschreibung`, `baut_auf_auf`, `kurzbezeichnung`, `ebene`) VALUES
(1, 'Remembering (Kategorie)', 'Die Lernenden können relevantes Wissen aus dem Langzeitgedächtnis abrufen.', NULL, 'Erinnern', 'Remembering'),
(2, 'Wiedererkennen', 'Die Lernenden können Informationen aus einer Liste identifizieren.', 1, 'Wiedererkennen', 'Remembering'),
(3, 'Abrufen', 'Die Lernenden können Informationen ohne Hinweise reproduzieren.', 1, 'Abrufen', 'Remembering'),
(4, 'Benennen', 'Die Lernenden können Namen, Begriffe oder Daten korrekt zuordnen.', 1, 'Benennen', 'Remembering'),
(5, 'Auflisten', 'Die Lernenden können Elemente einer Gruppe systematisch aufzählen.', 1, 'Auflisten', 'Remembering'),
(6, 'Understanding (Kategorie)', 'Die Lernenden können die Bedeutung von Anweisungen oder Botschaften konstruieren, indem sie interpretieren, illustrieren, klassifizieren, zusammenfassen, schlussfolgern, vergleichen oder erklären.', NULL, 'Verstehen', 'Understanding'),
(7, 'Interpretieren', 'Die Lernenden können Bedeutungen von einem Format ins andere umwandeln (z.B. Wörter in Zahlen).', 6, 'Interpretieren', 'Understanding'),
(8, 'Illustrieren (Beispiele geben)', 'Die Lernenden können konkrete Beispiele für Konzepte oder Prinzipien liefern.', 6, 'Illustrieren', 'Understanding'),
(9, 'Klassifizieren', 'Die Lernenden können Objekte oder Ideen in Kategorien einordnen.', 6, 'Klassifizieren', 'Understanding'),
(10, 'Zusammenfassen', 'Die Lernenden können die Hauptgedanken eines Textes oder einer Präsentation in kondensierter Form wiedergeben.', 6, 'Zusammenfassen', 'Understanding'),
(11, 'Schlussfolgern', 'Die Lernenden können Muster oder Bedeutungen über das Gegebene hinaus erkennen.', 6, 'Schlussfolgern', 'Understanding'),
(12, 'Vergleichen', 'Die Lernenden können Ähnlichkeiten und Unterschiede zwischen zwei oder mehr Objekten oder Ideen aufzeigen.', 6, 'Vergleichen', 'Understanding'),
(13, 'Erklären', 'Die Lernenden können eine Ursache-Wirkungs-Beziehung oder ein Prinzip darstellen.', 6, 'Erklären', 'Understanding'),
(14, 'Applying (Kategorie)', 'Die Lernenden können ein Verfahren ausführen oder verwenden.', NULL, 'Anwenden', 'Applying'),
(15, 'Ausführen', 'Die Lernenden können ein Verfahren oder eine Methode in einer Standardaufgabe anwenden.', 14, 'Ausführen', 'Applying'),
(16, 'Implementieren', 'Die Lernenden können ein Verfahren auf eine neuartige oder unvertraute Situation übertragen.', 14, 'Implementieren', 'Applying'),
(17, 'Probleme anwenden', 'Die Lernenden können passende Konzepte und Prinzipien zur Problemlösung nutzen.', 14, 'Probleme Anwenden', 'Applying'),
(18, 'Analyzing (Kategorie)', 'Die Lernenden können Material in seine Einzelteile zerlegen und deren Beziehungen zueinander oder zur Gesamtstruktur bestimmen.', NULL, 'Analysieren', 'Analyzing'),
(19, 'Differenzieren', 'Die Lernenden können relevante von irrelevanten Informationen unterscheiden.', 18, 'Differenzieren', 'Analyzing'),
(20, 'Organisieren', 'Die Lernenden können Elemente in eine zusammenhängende Struktur oder Form bringen.', 18, 'Organisieren', 'Analyzing'),
(21, 'Attribuieren (Zuschreiben)', 'Die Lernenden können die Perspektive, den Bias, die Werte oder die Absichten hinter dem Material bestimmen.', 18, 'Attribuieren', 'Analyzing'),
(22, 'Strukturieren', 'Die Lernenden können die interne Konsistenz oder die logische Organisation eines Materials bewerten.', 18, 'Strukturieren', 'Analyzing'),
(23, 'Evaluating (Kategorie)', 'Die Lernenden können Urteile auf der Grundlage von Kriterien und Standards treffen.', NULL, 'Evaluieren', 'Evaluating'),
(24, 'Überprüfen (Konsistenz)', 'Die Lernenden können die Konsistenz oder die logische Stimmigkeit eines Materials bewerten.', 23, 'Überprüfen', 'Evaluating'),
(25, 'Kritisieren (Effektivität)', 'Die Lernenden können die Effektivität eines Produktes oder Prozesses basierend auf externen Kriterien beurteilen.', 23, 'Kritisieren', 'Evaluating'),
(26, 'Beurteilen', 'Die Lernenden können die Qualität, den Wert oder die Bedeutung von Ideen oder Lösungen einschätzen.', 23, 'Beurteilen', 'Evaluating'),
(27, 'Creating (Kategorie)', 'Die Lernenden können Elemente zusammenfügen, um ein kohärentes oder funktionales Ganzes zu bilden; ein neues Produkt oder eine neue Perspektive reorganisieren.', NULL, 'Kreieren', 'Creating'),
(28, 'Generieren', 'Die Lernenden können Hypothesen oder alternative Lösungen entwickeln.', 27, 'Generieren', 'Creating'),
(29, 'Planen', 'Die Lernenden können Vorgehensweisen oder Methoden zur Lösung einer Aufgabe entwerfen.', 27, 'Planen', 'Creating'),
(30, 'Produzieren', 'Die Lernenden können ein materielles oder immaterielles Produkt erstellen.', 27, 'Produzieren', 'Creating'),
(31, 'Entwerfen', 'Die Lernenden können ein Konzept oder Modell neu gestalten oder anpassen.', 27, 'Entwerfen', 'Creating');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_einrichtung`
--

CREATE TABLE `_mtr_einrichtung` (
  `id` int(11) NOT NULL,
  `einrichtung` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_mtr_einrichtung`
--

INSERT INTO `_mtr_einrichtung` (`id`, `einrichtung`) VALUES
(1, 'Schülerhilfe'),
(2, 'Studienkreis');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_emotionen`
--

CREATE TABLE `_mtr_emotionen` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) DEFAULT NULL,
  `fine_label` varchar(100) DEFAULT NULL,
  `show_emotion` tinyint(1) NOT NULL DEFAULT 1,
  `emotion` varchar(50) NOT NULL,
  `map_field` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_mtr_emotionen`
--

INSERT INTO `_mtr_emotionen` (`id`, `type_name`, `fine_label`, `show_emotion`, `emotion`, `map_field`) VALUES
(1, 'positiv', 'Glücksgefühl', 1, 'Freude', 'freude'),
(2, 'positiv', 'Glücksgefühl', 1, 'Zufriedenheit', 'zufriedenheit'),
(3, 'positiv', 'Glücksgefühl', 1, 'Erfüllung', 'erfuellung'),
(4, 'positiv', 'Aktivierendes positives Gefühl', 1, 'Motivation', 'motivation'),
(5, 'positiv', 'Soziales positives Gefühl', 1, 'Dankbarkeit', 'dankbarkeit'),
(6, 'positiv', 'Zukunftsorientiertes positives Gefühl', 1, 'Hoffnung', 'hoffnung'),
(7, 'positiv', 'Soziales positives Gefühl', 1, 'Stolz', 'stolz'),
(8, 'positiv', 'Soziales positives Gefühl', 1, 'Selbstvertrauen', 'selbstvertrauen'),
(9, 'positiv', 'Aktivierendes positives Gefühl', 1, 'Neugier', 'neugier'),
(10, 'positiv', 'Aktivierendes positives Gefühl', 1, 'Inspiration', 'inspiration'),
(11, 'positiv', 'Soziales positives Gefühl', 1, 'Zugehörigkeit', 'zugehoerigkeit'),
(12, 'positiv', 'Soziales positives Gefühl', 1, 'Vertrauen', 'vertrauen'),
(13, 'positiv', 'Glücksgefühl', 1, 'Spaß', 'spass'),
(14, 'positiv', 'Soziales positives Gefühl', 1, 'Sicherheit', 'sicherheit'),
(15, 'negativ', 'Stress-/Überforderungsgefühl', 1, 'Frustration', 'frustration'),
(16, 'negativ', 'Stress-/Überforderungsgefühl', 1, 'Überforderung', 'ueberforderung'),
(17, 'negativ', 'Ängstlich-vermeidendes Gefühl', 1, 'Angst', 'angst'),
(18, 'negativ', 'Niedergeschlagenheit/Rückzug', 1, 'Langeweile', 'langeweile'),
(19, 'negativ', 'Niedergeschlagenheit/Rückzug', 1, 'Scham', 'scham'),
(20, 'negativ', 'Ängstlich-vermeidendes Gefühl', 1, 'Zweifel', 'zweifel'),
(21, 'negativ', 'Niedergeschlagenheit/Rückzug', 1, 'Resignation', 'resignation'),
(22, 'negativ', 'Stress-/Überforderungsgefühl', 1, 'Erschöpfung', 'erschoepfung'),
(23, 'kognitiv', 'Erkenntnis-/Bewertungsgefühl', 1, 'Interesse', 'interesse'),
(24, 'kognitiv', 'Erkenntnis-/Bewertungsgefühl', 1, 'Verwirrung', 'verwirrung'),
(25, 'kognitiv', 'Ängstlich-vermeidendes Gefühl', 1, 'Unsicherheit', 'unsicherheit'),
(26, 'kognitiv', 'Erwartungsbezogenes Gefühl', 1, 'Überraschung', 'ueberraschung'),
(27, 'kognitiv', 'Erwartungsbezogenes Gefühl', 1, 'Erwartung', 'erwartung'),
(28, 'kognitiv', 'Erwartungsbezogenes Gefühl', 1, 'Erleichterung', 'erleichterung');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--

CREATE TABLE `_mtr_persoenlichkeitsmerkmal_definition` (
  `merkmal_id` int(11) NOT NULL,
  `merkmal_name` varchar(255) NOT NULL,
  `beschreibung_allgemein` text DEFAULT NULL,
  `theoretischer_hintergrund` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--

INSERT INTO `_mtr_persoenlichkeitsmerkmal_definition` (`merkmal_id`, `merkmal_name`, `beschreibung_allgemein`, `theoretischer_hintergrund`) VALUES
(1, 'offenheit_erfahrungen', 'Offenheit für neue Erfahrungen, Lerninhalte und Situationen.', 'Könnte sich in der Dynamik der Akteur-Funktion A(Ψ,x,y,z,t) äußern, die exploratives Verhalten in Bezug auf das \"Feld\" (Ψ) anregt. Verbunden mit der Fähigkeit zur Reflexion (Meta-Funktion) über neue Bedeutungsfelder und der Bereitschaft, etablierte semantische Attraktoren zu verlassen. Kann sich in der Offenheit für die Transformation symbolischer Meta-Strukturen zeigen.'),
(2, 'gewissenhaftigkeit', 'Gewissenhaftigkeit und Leistungsbereitschaft.', 'Könnte sich in der Dynamik der Akteur-Funktion A(Ψ,x,y,z,t) äußern, die exploratives Verhalten in Bezug auf das \"Feld\" (Ψ) anregt. Verbunden mit der Fähigkeit zur Reflexion (Meta-Funktion) über neue Bedeutungsfelder und der Bereitschaft, etablierte semantische Attraktoren zu verlassen. Kann sich in der Offenheit für die Transformation symbolischer Meta-Strukturen zeigen. Hohe Ausprägung könnte mit der Stabilität der Akteur-Funktion und der Fähigkeit zur Aufrechterhaltung von Zielen korrelieren.'),
(3, 'extraversion', 'Extraversion und soziale Interaktion.', 'Könnte sich in der Interaktion der Akteur-Funktion A(Ψ,x,y,z,t) mit sozialen \"Feldern\" (Ψ) manifestieren. Hohe Ausprägung ist verbunden mit der Fähigkeit, sich in soziale Interaktionen zu begeben und diese aktiv zu gestalten. Könnte auch mit der energetischen Komponente der Meta-Funktion M(A,Ψ,x,y,z,t) zusammenhängen, die die Bereitschaft zur Exploration und Einflussnahme auf die Umgebung fördert.'),
(4, 'vertraeglichkeit', 'Verträglichkeit und Kooperationsbereitschaft.', 'Zeigt sich in der Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t), sich an gemeinsame \"Feldzustände\" anzupassen und kooperatives Verhalten zu zeigen. Könnte auch mit der Resonanz der Meta-Funktion M(A,Ψ,x,y,z,t) auf die emotionalen und sozialen Signale anderer Akteure zusammenhängen. Hohe Ausprägung fördert die Bildung stabiler semantischer Attraktoren im sozialen Kontext.'),
(5, 'zielorientierung', 'Zielorientierung und Fokus.', 'Spiegelt die Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t) wider, spezifische \"Feldzustände\" als Ziele zu definieren und darauf hinzuarbeiten. Verbunden mit der Kohärenz der Meta-Funktion M(A,Ψ,x,y,z,t) in Bezug auf die Planung und Durchführung von Handlungen. Hohe Ausprägung könnte mit der Stärke der semantischen Attraktoren für bestimmte Ziele zusammenhängen.'),
(6, 'lernfaehigkeit', 'Lernfähigkeit und Adaptivität.', 'Beschreibt die Plastizität der Akteur-Funktion A(Ψ,x,y,z,t) und ihre Fähigkeit, neue \"Feldzustände\" und Verhaltensweisen zu internalisieren. Verbunden mit der Agilität der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Neubildung und Transformation von Wissensstrukturen. Hohe Ausprägung ist essentiell für die Anpassung an sich ändernde Umgebungen und die effektive Nutzung neuer Informationen.'),
(7, 'anpassungsfaehigkeit', 'Anpassungsfähigkeit an neue Situationen und Anforderungen.', 'Zeigt sich in der Flexibilität der Akteur-Funktion A(Ψ,x,y,z,t) und ihrer Fähigkeit, sich an unvorhergesehene \"Feldzustände\" anzupassen. Könnte auch mit der Robustheit der Meta-Funktion M(A,Ψ,x,y,z,t) unter Stressbedingungen und ihrer Fähigkeit zur Reorganisation von Handlungsplänen zusammenhängen. Hohe Ausprägung ermöglicht die effiziente Navigation in dynamischen Umgebungen.'),
(8, 'soziale_interaktion', 'Fähigkeit zur sozialen Interaktion und Kommunikation.', 'Betont die bidirektionale Kopplung der Akteur-Funktion A(Ψ,x,y,z,t) mit den sozialen \"Feldern\" (Ψ) und die Fähigkeit zur gemeinsamen Konstruktion von Bedeutung. Verbunden mit der Kommunikationsfähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t) in der Koordination mit anderen Akteuren. Hohe Ausprägung ist entscheidend für effektive Zusammenarbeit und den Aufbau sozialer Netzwerke.'),
(9, 'metakognition', 'Metakognitive Fähigkeiten und Selbstreflexion.', 'Beschreibt die Fähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t), die eigene Akteur-Funktion A(Ψ,x,y,z,t) und die Interaktion mit dem \"Feld\" (Ψ) zu beobachten, zu bewerten und zu regulieren. Dies beinhaltet das Verständnis der eigenen Lernprozesse und der Wirksamkeit der angewandten Strategien. Hohe Ausprägung ermöglicht eine bewusste Steuerung von Lern- und Handlungsprozessen.'),
(10, 'stressbewaeltigung', 'Stressbewältigungsstrategien und emotionale Regulation.', 'Bezieht sich auf die Resilienz der Akteur-Funktion A(Ψ,x,y,z,t) und ihre Fähigkeit, unter Druck effektive Handlungen aufrechtzuerhalten. Verbunden mit der Regulationsfähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Verarbeitung von emotionalen \"Feldzuständen\". Hohe Ausprägung ermöglicht es, Herausforderungen konstruktiv zu begegnen und psychische Belastungen zu reduzieren.'),
(11, 'bedeutungsbildung', 'Bedeutungsbildung und Sinnstiftung.', 'Umfasst die Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t), kohärente \"Feldzustände\" zu schaffen und diesen eine persönliche oder kollektive Bedeutung zu verleihen. Verbunden mit der integrativen Funktion der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Synthese von Erfahrungen und der Konstruktion von Weltbildern. Hohe Ausprägung ist fundamental für die persönliche Entwicklung und das Gefühl der Kohärenz.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_soziale_beziehung_type`
--

CREATE TABLE `_mtr_soziale_beziehung_type` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_mtr_soziale_beziehung_type`
--

INSERT INTO `_mtr_soziale_beziehung_type` (`id`, `name`) VALUES
(1, 'Persönlich'),
(2, 'Familiär'),
(3, 'Beruflich'),
(4, 'Gesellschaftlich'),
(5, 'Digital');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_soziale_beziehung_werte`
--

CREATE TABLE `_mtr_soziale_beziehung_werte` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `anzeigen` tinyint(1) NOT NULL,
  `bezeichnung` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_mtr_soziale_beziehung_werte`
--

INSERT INTO `_mtr_soziale_beziehung_werte` (`id`, `type_id`, `anzeigen`, `bezeichnung`) VALUES
(1, 1, 1, 'Freundschaft'),
(2, 1, 0, 'Beste Freundschaft'),
(3, 1, 0, 'Bekanntschaft'),
(4, 1, 0, 'Liebesbeziehung / Partnerschaft'),
(5, 1, 0, 'Ehe / Lebensgemeinschaft'),
(6, 1, 0, 'Ex-Partner/in'),
(7, 1, 0, 'Affäre'),
(8, 1, 0, 'Mitbewohner/in'),
(9, 2, 0, 'Eltern-Kind'),
(10, 2, 0, 'Geschwister'),
(11, 2, 0, 'Großeltern-Enkel'),
(12, 2, 0, 'Tante/Onkel – Nichte/Neffe'),
(13, 2, 0, 'Cousin/Cousine'),
(14, 2, 0, 'Schwiegerbeziehungen'),
(15, 2, 0, 'Pflege-/Adoptivverhältnis'),
(16, 2, 0, 'Patchwork-Familie'),
(17, 3, 0, 'Kollege/Kollegin'),
(18, 3, 0, 'Vorgesetzter'),
(19, 3, 0, 'Mitarbeiter'),
(20, 3, 0, 'Geschäftspartner'),
(21, 3, 0, 'Kunde – Dienstleister'),
(22, 3, 0, 'Mentor – Mentee'),
(23, 3, 0, 'Teammitglied'),
(24, 3, 0, 'Netzwerkkontakt'),
(25, 3, 0, 'Lehrer – Schüler'),
(26, 3, 0, 'Erzieher – Kind'),
(27, 3, 0, 'Trainer – Schützling'),
(28, 3, 0, 'Mitschüler / Kommilitonen'),
(29, 4, 0, 'Nachbar'),
(30, 4, 0, 'Vereinsmitglied'),
(31, 4, 0, 'Therapeut – Klient'),
(32, 4, 0, 'Arzt – Patient'),
(33, 4, 0, 'Seelsorger – Ratsuchender'),
(34, 4, 0, 'Sozialarbeiter – Klient'),
(35, 4, 0, 'Polizist – Bürger'),
(36, 4, 0, 'Beamter – Antragsteller'),
(37, 5, 0, 'Online-Freund'),
(38, 5, 0, 'Follower – Influencer'),
(39, 5, 0, 'Gaming-Bekanntschaft'),
(40, 5, 0, 'Online-Dating-Kontakt'),
(41, 5, 0, 'Community-Mitglied');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_std_lernthema_inhalt`
--

CREATE TABLE `_std_lernthema_inhalt` (
  `id` int(11) NOT NULL,
  `std_lernthema_id` int(11) DEFAULT NULL,
  `inhalt` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_std_lernthema_inhalt`
--

INSERT INTO `_std_lernthema_inhalt` (`id`, `std_lernthema_id`, `inhalt`) VALUES
(1, 1, 'Alle Werte des Kreises berechnen'),
(2, 1, 'Kreisausschnitt/Kreisbogen & Kreisring'),
(3, 1, 'Sachaufgaben zu Zylinder'),
(4, 1, 'Textaufgaben zu Kreisen'),
(5, 1, 'Umfang & Flächeninhalt berechnen'),
(6, 1, 'Zylindermantel'),
(7, 1, 'Zylinderoberfläche'),
(8, 1, 'Zylindervolumen'),
(9, 2, 'Allgemeine Form in Scheitelpunktform umwandeln'),
(10, 2, 'Beliebig Verschieben: f(x) = (x - d)² + e'),
(11, 2, 'Horizontal Verschieben: f(x) = (x - d)²'),
(12, 2, 'Normalparabel: f(x) = x²'),
(13, 2, 'Sachaufgaben'),
(14, 2, 'Scheitelpunktform in Allgemeine Form umwandeln'),
(15, 2, 'Scheitelpunktform: f(x) = a(x - d)² + e'),
(16, 2, 'Strecken'),
(17, 2, 'Stauchen'),
(18, 2, 'Spiegeln: f(x) = ax²'),
(19, 2, 'Vertikal Verschieben: f(x) = x² + c'),
(20, 3, 'Biquadratische Gleichungen (Substitution, Resubstitution)'),
(21, 3, 'Diskriminante'),
(22, 3, 'Grafisches Lösen'),
(23, 3, 'Mitternachtsformel'),
(24, 3, 'Quadratische Ungleichungen'),
(25, 3, 'Rechnerisches Lösen I: Reinquadratisch'),
(26, 3, 'Rechnerisches Lösen II: (x + d)² - Form'),
(27, 3, 'Rechnerisches Lösen III: Quadratische Ergänzung'),
(28, 3, 'Rechnerisches Lösen IV: P-Q-Formel'),
(29, 3, 'Rechnerisches Lösen V: I-IV vermischt'),
(30, 3, 'Satz von Vieta'),
(31, 3, 'Textaufgaben'),
(32, 3, 'Zerlegung in Linearfaktoren'),
(33, 3, 'abc-Formel'),
(34, 4, 'Näherungsweise Wurzelziehen'),
(35, 4, 'Potenzdarstellung von Wurzeln'),
(36, 4, 'Quadrieren & Wurzelziehen'),
(37, 4, 'Reelle Zahlenbereiche'),
(38, 4, 'Wurzelgleichungen'),
(39, 5, 'Anwendung'),
(40, 5, 'Grundlagen'),
(41, 5, 'Höhen- & Kathetensatz'),
(42, 6, 'Grundlagen'),
(43, 7, 'Zinseszinsen'),
(44, 8, 'Maßstab / Längenverhältnisse'),
(45, 8, 'Strahlensatz I'),
(46, 8, 'Strahlensatz II'),
(47, 8, 'Strahlensatz I & II'),
(48, 8, 'Zentrische Streckung'),
(49, 8, 'Ähnlichkeit'),
(50, 8, 'Ähnlichkeit bei Dreiecken'),
(51, 9, 'Boxplots (Begriffe, Zeichnung, Ablesen)'),
(52, 10, 'Anwendung bei Zinseszinsrechnung'),
(53, 10, 'Beschränktes & Logistisches Wachstum'),
(54, 10, 'Grundlagen'),
(55, 10, 'Logarithmen'),
(56, 10, 'Logarithmen - Exponentialgleichungen'),
(57, 10, 'Logarithmengesetze'),
(58, 11, 'Binomialkoeffizient'),
(59, 11, 'Kombinatorik'),
(60, 12, 'Kegel'),
(61, 12, 'Kegelstumpf'),
(62, 12, 'Kugel'),
(63, 12, 'Pyramide'),
(64, 12, 'Pyramidenstumpf'),
(65, 12, 'Sachaufgaben'),
(66, 12, 'Textaufgaben'),
(67, 12, 'Zusammengesetzte Körper'),
(68, 13, '10er Potenzen'),
(69, 13, 'Grundlagen'),
(70, 13, 'Potenzen mit ganzzahligen / rationalen Exponenten'),
(71, 13, 'Potenzfunktionen'),
(72, 13, 'Potenzgesetze'),
(73, 13, 'Wurzelgesetze'),
(74, 13, 'Wurzeln'),
(75, 14, 'Anwendung im Körper'),
(76, 14, 'Anwendung im beliebigen Dreieck'),
(77, 14, 'Anwendung im gleichschenkligen Dreieck'),
(78, 14, 'Anwendung im rechtwinkl. Dreieck'),
(79, 14, 'Anwendung in Textaufgaben'),
(80, 14, 'Anwendung in beliebigen Figuren'),
(81, 14, 'Sinus'),
(82, 14, 'Kosinus'),
(83, 14, 'Tangens im Einheitskreis'),
(84, 15, 'Anwendung im Sachaufgaben'),
(85, 15, 'Einheitskreis'),
(86, 15, 'Funktionen verändern'),
(87, 15, 'Grundlagen'),
(128, 16, 'Rationale Zahlen'),
(129, 16, 'Rechnen mit rationalen Zahlen'),
(130, 17, 'Rationale Zahlen'),
(131, 17, 'Rechnen mit rationalen Zahlen'),
(132, 18, 'Rationale Zahlen'),
(133, 18, 'Rechnen mit rationalen Zahlen'),
(134, 18, 'Irrationale Zahlen'),
(135, 19, 'Grundbegriffe der Geometrie'),
(136, 19, 'Flächenberechnung'),
(137, 20, 'Grundbegriffe der Geometrie'),
(138, 20, 'Flächenberechnung'),
(139, 21, 'Grundbegriffe der Geometrie'),
(140, 21, 'Flächenberechnung'),
(141, 21, 'Körperberechnung'),
(142, 22, 'Umfang und Flächeninhalt'),
(143, 22, 'Volumen'),
(144, 23, 'Umfang und Flächeninhalt'),
(145, 23, 'Volumen'),
(146, 24, 'Umfang und Flächeninhalt'),
(147, 24, 'Volumen'),
(148, 24, 'Masse und Gewicht'),
(149, 25, 'Proportionale und antiproportionale Zuordnungen'),
(150, 26, 'Proportionale und antiproportionale Zuordnungen'),
(151, 26, 'Lineare Funktionen'),
(152, 27, 'Terme und Gleichungen'),
(153, 27, 'Lineare Gleichungssysteme'),
(154, 28, 'Zufallsexperimente'),
(155, 28, 'Wahrscheinlichkeiten'),
(156, 29, 'Quadratische Funktionen darstellen'),
(157, 29, 'Quadratische Gleichungen lösen'),
(158, 30, 'Quadratische Funktionen darstellen'),
(159, 30, 'Quadratische Gleichungen lösen'),
(160, 31, 'Quadratische Funktionen darstellen'),
(161, 31, 'Quadratische Gleichungen lösen'),
(162, 31, 'Anwendungen quadratischer Funktionen'),
(163, 32, 'Volumen und Oberfläche von Pyramiden'),
(164, 32, 'Volumen und Oberfläche von Kegeln'),
(165, 33, 'Volumen und Oberfläche von Pyramiden'),
(166, 33, 'Volumen und Oberfläche von Kegeln'),
(167, 34, 'Volumen und Oberfläche von Pyramiden'),
(168, 34, 'Volumen und Oberfläche von Kegeln'),
(169, 34, 'Volumen und Oberfläche von Kugeln'),
(170, 35, 'Zufallsexperimente und Wahrscheinlichkeiten'),
(171, 35, 'Mehrstufige Zufallsexperimente'),
(172, 36, 'Sinus, Kosinus und Tangens im rechtwinkligen Dreieck'),
(173, 36, 'Sinussatz und Kosinussatz'),
(174, 37, 'Geraden und ihre Gleichungen'),
(175, 37, 'Kreise und ihre Gleichungen'),
(176, 38, 'Zahlen lesen und schreiben'),
(177, 38, 'Zahlen ordnen und vergleichen'),
(178, 39, 'Zahlen lesen und schreiben'),
(179, 39, 'Zahlen ordnen und vergleichen'),
(180, 40, 'Zahlen lesen und schreiben'),
(181, 40, 'Zahlen ordnen und vergleichen'),
(182, 40, 'Runden von Zahlen'),
(183, 41, 'Einfache geometrische Formen erkennen und benennen'),
(184, 41, 'Zeichnen von Linien und einfachen Formen'),
(185, 42, 'Einfache geometrische Formen erkennen und benennen'),
(186, 42, 'Zeichnen von Linien und einfachen Formen'),
(187, 43, 'Einfache geometrische Formen erkennen und benennen'),
(188, 43, 'Zeichnen von Linien und einfachen Formen'),
(189, 43, 'Symmetrie erkennen'),
(190, 44, 'Umgang mit Geld'),
(191, 44, 'Umgang mit Längen'),
(192, 45, 'Umgang mit Geld'),
(193, 45, 'Umgang mit Längen'),
(194, 46, 'Umgang mit Geld'),
(195, 46, 'Umgang mit Längen'),
(196, 46, 'Einfache Sachaufgaben lösen'),
(197, 47, 'Teil A'),
(198, 47, 'Teil B'),
(199, 47, 'komplexe Übung'),
(200, 48, 'Größen: Strecke, Zeit, Geschwindigkeit'),
(201, 48, 'Kräfte: Grundbegriffe, Kraftpfeile'),
(202, 48, 'Arbeit und Energie: Energieerhaltung, einfache Maschinen'),
(203, 49, 'Stromkreisaufbau: Quelle, Verbraucher, Leiter'),
(204, 49, 'Elektrische Größen: Spannung, Stromstärke, Widerstand'),
(205, 49, 'Ohmsches Gesetz, Messung'),
(206, 49, 'Magnetismus: Magnetfeld, Elektromagnet'),
(207, 50, 'Schall: Ausbreitung, Frequenz, Lautstärke'),
(208, 50, 'Wellenmodell: Überlagerung, Interferenz'),
(209, 50, 'Licht: Reflexion, Brechung, Totalreflexion'),
(210, 50, 'Linsen: Sammel-/Zerstreuung, Bildkonstruktion'),
(211, 51, 'Temperatur und Wärme: Temperaturbegriffe, Wärmekapazität, Wärmeleitung'),
(212, 51, 'Zustandsgrößen idealer Gase (p, V, T)'),
(213, 51, 'Teilchenmodell: Diffusion, Brownsche Bewegung'),
(214, 51, 'Atom- & Kernphysik: Radioaktivität, Zerfallsgesetz, Kernreaktionen'),
(215, 52, 'Impuls und Drehimpuls, Stoßprozesse'),
(216, 52, 'Energieerhaltung in geschlossenen Systemen'),
(217, 52, 'Hauptsätze der Thermodynamik'),
(218, 52, 'Kreisprozesse, Wirkungsgrad'),
(219, 53, 'Maxwell’sche Gleichungen, elektromagnetische Wellen'),
(220, 53, 'Wechselstrom, Induktion, Transformator'),
(221, 53, 'Quantenphysik: Photoeffekt, Wellen-Teilchen-Dualismus'),
(222, 53, 'Spezielle Relativitätstheorie, Zeitdilatation'),
(223, 53, 'Kern- und Teilchenphysik: Standardmodell (Grundzüge)'),
(224, 54, 'Bewegung: gleichförmig und beschleunigt'),
(225, 54, 'Kraftbegriff, Reibungskräfte'),
(226, 54, 'Einfache Maschinen: Hebel, Flaschenzug'),
(227, 55, 'Einfache Stromkreise: Quelle, Verbraucher, Leiter'),
(228, 55, 'Elektrische Größen: Spannung, Strom, Widerstand'),
(229, 55, 'Ohmsches Gesetz in Anwendungen'),
(230, 55, 'Magnetismus und Elektromagnet'),
(231, 56, 'Licht: Reflexion, Brechung, Spiegelbilder'),
(232, 56, 'Linsen und einfache optische Geräte'),
(233, 56, 'Schall: Ausbreitung, Tonhöhe, Lautstärke'),
(234, 57, 'Teilchenmodell: Aggregatzustände, Diffusion'),
(235, 57, 'Temperatur, Wärmeleitung, Wärmeströmung'),
(236, 57, 'Energieerhaltung und Wirkungsgrad'),
(237, 57, 'Atommodell, Radioaktivität, Anwendungen'),
(238, 58, 'Bewegungen beobachten: gleichförmig, beschleunigt'),
(239, 58, 'Kräfte erkennen: Schieben, Ziehen, Reibung'),
(240, 58, 'Einfache Maschinen: Hebel, Flaschenzug, schiefe Ebene'),
(241, 59, 'Stromkreis mit Batterie und Lampe'),
(242, 59, 'Spannung und Stromstärke messen'),
(243, 59, 'Ohmsches Gesetz in einfachen Beispielen'),
(244, 59, 'Magnete und elektromagnetische Anwendungen'),
(245, 60, 'Lichtstrahlen: Spiegel und Linsen'),
(246, 60, 'Sehen und optische Geräte im Alltag'),
(247, 60, 'Schall: Tonhöhe, Lautstärke, Ausbreitung'),
(248, 61, 'Teilchenmodell: Aggregatzustände und Diffusion'),
(249, 61, 'Temperatur und Wärmeleitung im Alltag'),
(250, 61, 'Energieerhaltung bei Wärmeprozessen'),
(251, 61, 'Einfache Atomvorstellungen, Radioaktivität im Alltag');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_std_lernthema_quelle`
--

CREATE TABLE `_std_lernthema_quelle` (
  `id` int(11) NOT NULL,
  `quelle` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_std_lernthema_quelle`
--

INSERT INTO `_std_lernthema_quelle` (`id`, `quelle`) VALUES
(1, 'Schülerserver'),
(2, 'Lehrplan Sachsen'),
(3, 'freie Quelle');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_std_schulform`
--

CREATE TABLE `_std_schulform` (
  `id` int(11) NOT NULL,
  `schulform` varchar(20) DEFAULT NULL,
  `beschreibung` varchar(255) DEFAULT NULL COMMENT 'Erweiterte Bezeichnung, z.B. Gymnasium G8, Oberschule mit Teilschwerpunkt etc.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_std_schulform`
--

INSERT INTO `_std_schulform` (`id`, `schulform`, `beschreibung`) VALUES
(1, 'GR', 'Grundschule'),
(2, 'GYM', 'Gymnasium'),
(3, 'RS', 'Realschule'),
(4, 'HoS', 'Hochschule'),
(5, 'OS', 'Oberschule'),
(6, 'UNI', 'Universität'),
(7, 'BS', 'Berufsschule'),
(8, 'frei', NULL),
(9, 'HS', 'Hauptschule');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_ue_fach`
--

CREATE TABLE `_ue_fach` (
  `id` int(11) NOT NULL,
  `fach` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `_ue_fach`
--

INSERT INTO `_ue_fach` (`id`, `fach`) VALUES
(1, 'MAT'),
(2, 'PHY');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `frzk_interdependenz`
--
ALTER TABLE `frzk_interdependenz`
  ADD PRIMARY KEY (`interdependenz_id`);

--
-- Indizes für die Tabelle `frzk_loops`
--
ALTER TABLE `frzk_loops`
  ADD PRIMARY KEY (`loop_id`);

--
-- Indizes für die Tabelle `frzk_operatoren`
--
ALTER TABLE `frzk_operatoren`
  ADD PRIMARY KEY (`operator_id`);

--
-- Indizes für die Tabelle `frzk_reflexion`
--
ALTER TABLE `frzk_reflexion`
  ADD PRIMARY KEY (`reflexion_id`);

--
-- Indizes für die Tabelle `frzk_semantische_dichte`
--
ALTER TABLE `frzk_semantische_dichte`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `frzk_setze_hub`
--
ALTER TABLE `frzk_setze_hub`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `frzk_transitions`
--
ALTER TABLE `frzk_transitions`
  ADD PRIMARY KEY (`transition_id`);

--
-- Indizes für die Tabelle `mtr_didaktik`
--
ALTER TABLE `mtr_didaktik`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_didaktik_themenauswahl` (`themenauswahl`),
  ADD KEY `fk_didaktik_methodenvielfalt` (`methodenvielfalt`),
  ADD KEY `fk_didaktik_individualisierung` (`individualisierung`),
  ADD KEY `fk_didaktik_aufforderung` (`aufforderung`),
  ADD KEY `fk_mtr_didaktik_materialien_id` (`materialien`),
  ADD KEY `fk_mtr_didaktik_zielgruppen` (`zielgruppen`),
  ADD KEY `ue_zuweisung_schüler_id` (`ue_zuweisung_teilnehmer_id`);

--
-- Indizes für die Tabelle `mtr_emotions`
--
ALTER TABLE `mtr_emotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ue_zuweisung_teilnehmer_id` (`ue_zuweisung_teilnehmer_id`);

--
-- Indizes für die Tabelle `mtr_leistung`
--
ALTER TABLE `mtr_leistung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_zuwTn_TnId` (`ue_zuweisung_teilnehmer_id`,`teilnehmer_id`),
  ADD KEY `ue_zuweisung_schüler_id` (`ue_zuweisung_teilnehmer_id`),
  ADD KEY `fk_mtr_leistung_lernfortschritt` (`lernfortschritt`),
  ADD KEY `fk_mtr_leistung_beherrscht_thema` (`beherrscht_thema`),
  ADD KEY `fk_mtr_leistung_transferdenken` (`transferdenken`),
  ADD KEY `fk_mtr_leistung_basiswissen` (`basiswissen`),
  ADD KEY `fk_mtr_leistung_vorbereitet` (`vorbereitet`);

--
-- Indizes für die Tabelle `mtr_persoenlichkeit`
--
ALTER TABLE `mtr_persoenlichkeit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offenheit_erfahrungen` (`offenheit_erfahrungen`),
  ADD KEY `gewissenhaftigkeit` (`gewissenhaftigkeit`),
  ADD KEY `Extraversion` (`Extraversion`),
  ADD KEY `vertraeglichkeit` (`vertraeglichkeit`),
  ADD KEY `lernfaehigkeit` (`lernfaehigkeit`),
  ADD KEY `soziale_interaktion` (`soziale_interaktion`),
  ADD KEY `metakognition` (`metakognition`),
  ADD KEY `stressbewaeltigung` (`stressbewaeltigung`),
  ADD KEY `bedeutungsbildung` (`bedeutungsbildung`),
  ADD KEY `teilnehmer_id` (`teilnehmer_id`);

--
-- Indizes für die Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_lesson`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ue_zuweisung_schueler_id` (`ue_unterrichtseinheit_id`),
  ADD KEY `idx_mitarbeit` (`mitarbeit`),
  ADD KEY `idx_absprachen` (`absprachen`),
  ADD KEY `idx_selbststaendigkeit` (`selbststaendigkeit`),
  ADD KEY `idx_konzentration` (`konzentration`),
  ADD KEY `idx_fleiss` (`fleiss`),
  ADD KEY `idx_lernfortschritt` (`lernfortschritt`),
  ADD KEY `idx_beherrscht_thema` (`beherrscht_thema`),
  ADD KEY `idx_transferdenken` (`transferdenken`),
  ADD KEY `idx_basiswissen` (`basiswissen`),
  ADD KEY `idx_vorbereitet` (`vorbereitet`),
  ADD KEY `idx_themenauswahl` (`themenauswahl`),
  ADD KEY `idx_materialien` (`materialien`),
  ADD KEY `idx_methodenvielfalt` (`methodenvielfalt`),
  ADD KEY `idx_individualisierung` (`individualisierung`),
  ADD KEY `idx_aufforderung` (`aufforderung`);

--
-- Indizes für die Tabelle `mtr_rueckkopplung_lehrkraft_tn`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_tn`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ue_zuweisung_schueler_id` (`ue_zuweisung_teilnehmer_id`),
  ADD KEY `idx_val_mitarbeit` (`val_mitarbeit`),
  ADD KEY `idx_val_absprachen` (`val_absprachen`),
  ADD KEY `idx_val_selbststaendigkeit` (`val_selbststaendigkeit`),
  ADD KEY `idx_val_konzentration` (`val_konzentration`),
  ADD KEY `idx_val_fleiss` (`val_fleiss`),
  ADD KEY `idx_val_lernfortschritt` (`val_lernfortschritt`),
  ADD KEY `idx_val_beherrscht_thema` (`val_beherrscht_thema`),
  ADD KEY `idx_val_transferdenken` (`val_transferdenken`),
  ADD KEY `idx_val_basiswissen` (`val_basiswissen`),
  ADD KEY `idx_val_vorbereitet` (`val_vorbereitet`),
  ADD KEY `idx_val_themenauswahl` (`val_themenauswahl`),
  ADD KEY `idx_val_materialien` (`val_materialien`),
  ADD KEY `idx_val_methodenvielfalt` (`val_methodenvielfalt`),
  ADD KEY `idx_val_individualisierung` (`val_individualisierung`),
  ADD KEY `idx_val_aufforderung` (`val_aufforderung`);

--
-- Indizes für die Tabelle `mtr_rueckkopplung_teilnehmer`
--
ALTER TABLE `mtr_rueckkopplung_teilnehmer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ue_zuweisung_schueler_id` (`teilnehmer_id`),
  ADD KEY `idx_mitarbeit` (`mitarbeit`),
  ADD KEY `idx_absprachen` (`absprachen`),
  ADD KEY `idx_selbststaendigkeit` (`selbststaendigkeit`),
  ADD KEY `idx_konzentration` (`konzentration`),
  ADD KEY `idx_fleiss` (`fleiss`),
  ADD KEY `idx_lernfortschritt` (`lernfortschritt`),
  ADD KEY `idx_beherrscht_thema` (`beherrscht_thema`),
  ADD KEY `idx_transferdenken` (`transferdenken`),
  ADD KEY `idx_basiswissen` (`basiswissen`),
  ADD KEY `idx_vorbereitet` (`vorbereitet`),
  ADD KEY `idx_themenauswahl` (`themenauswahl`),
  ADD KEY `idx_materialien` (`materialien`),
  ADD KEY `idx_methodenvielfalt` (`methodenvielfalt`),
  ADD KEY `idx_individualisierung` (`individualisierung`),
  ADD KEY `idx_aufforderung` (`aufforderung`),
  ADD KEY `gruppe_id` (`gruppe_id`),
  ADD KEY `einrichtung_id` (`einrichtung_id`),
  ADD KEY `idx_m_teiln_erfasst` (`teilnehmer_id`,`erfasst_am`);

--
-- Indizes für die Tabelle `mtr_sozial`
--
ALTER TABLE `mtr_sozial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mtr_sozial_ue_teilnehmer_zuweisung_FK` (`ue_zuweisung_teilnehmer_id`);

--
-- Indizes für die Tabelle `mtr_soziale_beziehungen`
--
ALTER TABLE `mtr_soziale_beziehungen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mtrsoziale_from` (`from_tn`),
  ADD KEY `fk_mtrsoziale_to` (`to_tn`);

--
-- Indizes für die Tabelle `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `unique_room` (`name`);

--
-- Indizes für die Tabelle `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `unique_session` (`session_date`,`start_time`,`room_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indizes für die Tabelle `session_students`
--
ALTER TABLE `session_students`
  ADD PRIMARY KEY (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indizes für die Tabelle `std_lehrkraft`
--
ALTER TABLE `std_lehrkraft`
  ADD PRIMARY KEY (`std_lehrkraft`);

--
-- Indizes für die Tabelle `std_lernthema`
--
ALTER TABLE `std_lernthema`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `std_teilnehmer`
--
ALTER TABLE `std_teilnehmer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_std_teilnehmer` (`Nachname`,`Vorname`,`Klassenstufe`),
  ADD KEY `std_teilnehmer__std_klassentyp_FK` (`KlassentypID`),
  ADD KEY `einrichtung_id` (`einrichtung_id`);

--
-- Indizes für die Tabelle `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `idx_name_students` (`first_name`,`last_name`),
  ADD UNIQUE KEY `unique_student` (`first_name`,`last_name`,`school_form`,`grade`),
  ADD KEY `first_name` (`first_name`);

--
-- Indizes für die Tabelle `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `unique_subject` (`code`);

--
-- Indizes für die Tabelle `tmp_teilnehmer`
--
ALTER TABLE `tmp_teilnehmer`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `tmp_unterrichtseinheiten`
--
ALTER TABLE `tmp_unterrichtseinheiten`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `ue_gruppen`
--
ALTER TABLE `ue_gruppen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tag` (`tag`);

--
-- Indizes für die Tabelle `ue_unterrichtseinheit`
--
ALTER TABLE `ue_unterrichtseinheit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gruppe_id` (`gruppe_id`);

--
-- Indizes für die Tabelle `ue_unterrichtseinheit_zw_thema`
--
ALTER TABLE `ue_unterrichtseinheit_zw_thema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unterrichtseinheit_id` (`ue_unterrichtseinheit_id`),
  ADD KEY `std_lernthema_id` (`std_lernthema_id`),
  ADD KEY `zieltyp_id` (`zieltyp_id`),
  ADD KEY `lernmethode_id` (`lernmethode_id`);

--
-- Indizes für die Tabelle `ue_zuweisung_teilnehmer`
--
ALTER TABLE `ue_zuweisung_teilnehmer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ue_zuweisung_lernthema_id` (`ue_unterrichtseinheit_zw_thema_id`),
  ADD KEY `teilnehmer_id` (`teilnehmer_id`),
  ADD KEY `idx_u_teiln_datum` (`teilnehmer_id`,`datum`);

--
-- Indizes für die Tabelle `verhaltens_mapping`
--
ALTER TABLE `verhaltens_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_frzk_hubs`
--
ALTER TABLE `_frzk_hubs`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_definition_lernmethode`
--
ALTER TABLE `_mtr_definition_lernmethode`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bezeichnung` (`bezeichnung`),
  ADD UNIQUE KEY `kurzbezeichnung` (`kurzbezeichnung`);

--
-- Indizes für die Tabelle `_mtr_definition_zieltyp`
--
ALTER TABLE `_mtr_definition_zieltyp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bezeichnung` (`bezeichnung`),
  ADD UNIQUE KEY `kurzbezeichnung` (`kurzbezeichnung`);

--
-- Indizes für die Tabelle `_mtr_einrichtung`
--
ALTER TABLE `_mtr_einrichtung`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_emotionen`
--
ALTER TABLE `_mtr_emotionen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emotion` (`emotion`),
  ADD KEY `idx_map_field` (`map_field`);

--
-- Indizes für die Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--
ALTER TABLE `_mtr_persoenlichkeitsmerkmal_definition`
  ADD PRIMARY KEY (`merkmal_id`),
  ADD UNIQUE KEY `merkmal_name` (`merkmal_name`);

--
-- Indizes für die Tabelle `_mtr_soziale_beziehung_type`
--
ALTER TABLE `_mtr_soziale_beziehung_type`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_soziale_beziehung_werte`
--
ALTER TABLE `_mtr_soziale_beziehung_werte`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indizes für die Tabelle `_std_lernthema_inhalt`
--
ALTER TABLE `_std_lernthema_inhalt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lernthema_id` (`std_lernthema_id`);

--
-- Indizes für die Tabelle `_std_lernthema_quelle`
--
ALTER TABLE `_std_lernthema_quelle`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_std_schulform`
--
ALTER TABLE `_std_schulform`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_ue_fach`
--
ALTER TABLE `_ue_fach`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `frzk_interdependenz`
--
ALTER TABLE `frzk_interdependenz`
  MODIFY `interdependenz_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_loops`
--
ALTER TABLE `frzk_loops`
  MODIFY `loop_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_operatoren`
--
ALTER TABLE `frzk_operatoren`
  MODIFY `operator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_reflexion`
--
ALTER TABLE `frzk_reflexion`
  MODIFY `reflexion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_semantische_dichte`
--
ALTER TABLE `frzk_semantische_dichte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_setze_hub`
--
ALTER TABLE `frzk_setze_hub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `frzk_transitions`
--
ALTER TABLE `frzk_transitions`
  MODIFY `transition_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `mtr_didaktik`
--
ALTER TABLE `mtr_didaktik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT für Tabelle `mtr_emotions`
--
ALTER TABLE `mtr_emotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT für Tabelle `mtr_leistung`
--
ALTER TABLE `mtr_leistung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT für Tabelle `mtr_persoenlichkeit`
--
ALTER TABLE `mtr_persoenlichkeit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT für Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_lesson`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `mtr_rueckkopplung_teilnehmer`
--
ALTER TABLE `mtr_rueckkopplung_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'neu', AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT für Tabelle `mtr_sozial`
--
ALTER TABLE `mtr_sozial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `mtr_soziale_beziehungen`
--
ALTER TABLE `mtr_soziale_beziehungen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2018;

--
-- AUTO_INCREMENT für Tabelle `std_lehrkraft`
--
ALTER TABLE `std_lehrkraft`
  MODIFY `std_lehrkraft` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `std_lernthema`
--
ALTER TABLE `std_lernthema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT für Tabelle `std_teilnehmer`
--
ALTER TABLE `std_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT für Tabelle `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT für Tabelle `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `tmp_teilnehmer`
--
ALTER TABLE `tmp_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tmp_unterrichtseinheiten`
--
ALTER TABLE `tmp_unterrichtseinheiten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `ue_gruppen`
--
ALTER TABLE `ue_gruppen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT für Tabelle `ue_unterrichtseinheit`
--
ALTER TABLE `ue_unterrichtseinheit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT für Tabelle `ue_unterrichtseinheit_zw_thema`
--
ALTER TABLE `ue_unterrichtseinheit_zw_thema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT für Tabelle `ue_zuweisung_teilnehmer`
--
ALTER TABLE `ue_zuweisung_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT für Tabelle `verhaltens_mapping`
--
ALTER TABLE `verhaltens_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `_frzk_hubs`
--
ALTER TABLE `_frzk_hubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT für Tabelle `_mtr_definition_lernmethode`
--
ALTER TABLE `_mtr_definition_lernmethode`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT für Tabelle `_mtr_definition_zieltyp`
--
ALTER TABLE `_mtr_definition_zieltyp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT für Tabelle `_mtr_einrichtung`
--
ALTER TABLE `_mtr_einrichtung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `_mtr_emotionen`
--
ALTER TABLE `_mtr_emotionen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--
ALTER TABLE `_mtr_persoenlichkeitsmerkmal_definition`
  MODIFY `merkmal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT für Tabelle `_mtr_soziale_beziehung_type`
--
ALTER TABLE `_mtr_soziale_beziehung_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `_mtr_soziale_beziehung_werte`
--
ALTER TABLE `_mtr_soziale_beziehung_werte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT für Tabelle `_std_lernthema_inhalt`
--
ALTER TABLE `_std_lernthema_inhalt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=252;

--
-- AUTO_INCREMENT für Tabelle `_std_lernthema_quelle`
--
ALTER TABLE `_std_lernthema_quelle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `_std_schulform`
--
ALTER TABLE `_std_schulform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `_ue_fach`
--
ALTER TABLE `_ue_fach`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `mtr_didaktik`
--
ALTER TABLE `mtr_didaktik`
  ADD CONSTRAINT `fk_mtr_didaktik_uezt` FOREIGN KEY (`ue_zuweisung_teilnehmer_id`) REFERENCES `ue_zuweisung_teilnehmer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `mtr_leistung`
--
ALTER TABLE `mtr_leistung`
  ADD CONSTRAINT `fk_leistung_zuweisung` FOREIGN KEY (`ue_zuweisung_teilnehmer_id`) REFERENCES `ue_zuweisung_teilnehmer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `mtr_persoenlichkeit`
--
ALTER TABLE `mtr_persoenlichkeit`
  ADD CONSTRAINT `fk_persoenlichkeit_teilnehmer` FOREIGN KEY (`teilnehmer_id`) REFERENCES `std_teilnehmer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_lesson`
  ADD CONSTRAINT `fk_rkl_unterrichtseinheit` FOREIGN KEY (`ue_unterrichtseinheit_id`) REFERENCES `ue_unterrichtseinheit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `mtr_rueckkopplung_lehrkraft_tn`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_tn`
  ADD CONSTRAINT `fk_rkltn_zuweisung` FOREIGN KEY (`ue_zuweisung_teilnehmer_id`) REFERENCES `ue_zuweisung_teilnehmer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `ue_zuweisung_teilnehmer`
--
ALTER TABLE `ue_zuweisung_teilnehmer`
  ADD CONSTRAINT `fk_zuweisung_zw_thema` FOREIGN KEY (`ue_unterrichtseinheit_zw_thema_id`) REFERENCES `ue_unterrichtseinheit_zw_thema` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `_mtr_soziale_beziehung_werte`
--
ALTER TABLE `_mtr_soziale_beziehung_werte`
  ADD CONSTRAINT `_mtr_soziale_beziehung_werte_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `_mtr_soziale_beziehung_type` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
