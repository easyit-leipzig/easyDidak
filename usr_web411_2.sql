-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 10. Sep 2025 um 18:51
-- Server-Version: 5.7.25
-- PHP-Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `usr_web411_2`
--

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
  `bemerkung` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `map_tmp_ue`
--

CREATE TABLE `map_tmp_ue` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `behavior_phrase` varchar(255) NOT NULL,
  `mapped_value` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `map_tmp_ue`
--

INSERT INTO `map_tmp_ue` (`id`, `category`, `behavior_phrase`, `mapped_value`) VALUES
(1, 'Sozialverhalten', 'Absprachen einhaltend', 1),
(2, 'Sozialverhalten', 'Absprachen nicht einhaltend', 6),
(3, 'Sozialverhalten', 'beteiligt sich / gute Mitarbeit', 1),
(4, 'Sozialverhalten', 'störend / blockierend / resignierend', 6),
(5, 'Sozialverhalten', 'fleißig / bemüht', 1),
(6, 'Sozialverhalten', 'desinteressiert / gleichgültig', 6),
(7, 'Lernverhalten', 'arbeitet selbstständig', 1),
(8, 'Lernverhalten', 'benötigt Aufforderung', 6),
(9, 'Lernverhalten', 'konzentriert', 1),
(10, 'Lernverhalten', 'unkonzentriert', 6),
(11, 'Lernverhalten', 'vorbereitet', 1),
(12, 'Lernverhalten', 'Materialien fehlen', 6),
(13, 'Sozialverhalten', 'arbeitet selbstständig', 1),
(14, 'Sozialverhalten', 'benötigt Aufforderung', 6),
(15, 'Sozialverhalten', 'konzentriert', 1),
(16, 'Sozialverhalten', 'unkonzentriert', 6),
(17, 'Sozialverhalten', 'vorbereitet', 1),
(18, 'Sozialverhalten', 'Materialien fehlen', 6);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_didaktik`
--

CREATE TABLE `mtr_didaktik` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `themenauswahl` int(1) DEFAULT NULL,
  `methodenvielfalt` int(1) DEFAULT NULL,
  `individualisierung` int(1) DEFAULT NULL,
  `aufforderung` int(1) DEFAULT NULL,
  `materialien` int(1) DEFAULT NULL,
  `zielgruppen` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_emotions`
--

CREATE TABLE `mtr_emotions` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `emotions` varchar(50) NOT NULL,
  `freude` tinyint(1) DEFAULT '0' COMMENT 'Freude: Ein Zustand des Wohlbefindens und der Zufriedenheit.',
  `traurigkeit` tinyint(1) DEFAULT '0' COMMENT 'Traurigkeit: Ein Gefühl des Verlusts oder der Enttäuschung.',
  `wut` tinyint(1) DEFAULT '0' COMMENT 'Wut: Eine starke feindselige Reaktion auf eine Provokation oder Ungerechtigkeit.',
  `angst` tinyint(1) DEFAULT '0' COMMENT 'Angst: Eine Reaktion auf wahrgenommene Gefahr oder Bedrohung.',
  `ueberraschung` tinyint(1) DEFAULT '0' COMMENT 'Überraschung: Eine kurze Reaktion auf etwas Unerwartetes.',
  `verachtung` tinyint(1) DEFAULT '0' COMMENT 'Verachtung: Ein Gefühl der Geringschätzung oder Herablassung.',
  `interesse` tinyint(1) DEFAULT '0' COMMENT 'Interesse: Eine Haltung der Aufmerksamkeit oder Neugier.',
  `schuld` tinyint(1) DEFAULT '0' COMMENT 'Schuld: Ein Gefühl der Verantwortung oder des Bedauerns über eine tatsächliche oder eingebildete Verfehlung.',
  `neid` tinyint(1) DEFAULT '0' COMMENT 'Neid: Ein Gefühl des Grolls oder der Unzufriedenheit, hervorgerufen durch das Bewusstsein eines Vorteils, den andere besitzen.',
  `stolz` tinyint(1) DEFAULT '0' COMMENT 'Stolz: Ein Gefühl tiefer Zufriedenheit aus den eigenen Leistungen oder denen einer Gruppe.',
  `erleichterung` tinyint(1) DEFAULT '0' COMMENT 'Erleichterung: Ein Zustand des Friedens, der durch die Beseitigung von Angst oder Schmerz hervorgerufen wird.',
  `hoffnung` tinyint(1) DEFAULT '0' COMMENT 'Hoffnung: Ein Gefühl der Erwartung und des Wunsches nach positiven Ergebnissen.',
  `liebe` tinyint(1) DEFAULT '0' COMMENT 'Liebe: Ein intensives Gefühl tiefer Zuneigung.',
  `eifersucht` tinyint(1) DEFAULT '0' COMMENT 'Eifersucht: Ein komplexes Gefühl, das Angst vor Verlust und Misstrauen umfasst.',
  `zufriedenheit` tinyint(1) DEFAULT '0',
  `selbstvertrauen` tinyint(1) DEFAULT '0',
  `frustration` tinyint(1) DEFAULT '0',
  `erfuellung` tinyint(1) DEFAULT '0',
  `motivation` tinyint(1) DEFAULT '0',
  `dankbarkeit` tinyint(1) DEFAULT '0',
  `ueberforderung` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_leistung`
--

CREATE TABLE `mtr_leistung` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilnehmer_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `lernfortschritt` int(1) DEFAULT NULL,
  `beherrscht_thema` int(1) DEFAULT NULL,
  `transferdenken` int(1) DEFAULT NULL,
  `basiswissen` int(1) DEFAULT NULL,
  `vorbereitet` int(1) DEFAULT NULL,
  `verhaltensbeurteilung_code` varchar(255) DEFAULT NULL COMMENT 'Zusätzlicher Code zur Bewertung z.B. Chips-Auswahl',
  `reflexionshinweis` text COMMENT 'Freitext für didaktische oder diagnostische Reflexion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_persoenlichkeit`
