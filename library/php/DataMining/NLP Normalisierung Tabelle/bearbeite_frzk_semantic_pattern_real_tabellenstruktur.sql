DROP TABLE IF EXISTS frzk_funktionsklasse;

CREATE TABLE frzk_funktionsklasse (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  beschreibung TEXT,
  literaturquelle TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO frzk_funktionsklasse (name, beschreibung, literaturquelle) VALUES
('performanzsteigerung',
 'Leistungszuwachs und Kompetenzentwicklung',
 'Hattie 2009; Helmke 2009'),

('performanzdefizit',
 'Leistungsrückgang oder Nichterreichen von Zielen',
 'Hattie 2009'),

('metakognitive_regulation',
 'Selbstreflexion, Überprüfung, Strategieanpassung',
 'Flavell 1979; Zimmerman 2000'),

('kognitive_aktivierung',
 'Vertieftes Verstehen, Durchdringen, Anwenden',
 'Helmke 2009'),

('intrinsische_motivation',
 'Interesse, Engagement, freiwillige Beteiligung',
 'Deci & Ryan 1985'),

('extrinsische_motivation',
 'Leistungsorientierung, Zielerreichung unter Vorgabe',
 'Deci & Ryan 1985'),

('methodische_kompetenz',
 'Strategieanwendung, strukturiertes Vorgehen',
 'Zimmerman 2000'),

('aufmerksamkeitsregulation',
 'Konzentration, Fokussierung',
 'Helmke 2009'),

('sozial_interaktion',
 'Kooperation, Beteiligung',
 'Johnson & Johnson 2009'),

('neutral',
 'Semantisch nicht eindeutig zuordenbar',
 'FRZK Systemdefinition');

DROP TABLE IF EXISTS frzk_funktionsklasse_weight;

CREATE TABLE frzk_funktionsklasse_weight (
  funktionsklasse_id INT PRIMARY KEY,
  kognition DECIMAL(4,3),
  motivation DECIMAL(4,3),
  methodik DECIMAL(4,3),
  performanz DECIMAL(4,3),
  regulation DECIMAL(4,3),
  FOREIGN KEY (funktionsklasse_id)
    REFERENCES frzk_funktionsklasse(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO frzk_funktionsklasse_weight VALUES
(1, 0.600, 0.400, 0.400, 0.900, 0.600),
(2,-0.600,-0.400,-0.400,-0.900,-0.600),
(3, 0.600, 0.400, 0.600, 0.500, 0.900),
(4, 0.900, 0.400, 0.500, 0.600, 0.600),
(5, 0.400, 0.900, 0.300, 0.500, 0.500),
(6, 0.400, 0.600, 0.300, 0.700, 0.400),
(7, 0.600, 0.500, 0.900, 0.600, 0.700),
(8, 0.500, 0.400, 0.400, 0.500, 0.800),
(9, 0.400, 0.700, 0.500, 0.500, 0.500),
(10,0.000, 0.000, 0.000, 0.000, 0.000);

DROP TABLE IF EXISTS frzk_lexem;

CREATE TABLE frzk_lexem (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lexem VARCHAR(255) NOT NULL UNIQUE,
  wortart ENUM('verb','adjektiv','adverb','nomen','negation'),
  funktionsklasse_id INT NULL,
  FOREIGN KEY (funktionsklasse_id)
    REFERENCES frzk_funktionsklasse(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS frzk_pattern;

CREATE TABLE frzk_pattern (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pattern VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS frzk_pattern_lexem;

CREATE TABLE frzk_pattern_lexem (
  pattern_id INT,
  lexem_id INT,
  PRIMARY KEY(pattern_id, lexem_id),
  FOREIGN KEY (pattern_id) REFERENCES frzk_pattern(id),
  FOREIGN KEY (lexem_id) REFERENCES frzk_lexem(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS frzk_intensitaet;

CREATE TABLE frzk_intensitaet (
  lexem_id INT PRIMARY KEY,
  faktor DECIMAL(4,2),
  FOREIGN KEY (lexem_id)
    REFERENCES frzk_lexem(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO frzk_lexem (lexem, wortart, funktionsklasse_id) VALUES
('sehr','adverb',10),
('stark','adverb',10),
('deutlich','adverb',10),
('kaum','adverb',10),
('teilweise','adverb',10),
('nicht','negation',10);

INSERT INTO frzk_intensitaet VALUES
((SELECT id FROM frzk_lexem WHERE lexem='sehr'),1.30),
((SELECT id FROM frzk_lexem WHERE lexem='stark'),1.50),
((SELECT id FROM frzk_lexem WHERE lexem='deutlich'),1.40),
((SELECT id FROM frzk_lexem WHERE lexem='kaum'),0.40),
((SELECT id FROM frzk_lexem WHERE lexem='teilweise'),0.60);

DROP TABLE IF EXISTS frzk_negationsregel;

CREATE TABLE frzk_negationsregel (
  lexem_id INT PRIMARY KEY,
  invertierungsfaktor DECIMAL(4,2) DEFAULT -0.80,
  FOREIGN KEY (lexem_id)
    REFERENCES frzk_lexem(id)
);

INSERT INTO frzk_negationsregel VALUES
((SELECT id FROM frzk_lexem WHERE lexem='nicht'),-1.00);

DROP TABLE IF EXISTS frzk_lexem_mapping;

CREATE TABLE frzk_lexem_mapping (
  lexem VARCHAR(255) PRIMARY KEY,
  funktionsklasse_id INT,
  FOREIGN KEY (funktionsklasse_id)
    REFERENCES frzk_funktionsklasse(id)
);

INSERT INTO frzk_lexem_mapping VALUES
('verbessern',1),
('steigern',1),
('erreichen',1),
('verschlechtern',2),
('reflektieren',3),
('überprüfen',3),
('verstehen',4),
('durchdringen',4),
('interessieren',5),
('engagieren',5),
('planen',7),
('strukturieren',7),
('konzentrieren',8),
('kooperieren',9);

CREATE OR REPLACE VIEW frzk_pattern_weight_view AS
SELECT 
  p.id AS pattern_id,
  SUM(w.kognition) AS kognition,
  SUM(w.motivation) AS motivation,
  SUM(w.methodik) AS methodik,
  SUM(w.performanz) AS performanz,
  SUM(w.regulation) AS regulation
FROM frzk_pattern p
JOIN frzk_pattern_lexem pl ON p.id = pl.pattern_id
JOIN frzk_lexem l ON pl.lexem_id = l.id
JOIN frzk_funktionsklasse_weight w
  ON l.funktionsklasse_id = w.funktionsklasse_id
GROUP BY p.id