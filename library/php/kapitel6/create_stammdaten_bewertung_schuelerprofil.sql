
-- FRZK PROFILE SQL DUMP
-- Generated for complete table creation and population
-- Charset: utf8mb4
-- Engine: InnoDB

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS _frzk_orverlust_profil_interventionspfad;
DROP TABLE IF EXISTS _frzk_orverlust_profil_typische_fehler;
DROP TABLE IF EXISTS _frzk_orverlust_profil_didaktische_ableitung;
DROP TABLE IF EXISTS _frzk_orverlust_profil_interpretation;
DROP TABLE IF EXISTS _frzk_orverlust_schuelerprofil_definition;

CREATE TABLE _frzk_orverlust_schuelerprofil_definition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profil_code VARCHAR(64) NOT NULL UNIQUE,
    titel VARCHAR(255) NOT NULL,
    epistemischer_typ ENUM('affektiv','kognitiv','sozial','hybrid','fragmentiert') NOT NULL,
    beschreibung TEXT NOT NULL,
    x_min FLOAT NOT NULL,
    x_max FLOAT NOT NULL,
    y_min FLOAT NOT NULL,
    y_max FLOAT NOT NULL,
    z_min FLOAT NOT NULL,
    z_max FLOAT NOT NULL,
    aktiv BOOLEAN DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO _frzk_orverlust_schuelerprofil_definition
(profil_code,titel,epistemischer_typ,beschreibung,x_min,x_max,y_min,y_max,z_min,z_max)
VALUES
('AFFEKTIV_GETRAGEN','Affektiv getragenes Schülerprofil','affektiv',
 'Hohe affektive Beteiligung bei geringer kognitiver und sozialer Integration.',0.0,0.3,0.0,0.4,0.8,1.0),
('KOGNITIV_FOKUSSIERT','Kognitiv fokussiertes Schülerprofil','kognitiv',
 'Hohe fachliche Strukturierung ohne emotionale oder soziale Einbettung.',0.7,1.0,0.0,0.4,0.0,0.3),
('SOZIAL_GETRAGEN','Sozial getragenes Schülerprofil','sozial',
 'Hohe soziale Einbindung ohne stabile fachliche oder affektive Struktur.',0.0,0.4,0.7,1.0,0.0,0.4),
('INTEGRIERT_STABIL','Integriertes stabiles Schülerprofil','hybrid',
 'Hohe kognitive, soziale und affektive Einbindung.',0.6,1.0,0.6,1.0,0.6,1.0),
('FRAGMENTIERT','Fragmentiertes Schülerprofil','fragmentiert',
 'Keine stabile Verankerung in einer Dimension.',0.0,0.4,0.0,0.4,0.0,0.4);

CREATE TABLE _frzk_orverlust_profil_interpretation (
    profil_id INT PRIMARY KEY,
    epistemische_lesart TEXT NOT NULL,
    lernpsychologische_einordnung TEXT NOT NULL,
    typische_erlebensqualitaet TEXT NOT NULL,
    FOREIGN KEY (profil_id) REFERENCES frzk_schuelerprofil_definition(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO _frzk_orverlust_profil_interpretation VALUES
(1,'Affekt dominiert Bedeutungszuschreibung.','Hohe emotionale Aktivierung ohne kognitive Stabilisierung.','Resonanz ohne Struktur.'),
(2,'Bedeutung über formale Struktur.','Stabile Leistung ohne emotionale Beteiligung.','Sachlich-distanziert.'),
(3,'Lernen als Beziehungsgeschehen.','Soziale Sicherheit ersetzt Struktur.','Zugehörigkeit vor Inhalt.'),
(4,'Kohärente Bedeutungsdichte.','Stabile Lernprozesse.','Sinnhaftigkeit und Orientierung.'),
(5,'Keine dominante Achse.','Instabile Lernprozesse.','Fragmentierung.');

CREATE TABLE _frzk_orverlust_profil_didaktische_ableitung (
    profil_id INT PRIMARY KEY,
    zentrale_empfehlung TEXT NOT NULL,
    fokus_im_unterricht TEXT NOT NULL,
    empfohlene_lehrkraftstrategie TEXT NOT NULL,
    FOREIGN KEY (profil_id) REFERENCES frzk_schuelerprofil_definition(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO _frzk_orverlust_profil_didaktische_ableitung VALUES
(1,'Nicht weiter motivieren.','Struktur und Begriffe.','Affekt → Struktur → Begriff.'),
(2,'Nicht weiter strukturieren.','Affekt & Soziales.','Emotionale Hubs einsetzen.'),
(3,'Soziales nicht weiter verstärken.','Fachliche Ordnung.','Sozial → Struktur.'),
(4,'Keine Intervention.','Stabilisierung.','Reflexion.'),
(5,'Reduktion.','Eine Achse.','Einen Hub stabilisieren.');

CREATE TABLE _frzk_orverlust_profil_typische_fehler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profil_id INT NOT NULL,
    fehler_code VARCHAR(64) NOT NULL,
    beschreibung TEXT NOT NULL,
    warum_problematisch TEXT NOT NULL,
    FOREIGN KEY (profil_id) REFERENCES frzk_schuelerprofil_definition(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO _frzk_orverlust_profil_typische_fehler
(profil_id,fehler_code,beschreibung,warum_problematisch)
VALUES
(1,'UEBERAKTIVIERUNG','Mehr Emotion.','Keine Struktur.'),
(2,'UEBERSTRUKTURIERUNG','Mehr Regeln.','Kein Mehrwert.'),
(3,'SOZIALFLUCHT','Mehr Gruppenarbeit.','Inhalt sekundär.'),
(4,'UEBERINTERVENTION','Optimierung.','Stört Stabilität.'),
(5,'MEHRGLEISIGKEIT','Alles gleichzeitig.','Überforderung.');

CREATE TABLE _frzk_orverlust_profil_interventionspfad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profil_id INT NOT NULL,
    schritt_nr INT NOT NULL,
    phase ENUM('affekt','struktur','begriff') NOT NULL,
    beschreibung TEXT NOT NULL,
    FOREIGN KEY (profil_id) REFERENCES frzk_schuelerprofil_definition(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO _frzk_orverlust_profil_interventionspfad
(profil_id,schritt_nr,phase,beschreibung)
VALUES
(1,1,'affekt','Affekt stabilisieren.'),
(1,2,'struktur','Struktur aufbauen.'),
(1,3,'begriff','Begriffe fixieren.'),
(2,1,'struktur','Sicherheit anerkennen.'),
(2,2,'affekt','Relevanz erzeugen.'),
(2,3,'begriff','Begriffe rückbinden.'),
(3,1,'affekt','Soziale Sicherheit anerkennen.'),
(3,2,'struktur','Fachliche Ordnung.'),
(3,3,'begriff','Begriffe sozial verankern.'),
(4,1,'struktur','Struktur sichern.'),
(4,2,'begriff','Begriffe reflektieren.'),
(4,3,'affekt','Bedeutung bewusst machen.'),
(5,1,'struktur','Einstieg setzen.'),
(5,2,'begriff','Zentralen Begriff fixieren.'),
(5,3,'affekt','Affekt dosieren.');

SET FOREIGN_KEY_CHECKS=1;