--

CREATE TABLE `mtr_persoenlichkeit` (
  `id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `offenheit_erfahrungen` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Sucht aktiv nach neuen "Feldzuständen" (Lerninhalten, Situationen), experimentiert mit verschiedenen "Akteur-Funktionen" (Lernstrategien, Verhaltensweisen), ist offen für die Transformation symbolischer Meta-Strukturen.\r\nNiedrige Ausprägung: Bevorzugt bekannte "Feldzustände", vermeidet neue Verhaltensmuster, hält an etablierten symbolischen Ordnungen fest.',
  `gewissenhaftigkeit` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Richtet seine "Akteur-Funktion" auf die systematische Verfolgung definierter Ziele aus, zeigt Ausdauer bei der Bearbeitung von Aufgaben im "Feld", reguliert seine "Meta-Funktion" zur Selbstüberwachung und -korrektur im Lernprozess.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Zielverfolgung, geringe Ausdauer, impulsive oder wenig geplante "Akteur-Funktionen".',
  `Extraversion` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Zeigt eine starke Tendenz zur Bildung gekoppelter "Akteur-Funktionen" in sozialen Feldern, sucht aktiv soziale "Feldzustände" auf, zeigt eine hohe "Handlungsdichte" im sozialen Kontext.\r\nNiedrige Ausprägung: Geringere Tendenz zu gekoppelten "Akteur-Funktionen", vermeidet soziale "Feldzustände", zeigt weniger "Handlungen" im sozialen Kontext.',
  `vertraeglichkeit` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Passt seine "Akteur-Funktion" an die der Ko-Akteure in sozialen Feldern an, zeigt Kooperationsbereitschaft, ist empfänglich für die semantischen Attraktoren gemeinsamer sozialer Narrative.\r\nNiedrige Ausprägung: Zeigt weniger Anpassung, ist weniger kooperativ, neigt zu Konflikten in gekoppelten "Akteur-Funktionen".',
  `zielorientierung` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Setzt sich aktiv Lernziele, verfolgt Aufgaben beharrlich, zeigt Eigeninitiative.\r\nNiedrige Ausprägung: Schwierigkeiten, Ziele zu formulieren oder zu verfolgen, geringe Eigenmotivation.',
  `lernfaehigkeit` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler.\r\n\r\nLernprozesse verändern die Struktur der Akteur-Funktionen. Ausprägungen zeigen sich in der Geschwindigkeit und Effizienz dieser funktionalen Anpassungen [citation: 6, 8].',
  `anpassungsfaehigkeit` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler.',
  `soziale_interaktion` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Kooperativ, kommuniziert offen, integriert sich gut in Gruppen, zeigt Empathie.\r\nNiedrige Ausprägung: Schwierigkeiten in der Zusammenarbeit, vermeidet Interaktion, soziale Konflikte.',
  `metakognition` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Denkt über eigenes Lernen nach, plant Lernprozesse, überwacht Verständnis, bewertet eigene Leistung realistisch.\r\nNiedrige Ausprägung: Wenig Bewusstsein für eigene Lernprozesse, Schwierigkeiten bei der Selbstbewertung.',
  `stressbewaeltigung` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Reagiert ängstlich auf Prüfungen oder neue Situationen, ist besorgt über Leistung, zeigt emotionale Labilität.\r\nNiedrige Ausprägung: Bleibt ruhig unter Druck, geht gelassen mit Unsicherheit um.\r\n\r\nKönnte sich in der erhöhten Reaktivität der Akteur-Funktion auf als bedrohlich interpretierte Feldzustände (z.B. Prüfungsdruck) oder in negativen Mustern der Meta-Funktion (z.B. negative Selbstbewertung) äußern [citation: 6, 7, 9, 11].',
  `bedeutungsbildung` int(1) DEFAULT NULL COMMENT 'Hohe Ausprägung: Konstruiert kohärente Bedeutungen aus Lerninhalten, vernetzt Wissen, entwickelt eigene Interpretationen, findet Sinn im Gelernten.\r\nNiedrige Ausprägung: Schwierigkeiten bei der Sinnstiftung, isolierte Wissensfragmente, wenig eigene Interpretationen.\r\n\r\nIntegrale Funktionalität (Kohärenzbildung, Kontextualisierung, Narrativierung, Wertschöpfung) synthetisiert lokale Beobachtungen zu globalen Bedeutungen. Ausprägungen zeigen sich in der Qualität und Struktur der konstruierten semantischen Felder und Narrative [citation: 1, 16, 11].'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Offenheit für Erfahrungen, Gewissenhaftigkeit, Extraversion,';

--
-- Daten für Tabelle `mtr_persoenlichkeit`
--

INSERT INTO `mtr_persoenlichkeit` (`id`, `teilnehmer_id`, `datum`, `offenheit_erfahrungen`, `gewissenhaftigkeit`, `Extraversion`, `vertraeglichkeit`, `zielorientierung`, `lernfaehigkeit`, `anpassungsfaehigkeit`, `soziale_interaktion`, `metakognition`, `stressbewaeltigung`, `bedeutungsbildung`) VALUES
(1, 2, '2025-09-08', 2, 3, 3, 3, 2, 2, 3, 3, 3, 3, 3),
(2, 3, '2025-09-09', 3, 3, 3, 2, 2, 2, 3, 2, 3, NULL, NULL),
(3, 4, '2025-09-09', 4, 4, 3, 3, 4, 4, 4, 4, 4, NULL, NULL),
(4, 5, '2025-09-09', 2, 2, 2, 2, 2, 3, 3, 2, 3, 3, 0),
(5, 6, '2025-09-09', 3, 2, 4, 3, 4, 4, 3, 4, 4, NULL, NULL),
(6, 7, '2025-09-09', 4, 3, 4, 4, 4, 4, 4, 4, 3, NULL, NULL),
(7, 8, '2025-09-09', 3, 3, 3, 3, 3, 3, 4, 3, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--

CREATE TABLE `mtr_rueckkopplung_lehrkraft_lesson` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_id` int(11) DEFAULT NULL,
  `erfasst_am` datetime DEFAULT CURRENT_TIMESTAMP,
  `mitarbeit` int(11) DEFAULT NULL,
  `absprachen` int(11) DEFAULT NULL,
  `selbststaendigkeit` int(11) DEFAULT NULL,
  `konzentration` int(11) DEFAULT NULL,
  `fleiss` int(11) DEFAULT NULL,
  `lernfortschritt` int(11) DEFAULT NULL,
  `beherrscht_thema` int(11) DEFAULT NULL,
  `transferdenken` int(11) DEFAULT NULL,
  `basiswissen` int(11) DEFAULT NULL,
  `vorbereitet` int(11) DEFAULT NULL,
  `themenauswahl` int(11) DEFAULT NULL,
  `materialien` int(11) DEFAULT NULL,
  `methodenvielfalt` int(11) DEFAULT NULL,
  `individualisierung` int(11) DEFAULT NULL,
  `aufforderung` int(11) DEFAULT NULL,
  `emotions` varchar(100) NOT NULL,
  `bemerkungen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_lehrkraft_tn`
--

CREATE TABLE `mtr_rueckkopplung_lehrkraft_tn` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_id` int(11) DEFAULT NULL,
  `erfasst_am` datetime DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_rueckkopplung_teilnehmer`
--

CREATE TABLE `mtr_rueckkopplung_teilnehmer` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_schueler_id` int(11) DEFAULT NULL,
  `gruppe_id` tinyint(4) NOT NULL,
  `erfasst_am` datetime DEFAULT CURRENT_TIMESTAMP,
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
  `bemerkungen` varchar(255) NOT NULL,
  `val_emotions_new` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `mtr_rueckkopplung_teilnehmer`
--

INSERT INTO `mtr_rueckkopplung_teilnehmer` (`id`, `ue_zuweisung_schueler_id`, `gruppe_id`, `erfasst_am`, `val_mitarbeit`, `val_absprachen`, `val_selbststaendigkeit`, `val_konzentration`, `val_fleiss`, `val_lernfortschritt`, `val_beherrscht_thema`, `val_transferdenken`, `val_basiswissen`, `val_vorbereitet`, `val_themenauswahl`, `val_materialien`, `val_methodenvielfalt`, `val_individualisierung`, `val_aufforderung`, `val_emotions`, `bemerkungen`, `val_emotions_new`) VALUES
(1, 21, 2, '2025-09-08 18:29:34', 3, 1, 1, 2, 2, 2, 2, 1, 2, 3, 1, 1, 1, 1, 1, '3,28,1', '', NULL),
(2, 12, 2, '2025-09-08 18:30:22', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '1', '', NULL),
(3, 20, 2, '2025-09-08 18:32:37', 2, 2, 2, 2, 2, 1, 2, 2, 2, 1, 1, 1, 1, 1, 1, '6,23,2', '', NULL),
(4, 16, 2, '2025-09-08 18:32:52', 2, 1, 4, 2, 1, 2, 2, 2, 3, 1, 1, 1, 1, 2, 2, '23', '', NULL),
(5, 3, 3, '2025-09-09 17:01:01', 1, 1, 2, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, '6,25,24,2', '', NULL),
(6, 9, 4, '2025-09-09 18:29:48', 1, 2, 1, 1, 5, 3, 3, 1, 3, 3, 2, 2, 1, 1, 1, '22', '', NULL),
(7, 16, 4, '2025-09-09 18:33:49', 2, 3, 5, 3, 2, 2, 2, 2, 2, 2, 1, 3, 1, 2, 1, '22', '', NULL),
(8, 8, 4, '2025-09-09 18:34:03', 3, 2, 3, 3, 3, 3, 4, 2, 3, 2, 2, 2, 3, 3, 3, '', '', NULL),
(9, 5, 4, '2025-09-09 18:37:51', 2, 2, 0, 0, 2, 3, 0, 2, 3, 1, 2, 2, 2, 0, 2, '22,1,15,6,23,4,25,20', '', NULL),
(10, 11, 5, '2025-09-10 16:58:02', 2, 1, 2, 2, 1, 2, 2, 2, 2, 1, 2, 2, 1, 2, 2, '1,13,2', '', NULL),
(11, 6, 5, '2025-09-10 16:58:08', 2, 3, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, '3,28,1', '', NULL),
(12, 7, 6, '2025-09-10 18:29:44', 3, 1, 4, 4, 2, 2, 3, 3, 1, 1, 1, 1, 1, 2, 2, '22,4,25', '', NULL),
(13, 14, 6, '2025-09-10 18:32:02', 1, 2, 2, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 1, 1, '15,25,20', '', NULL),
(14, 12, 6, '2025-09-10 18:32:50', 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, '', '', NULL),
(15, 12, 6, '2025-09-10 18:32:51', 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 1, 1, 1, 1, 1, '', '', NULL),
(16, 22, 6, '2025-09-10 18:33:32', 2, 2, 2, 1, 2, 3, 4, 3, 3, 2, 1, 1, 1, 1, 1, '5,4,7,25', '', NULL),
(17, 13, 6, '2025-09-10 18:34:26', 2, 2, 2, 2, 1, 2, 2, 3, 2, 2, 2, 2, 2, 2, 2, '', '', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mtr_sozial`
--

CREATE TABLE `mtr_sozial` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_teilehmer_id` int(11) NOT NULL,
  `mitarbeit` int(11) DEFAULT NULL,
  `absprachen` int(11) DEFAULT NULL,
  `selbststaendigkeit` int(11) DEFAULT NULL,
  `konzentration` int(11) DEFAULT NULL,
  `fleiss` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE latin1_german1_ci NOT NULL
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
(9, '2025-09-05', '15:35:00', '17:05:00', 1, 1);

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
(9, 21);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `std_lernthema`
--

CREATE TABLE `std_lernthema` (
  `id` int(11) NOT NULL,
  `quelle_id` int(11) NOT NULL,
  `fach_id` int(11) NOT NULL DEFAULT '1',
  `klassenstufe` int(11) DEFAULT NULL,
  `schulform` varchar(20) DEFAULT NULL,
  `lernthema` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `teilnehmer_typ` int(1) NOT NULL DEFAULT '1' COMMENT '0 - Lehrkraft / 1 - Teilnehmer',
  `Vorname` varchar(255) DEFAULT NULL,
  `Nachname` varchar(255) DEFAULT NULL,
  `geschlecht` char(1) NOT NULL,
  `geburtstag` date NOT NULL,
  `Nachstunde` tinyint(1) DEFAULT NULL,
  `Klassenstufe` int(11) DEFAULT NULL,
  `KlassentypID` int(11) DEFAULT NULL,
  `Bis` date DEFAULT NULL,
  `GruppenID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `std_teilnehmer`
--

INSERT INTO `std_teilnehmer` (`id`, `teilnehmer_typ`, `Vorname`, `Nachname`, `geschlecht`, `geburtstag`, `Nachstunde`, `Klassenstufe`, `KlassentypID`, `Bis`, `GruppenID`) VALUES
(1, 0, 'Olaf', 'Thiele', '1', '0000-00-00', NULL, 23, 4, NULL, NULL),
(2, 1, 'Lukas', 'Hilpert', '1', '0000-00-00', NULL, 9, 3, NULL, NULL),
(3, 1, 'Sarah', 'Kahle', '2', '0000-00-00', NULL, 6, 2, NULL, NULL),
(4, 1, 'Simos', 'Giannakidis', '1', '0000-00-00', NULL, 10, 2, NULL, NULL),
(5, 1, 'Paula', 'Juraschek', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(6, 1, 'Karl', 'König', '1', '0000-00-00', NULL, 7, 2, NULL, NULL),
(7, 1, 'Carlotta', 'Körber', '2', '0000-00-00', NULL, 9, 2, NULL, NULL),
(8, 1, 'Selina', 'Möwes', '2', '0000-00-00', NULL, 9, 3, NULL, NULL),
(9, 1, 'Lia', 'Schubert', '2', '0000-00-00', NULL, 8, 2, NULL, NULL),
(10, 1, 'Noah', 'Freiberg', '1', '0000-00-00', NULL, 12, 2, NULL, NULL),
(11, 1, 'Zoey', 'Schönherr', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(12, 1, 'Felix', 'Schölzel', '1', '0000-00-00', NULL, 8, 2, NULL, NULL),
(13, 1, 'Juni', 'Schölzel', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(14, 1, 'Jalia', 'Wagner', '2', '0000-00-00', NULL, 19, 2, NULL, NULL),
(15, 1, 'Ida', 'Johnson', '2', '0000-00-00', NULL, 9, 2, NULL, NULL),
(16, 1, 'Anna-Sophie', 'Canitz', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(17, 1, 'Maja', 'Deutlich', '2', '0000-00-00', NULL, 7, 3, NULL, NULL),
(18, 1, 'Louis', 'Grobe', '1', '0000-00-00', NULL, 9, 2, NULL, NULL),
(19, 1, 'Pia', 'Ponader', '2', '0000-00-00', NULL, 7, 2, NULL, NULL),
(20, 1, 'Luise', 'Schaff', '2', '0000-00-00', NULL, 10, 2, NULL, NULL),
(21, 1, 'Gustav', 'Fleischer', '1', '0000-00-00', NULL, 11, 2, NULL, NULL),
(22, 1, 'Maruschka', 'Gottschlich', '2', '0000-00-00', NULL, 11, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(50) COLLATE latin1_german1_ci NOT NULL,
  `last_name` varchar(50) COLLATE latin1_german1_ci NOT NULL,
  `school_form` varchar(10) COLLATE latin1_german1_ci DEFAULT NULL,
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
(32, 'Felix', 'Scheithauer', 'GYM', 8, '2025-09-30');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `code` varchar(10) COLLATE latin1_german1_ci NOT NULL,
  `name` varchar(50) COLLATE latin1_german1_ci DEFAULT NULL
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
  `status` text,
  `besondere_hinweise` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `freitext` text,
  `desinteressiert_gleichgueltig` int(1) DEFAULT NULL,
  `unkonzentriert` int(1) DEFAULT NULL,
  `unverstaendnis` int(1) DEFAULT NULL,
  `benoetigt_aufforderung` int(1) DEFAULT NULL,
  `stoerend_blockierend_resignierend` int(1) DEFAULT NULL,
  `absprachen_nicht_einhaltend` int(1) DEFAULT NULL,
  `materialien_fehlen` int(1) DEFAULT NULL,
  `unpuenktlich` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `kommentar` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
(8, 6, 'Do.', '17:10:00', '18:40:00', 'PHY', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 30.09.25'),
(9, 6, 'Fr.', '15:35:00', '17:05:00', 'MAT', 'Rm. 3', 'SH Leipzig-Lausen', 'bis 29.08.25'),
(10, 3, '', '07:15:00', '08:50:00', '', 'dummy', NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_teilnehmer_zuweisung`
--

CREATE TABLE `ue_teilnehmer_zuweisung` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_thema_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `ue_teilnehmer_zuweisung`
--

INSERT INTO `ue_teilnehmer_zuweisung` (`id`, `ue_unterrichtseinheit_thema_id`, `teilnehmer_id`) VALUES
(1, 1, 20),
(2, 2, 20);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_unterrichtseinheit`
--

CREATE TABLE `ue_unterrichtseinheit` (
  `id` int(11) NOT NULL,
  `gruppe_id` int(11) DEFAULT NULL,
  `datum` date DEFAULT NULL,
  `zeit` time DEFAULT NULL,
  `dauer` int(2) NOT NULL DEFAULT '90',
  `beschreibung` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `ue_unterrichtseinheit`
--

INSERT INTO `ue_unterrichtseinheit` (`id`, `gruppe_id`, `datum`, `zeit`, `dauer`, `beschreibung`) VALUES
(1, 1, NULL, NULL, 90, 'test'),
(2, 3, '2025-09-09', '15:35:00', 90, 'Gruppenveranstaltung'),
(3, 4, '2025-09-09', '17:10:00', 90, 'Gruppenveranstaltung'),
(4, 5, '2025-09-10', '15:35:00', 90, 'Gruppenveranstaltung'),
(5, 6, '2025-09-10', '17:10:00', 90, 'Gruppenveranstaltung');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_unterrichtseinheit_zw_thema`
--

CREATE TABLE `ue_unterrichtseinheit_zw_thema` (
  `id` int(11) NOT NULL,
  `ue_unterrichtseinheit_id` int(11) NOT NULL,
  `schulform_id` int(11) NOT NULL,
  `fach_id` int(11) NOT NULL DEFAULT '1',
  `zieltyp_id` int(11) NOT NULL,
  `lernmethode_id` int(11) NOT NULL,
  `std_lernthema_id` varchar(100) NOT NULL,
  `thema` varchar(255) NOT NULL,
  `dauer` int(11) NOT NULL,
  `teilnehmer_id` varchar(100) NOT NULL,
  `beschreibung` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `ue_unterrichtseinheit_zw_thema`
--

INSERT INTO `ue_unterrichtseinheit_zw_thema` (`id`, `ue_unterrichtseinheit_id`, `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `dauer`, `teilnehmer_id`, `beschreibung`) VALUES
(1, 1, 2, 1, 3, 24, 'Vorbereitung BLF', 'Teil A', 15, '20', ''),
(2, 1, 2, 1, 3, 24, 'Geometrie', 'Flächenberechnung', 30, '', ''),
(3, 2, 0, 1, 1, 24, '', '', 0, '3', 'Gruppe 3'),
(4, 3, 0, 1, 1, 24, '', '', 15, '7,9,5,8', 'Gruppe 4'),
(5, 4, 0, 1, 1, 24, '', '', 0, '11,6', 'Gruppe 5'),
(6, 5, 0, 1, 1, 24, '', '', 0, '7,14,12,12,22,13', 'Gruppe 6');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ue_zuweisung_teilnehmer`
--

CREATE TABLE `ue_zuweisung_teilnehmer` (
  `id` int(11) NOT NULL,
  `ue_zuweisung_lernthema_id` int(11) NOT NULL,
  `teilnehmer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `ue_zuweisung_teilnehmer`
--

INSERT INTO `ue_zuweisung_teilnehmer` (`id`, `ue_zuweisung_lernthema_id`, `teilnehmer_id`) VALUES
(1, 1, 20),
(2, 3, 3),
(3, 4, 9),
(4, 4, 7),
(5, 4, 8),
(6, 4, 5);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `verhaltens_mapping`
--

CREATE TABLE `verhaltens_mapping` (
  `id` int(11) NOT NULL,
  `flag_typ` int(11) NOT NULL,
  `flag_text` varchar(255) NOT NULL,
  `spaltenname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Tabellenstruktur für Tabelle `_mtr_definition_lernmethode`
--

CREATE TABLE `_mtr_definition_lernmethode` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beschreibung` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategorie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baut_auf_auf` int(11) DEFAULT NULL,
  `kurzbezeichnung` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `schwerpunkt` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
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
  `bezeichnung` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beschreibung` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `baut_auf_auf` int(11) DEFAULT NULL,
  `kurzbezeichnung` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ebene` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
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
-- Tabellenstruktur für Tabelle `_mtr_emotionen`
--

CREATE TABLE `_mtr_emotionen` (
  `id` int(11) NOT NULL,
  `show_emotion` tinyint(1) NOT NULL DEFAULT '1',
  `type_id` tinyint(4) NOT NULL COMMENT '1=positiv, 2=negativ, 3=kognitiv',
  `emotion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `_mtr_emotionen`
--

INSERT INTO `_mtr_emotionen` (`id`, `show_emotion`, `type_id`, `emotion`) VALUES
(1, 1, 1, 'Freude'),
(2, 1, 1, 'Zufriedenheit'),
(3, 1, 1, 'Erfüllung'),
(4, 1, 1, 'Motivation'),
(5, 1, 1, 'Dankbarkeit'),
(6, 1, 1, 'Hoffnung'),
(7, 1, 1, 'Stolz'),
(8, 1, 1, 'Selbstvertrauen'),
(9, 1, 1, 'Neugier'),
(10, 1, 1, 'Inspiration'),
(11, 1, 1, 'Zugehörigkeit'),
(12, 1, 1, 'Vertrauen'),
(13, 1, 1, 'Spaß'),
(14, 1, 1, 'Sicherheit'),
(15, 1, 2, 'Frustration'),
(16, 1, 2, 'Überforderung'),
(17, 1, 2, 'Angst'),
(18, 1, 2, 'Langeweile'),
(19, 1, 2, 'Scham'),
(20, 1, 2, 'Zweifel'),
(21, 1, 2, 'Resignation'),
(22, 1, 2, 'Erschöpfung'),
(23, 1, 3, 'Interesse'),
(24, 1, 3, 'Verwirrung'),
(25, 1, 3, 'Unsicherheit'),
(26, 1, 3, 'Überraschung'),
(27, 1, 3, 'Erwartung'),
(28, 1, 3, 'Erleichterung');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_emotion_type`
--

CREATE TABLE `_mtr_emotion_type` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `_mtr_emotion_type`
--

INSERT INTO `_mtr_emotion_type` (`id`, `type`) VALUES
(1, 'positiv'),
(2, 'negativ'),
(3, 'kognitiv');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_leistung_val_basiswissen`
--

CREATE TABLE `_mtr_leistung_val_basiswissen` (
  `id` int(11) NOT NULL,
  `wert_a` int(11) NOT NULL,
  `wert_b` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_leistung_val_beherrscht_thema`
--

CREATE TABLE `_mtr_leistung_val_beherrscht_thema` (
  `id` int(11) NOT NULL,
  `wert_a` int(11) NOT NULL,
  `wert_b` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_leistung_val_lernfortschritt`
--

CREATE TABLE `_mtr_leistung_val_lernfortschritt` (
  `id` int(11) NOT NULL,
  `wert_a` int(11) NOT NULL,
  `wert_b` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_leistung_val_transferdenken`
--

CREATE TABLE `_mtr_leistung_val_transferdenken` (
  `id` int(11) NOT NULL,
  `wert_a` int(11) NOT NULL,
  `wert_b` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_leistung_val_vorbereitet`
--

CREATE TABLE `_mtr_leistung_val_vorbereitet` (
  `id` int(11) NOT NULL,
  `wert_a` int(11) NOT NULL,
  `wert_b` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_map_emotions`
--

CREATE TABLE `_mtr_map_emotions` (
  `id` int(11) NOT NULL,
  `mtr_rk_tn` int(11) NOT NULL,
  `mtr_emotions` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `_mtr_map_emotions`
--

INSERT INTO `_mtr_map_emotions` (`id`, `mtr_rk_tn`, `mtr_emotions`) VALUES
(1, 1, 'zufriedenheit'),
(2, 2, 'stolz'),
(3, 3, 'frustration'),
(4, 4, 'erfuellung'),
(5, 5, 'motivation'),
(6, 6, 'selbstvertrauen'),
(7, 7, 'ueberforderung'),
(8, 8, 'dankbarkeit'),
(9, 9, 'angst'),
(10, 10, 'hoffnung'),
(11, 11, 'erleichterung'),
(12, 12, 'interesse'),
(13, 13, '	freude');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--

CREATE TABLE `_mtr_persoenlichkeitsmerkmal_definition` (
  `merkmal_id` int(11) NOT NULL,
  `merkmal_name` varchar(255) NOT NULL,
  `beschreibung_allgemein` text,
  `theoretischer_hintergrund` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--

INSERT INTO `_mtr_persoenlichkeitsmerkmal_definition` (`merkmal_id`, `merkmal_name`, `beschreibung_allgemein`, `theoretischer_hintergrund`) VALUES
(1, 'offenheit_erfahrungen', 'Offenheit für neue Erfahrungen, Lerninhalte und Situationen.', 'Könnte sich in der Dynamik der Akteur-Funktion A(Ψ,x,y,z,t) äußern, die exploratives Verhalten in Bezug auf das "Feld" (Ψ) anregt. Verbunden mit der Fähigkeit zur Reflexion (Meta-Funktion) über neue Bedeutungsfelder und der Bereitschaft, etablierte semantische Attraktoren zu verlassen. Kann sich in der Offenheit für die Transformation symbolischer Meta-Strukturen zeigen.'),
(2, 'gewissenhaftigkeit', 'Gewissenhaftigkeit und Leistungsbereitschaft.', 'Könnte sich in der Dynamik der Akteur-Funktion A(Ψ,x,y,z,t) äußern, die exploratives Verhalten in Bezug auf das "Feld" (Ψ) anregt. Verbunden mit der Fähigkeit zur Reflexion (Meta-Funktion) über neue Bedeutungsfelder und der Bereitschaft, etablierte semantische Attraktoren zu verlassen. Kann sich in der Offenheit für die Transformation symbolischer Meta-Strukturen zeigen. Hohe Ausprägung könnte mit der Stabilität der Akteur-Funktion und der Fähigkeit zur Aufrechterhaltung von Zielen korrelieren.'),
(3, 'extraversion', 'Extraversion und soziale Interaktion.', 'Könnte sich in der Interaktion der Akteur-Funktion A(Ψ,x,y,z,t) mit sozialen "Feldern" (Ψ) manifestieren. Hohe Ausprägung ist verbunden mit der Fähigkeit, sich in soziale Interaktionen zu begeben und diese aktiv zu gestalten. Könnte auch mit der energetischen Komponente der Meta-Funktion M(A,Ψ,x,y,z,t) zusammenhängen, die die Bereitschaft zur Exploration und Einflussnahme auf die Umgebung fördert.'),
(4, 'vertraeglichkeit', 'Verträglichkeit und Kooperationsbereitschaft.', 'Zeigt sich in der Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t), sich an gemeinsame "Feldzustände" anzupassen und kooperatives Verhalten zu zeigen. Könnte auch mit der Resonanz der Meta-Funktion M(A,Ψ,x,y,z,t) auf die emotionalen und sozialen Signale anderer Akteure zusammenhängen. Hohe Ausprägung fördert die Bildung stabiler semantischer Attraktoren im sozialen Kontext.'),
(5, 'zielorientierung', 'Zielorientierung und Fokus.', 'Spiegelt die Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t) wider, spezifische "Feldzustände" als Ziele zu definieren und darauf hinzuarbeiten. Verbunden mit der Kohärenz der Meta-Funktion M(A,Ψ,x,y,z,t) in Bezug auf die Planung und Durchführung von Handlungen. Hohe Ausprägung könnte mit der Stärke der semantischen Attraktoren für bestimmte Ziele zusammenhängen.'),
(6, 'lernfaehigkeit', 'Lernfähigkeit und Adaptivität.', 'Beschreibt die Plastizität der Akteur-Funktion A(Ψ,x,y,z,t) und ihre Fähigkeit, neue "Feldzustände" und Verhaltensweisen zu internalisieren. Verbunden mit der Agilität der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Neubildung und Transformation von Wissensstrukturen. Hohe Ausprägung ist essentiell für die Anpassung an sich ändernde Umgebungen und die effektive Nutzung neuer Informationen.'),
(7, 'anpassungsfaehigkeit', 'Anpassungsfähigkeit an neue Situationen und Anforderungen.', 'Zeigt sich in der Flexibilität der Akteur-Funktion A(Ψ,x,y,z,t) und ihrer Fähigkeit, sich an unvorhergesehene "Feldzustände" anzupassen. Könnte auch mit der Robustheit der Meta-Funktion M(A,Ψ,x,y,z,t) unter Stressbedingungen und ihrer Fähigkeit zur Reorganisation von Handlungsplänen zusammenhängen. Hohe Ausprägung ermöglicht die effiziente Navigation in dynamischen Umgebungen.'),
(8, 'soziale_interaktion', 'Fähigkeit zur sozialen Interaktion und Kommunikation.', 'Betont die bidirektionale Kopplung der Akteur-Funktion A(Ψ,x,y,z,t) mit den sozialen "Feldern" (Ψ) und die Fähigkeit zur gemeinsamen Konstruktion von Bedeutung. Verbunden mit der Kommunikationsfähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t) in der Koordination mit anderen Akteuren. Hohe Ausprägung ist entscheidend für effektive Zusammenarbeit und den Aufbau sozialer Netzwerke.'),
(9, 'metakognition', 'Metakognitive Fähigkeiten und Selbstreflexion.', 'Beschreibt die Fähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t), die eigene Akteur-Funktion A(Ψ,x,y,z,t) und die Interaktion mit dem "Feld" (Ψ) zu beobachten, zu bewerten und zu regulieren. Dies beinhaltet das Verständnis der eigenen Lernprozesse und der Wirksamkeit der angewandten Strategien. Hohe Ausprägung ermöglicht eine bewusste Steuerung von Lern- und Handlungsprozessen.'),
(10, 'stressbewaeltigung', 'Stressbewältigungsstrategien und emotionale Regulation.', 'Bezieht sich auf die Resilienz der Akteur-Funktion A(Ψ,x,y,z,t) und ihre Fähigkeit, unter Druck effektive Handlungen aufrechtzuerhalten. Verbunden mit der Regulationsfähigkeit der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Verarbeitung von emotionalen "Feldzuständen". Hohe Ausprägung ermöglicht es, Herausforderungen konstruktiv zu begegnen und psychische Belastungen zu reduzieren.'),
(11, 'bedeutungsbildung', 'Bedeutungsbildung und Sinnstiftung.', 'Umfasst die Fähigkeit der Akteur-Funktion A(Ψ,x,y,z,t), kohärente "Feldzustände" zu schaffen und diesen eine persönliche oder kollektive Bedeutung zu verleihen. Verbunden mit der integrativen Funktion der Meta-Funktion M(A,Ψ,x,y,z,t) bei der Synthese von Erfahrungen und der Konstruktion von Weltbildern. Hohe Ausprägung ist fundamental für die persönliche Entwicklung und das Gefühl der Kohärenz.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `_std_lernthema_inhalt`
--

CREATE TABLE `_std_lernthema_inhalt` (
  `id` int(11) NOT NULL,
  `std_lernthema_id` int(11) DEFAULT NULL,
  `inhalt` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Indizes für die Tabelle `frzk_semantische_dichte`
--
ALTER TABLE `frzk_semantische_dichte`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `map_tmp_ue`
--
ALTER TABLE `map_tmp_ue`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_ue_zuweisung_schueler_id` (`ue_unterrichtseinheit_id`),
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
  ADD KEY `idx_ue_zuweisung_schueler_id` (`ue_zuweisung_schueler_id`),
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
-- Indizes für die Tabelle `mtr_sozial`
--
ALTER TABLE `mtr_sozial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mtr_sozial_ue_teilnehmer_zuweisung_FK` (`ue_zuweisung_teilehmer_id`);

--
-- Indizes für die Tabelle `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indizes für die Tabelle `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indizes für die Tabelle `session_students`
--
ALTER TABLE `session_students`
  ADD PRIMARY KEY (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

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
  ADD KEY `std_teilnehmer__std_klassentyp_FK` (`KlassentypID`);

--
-- Indizes für die Tabelle `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- Indizes für die Tabelle `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

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
-- Indizes für die Tabelle `ue_teilnehmer_zuweisung`
--
ALTER TABLE `ue_teilnehmer_zuweisung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teilnehmer_id` (`teilnehmer_id`),
  ADD KEY `ue_unterrichtseinheit_thema_id` (`ue_unterrichtseinheit_thema_id`);

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
  ADD KEY `ue_zuweisung_lernthema_id` (`ue_zuweisung_lernthema_id`),
  ADD KEY `teilnehmer_id` (`teilnehmer_id`);

--
-- Indizes für die Tabelle `verhaltens_mapping`
--
ALTER TABLE `verhaltens_mapping`
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
-- Indizes für die Tabelle `_mtr_emotionen`
--
ALTER TABLE `_mtr_emotionen`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_emotion_type`
--
ALTER TABLE `_mtr_emotion_type`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_leistung_val_basiswissen`
--
ALTER TABLE `_mtr_leistung_val_basiswissen`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_leistung_val_beherrscht_thema`
--
ALTER TABLE `_mtr_leistung_val_beherrscht_thema`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_leistung_val_lernfortschritt`
--
ALTER TABLE `_mtr_leistung_val_lernfortschritt`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_leistung_val_transferdenken`
--
ALTER TABLE `_mtr_leistung_val_transferdenken`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_leistung_val_vorbereitet`
--
ALTER TABLE `_mtr_leistung_val_vorbereitet`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_map_emotions`
--
ALTER TABLE `_mtr_map_emotions`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--
ALTER TABLE `_mtr_persoenlichkeitsmerkmal_definition`
  ADD PRIMARY KEY (`merkmal_id`),
  ADD UNIQUE KEY `merkmal_name` (`merkmal_name`);

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
-- AUTO_INCREMENT für Tabelle `frzk_semantische_dichte`
--
ALTER TABLE `frzk_semantische_dichte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `map_tmp_ue`
--
ALTER TABLE `map_tmp_ue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT für Tabelle `mtr_didaktik`
--
ALTER TABLE `mtr_didaktik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `mtr_emotions`
--
ALTER TABLE `mtr_emotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `mtr_leistung`
--
ALTER TABLE `mtr_leistung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `mtr_persoenlichkeit`
--
ALTER TABLE `mtr_persoenlichkeit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT für Tabelle `mtr_rueckkopplung_lehrkraft_lesson`
--
ALTER TABLE `mtr_rueckkopplung_lehrkraft_lesson`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `mtr_rueckkopplung_teilnehmer`
--
ALTER TABLE `mtr_rueckkopplung_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT für Tabelle `mtr_sozial`
--
ALTER TABLE `mtr_sozial`
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
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT für Tabelle `std_lernthema`
--
ALTER TABLE `std_lernthema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;
--
-- AUTO_INCREMENT für Tabelle `std_teilnehmer`
--
ALTER TABLE `std_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT für Tabelle `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT für Tabelle `ue_teilnehmer_zuweisung`
--
ALTER TABLE `ue_teilnehmer_zuweisung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT für Tabelle `ue_unterrichtseinheit`
--
ALTER TABLE `ue_unterrichtseinheit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT für Tabelle `ue_unterrichtseinheit_zw_thema`
--
ALTER TABLE `ue_unterrichtseinheit_zw_thema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT für Tabelle `ue_zuweisung_teilnehmer`
--
ALTER TABLE `ue_zuweisung_teilnehmer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT für Tabelle `verhaltens_mapping`
--
ALTER TABLE `verhaltens_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
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
-- AUTO_INCREMENT für Tabelle `_mtr_emotionen`
--
ALTER TABLE `_mtr_emotionen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT für Tabelle `_mtr_emotion_type`
--
ALTER TABLE `_mtr_emotion_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT für Tabelle `_mtr_leistung_val_basiswissen`
--
ALTER TABLE `_mtr_leistung_val_basiswissen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `_mtr_leistung_val_beherrscht_thema`
--
ALTER TABLE `_mtr_leistung_val_beherrscht_thema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `_mtr_leistung_val_lernfortschritt`
--
ALTER TABLE `_mtr_leistung_val_lernfortschritt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `_mtr_leistung_val_transferdenken`
--
ALTER TABLE `_mtr_leistung_val_transferdenken`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `_mtr_leistung_val_vorbereitet`
--
ALTER TABLE `_mtr_leistung_val_vorbereitet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `_mtr_map_emotions`
--
ALTER TABLE `_mtr_map_emotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT für Tabelle `_mtr_persoenlichkeitsmerkmal_definition`
--
ALTER TABLE `_mtr_persoenlichkeitsmerkmal_definition`
  MODIFY `merkmal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
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
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
