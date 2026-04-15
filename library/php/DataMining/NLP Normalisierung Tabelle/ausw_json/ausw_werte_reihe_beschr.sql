-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 26. Mrz 2026 um 09:34
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
DROP TABLE `ausw_werte_reihe_beschr`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ausw_werte_reihe_beschr`
--

CREATE TABLE `ausw_werte_reihe_beschr` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `beschreibung` varchar(200) NOT NULL,
  `handler` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `ausw_werte_reihe_beschr`
--

INSERT INTO `ausw_werte_reihe_beschr` (`id`, `type`, `beschreibung`, `handler`) VALUES
(1, 1, 'Vektorberechnung', ''),
(2, 0, 'ChatGPT ohne Anmeldung', ''),
(3, 0, 'ChatGPT ohne Anmeldung', ''),
(4, 0, 'ChatGPT ohne Anmeldung', ''),
(5, 2, 'ChatGPT thomas.mahler 25.03.26', ''),
(6, 2, 'ChatGPT thiele.olaf 25.03.26', ''),
(7, 2, 'ChatGPT thiele.nachhilfe 25.03.26', ''),
(8, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell', ''),
(9, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als Pädagoge', 'Pädagoge'),
(10, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als Schüler', 'Schüler'),
(11, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als Eltern', 'Eltern'),
(12, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als neutrale Person', 'neutrale Person'),
(13, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als „Operator“ → bewertet nur funktionale Übergänge', 'Operator'),
(14, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als „Kohärenzprüfer“ → misst Stabilität (ΔK)', 'Kohärenzprüfer'),
(15, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als „Emergenz-Beobachter“ → erkennt neue Strukturen', 'Emergenz-Beobachter'),
(16, 3, 'ChatGPT thiele.olaf 26.03.26 mit neuem Modell als „Hub-Detektor“ → erkennt Orientierungspunkte hintereinander neu', 'Hub-Detektor');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `ausw_werte_reihe_beschr`
--
ALTER TABLE `ausw_werte_reihe_beschr`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `ausw_werte_reihe_beschr`
--
ALTER TABLE `ausw_werte_reihe_beschr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
