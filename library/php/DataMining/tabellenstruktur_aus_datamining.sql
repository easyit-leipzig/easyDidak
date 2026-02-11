-- ------------------------------------------------------------------------
-- Tabelle: frzk_semantische_dichte_dm
-- Beschreibung: Semantische Dichte aus Sicht der Teilnehmer, inkl. Emotionen
-- ------------------------------------------------------------------------
CREATE TABLE `frzk_semantische_dichte_dm` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `teilnehmer_id` int(11) NOT NULL,
    `gruppe_id` int(11) DEFAULT NULL,
    `zeitpunkt` datetime NOT NULL,
    `x_kognition` decimal(5,2) DEFAULT 0,
    `y_sozial` decimal(5,2) DEFAULT 0,
    `z_affektiv` decimal(5,2) DEFAULT 0,
    `h_bedeutung` decimal(5,2) DEFAULT 0,
    `dh_dt` decimal(5,2) DEFAULT 0,
    `cluster_id` int(2) DEFAULT 0,
    `stabilitaet_score` decimal(4,2) DEFAULT 0,
    `transitions_marker` varchar(50) DEFAULT NULL,
    `bemerkung` varchar(255) DEFAULT NULL,
    `emotions` text DEFAULT NULL,
    `emotions_valenz` decimal(4,2) DEFAULT 0,
    `emotions_aktivierung` decimal(4,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_tid` (`teilnehmer_id`),
    KEY `idx_gruppe` (`gruppe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------------------
-- Tabelle: frzk_unterrichtsleistung_dm
-- Beschreibung: Unterrichtsleistung aus Teilnehmersicht, inkl. Engagement-Score
-- ------------------------------------------------------------------------
CREATE TABLE `frzk_unterrichtsleistung_dm` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `teilnehmer_id` int(11) NOT NULL,
    `gruppe_id` int(11) DEFAULT NULL,
    `zeitpunkt` datetime NOT NULL,
    `x_kognition` decimal(5,2) DEFAULT 0,
    `y_sozial` decimal(5,2) DEFAULT 0,
    `z_affektiv` decimal(5,2) DEFAULT 0,
    `h_bedeutung` decimal(5,2) DEFAULT 0,
    `engagement_score` decimal(5,2) DEFAULT 0,
    `emotions` text DEFAULT NULL,
    `emotions_valenz` decimal(4,2) DEFAULT 0,
    `emotions_aktivierung` decimal(4,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_tid` (`teilnehmer_id`),
    KEY `idx_gruppe` (`gruppe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
