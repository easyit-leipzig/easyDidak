START TRANSACTION;

/*
Schritt 1 der minimalinvasiven Kalibrierung
Ziel:
- kognitive Übergewichtung in Reihe 1 leicht reduzieren
- soziale, motivationale und regulatorische Anteile moderat anheben
- Intensitätsklassen (211/212) bewusst unberührt lassen; diese folgen in Schritt 2/3

Basis der Auswahl:
- hohe Nutzung in den 35 Vergleichssätzen
- systematische Abweichung von Reihe 1 gegenüber dem Referenzfeld 8–16
- konservative Erstkalibrierung ohne Umbau des Vektoransatzes
*/

/* 1) stark frequentierte kognitive Basisklassen leicht entkognitivieren,
      zugleich Motivation/Regulation moderat anheben */
UPDATE frzk_funktionsklasse_weight
SET kognition = 0.720,
    motivation = 0.080,
    regulation = 0.050
WHERE funktionsklasse_id = 200; -- kognition_pos

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.820,
    motivation = 0.450,
    regulation = 0.650
WHERE funktionsklasse_id = 4; -- kognitive_aktivierung

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.740,
    motivation = 0.450,
    regulation = 0.650
WHERE funktionsklasse_id = 12; -- kognitiv_verstehen

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.650,
    motivation = 0.550,
    regulation = 0.650
WHERE funktionsklasse_id = 13; -- kognitiv_anwenden

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.540,
    motivation = 0.340,
    regulation = 0.450
WHERE funktionsklasse_id = 11; -- kognitiv_erinnern

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.560,
    sozial    = 0.050,
    motivation = 0.550,
    regulation = 0.750
WHERE funktionsklasse_id = 7; -- methodische_kompetenz

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.850,
    motivation = 0.520,
    regulation = 0.720
WHERE funktionsklasse_id = 14; -- kognitiv_analysieren

/* 2) soziale, motivationale und regulatorische Klassen gezielt stärken */
UPDATE frzk_funktionsklasse_weight
SET kognition = 0.450,
    sozial    = 0.850,
    motivation = 0.750,
    regulation = 0.650
WHERE funktionsklasse_id = 18; -- kommunikativ_interaktion

UPDATE frzk_funktionsklasse_weight
SET sozial    = 0.500,
    motivation = 0.980,
    regulation = 0.300
WHERE funktionsklasse_id = 202; -- motivation_pos

UPDATE frzk_funktionsklasse_weight
SET sozial    = 0.280,
    motivation = 0.380,
    regulation = 0.980
WHERE funktionsklasse_id = 204; -- regulation_pos

/* 3) positive Leistungsklassen leicht in Richtung Selbststeuerung verschieben */
UPDATE frzk_funktionsklasse_weight
SET kognition = 0.560,
    motivation = 0.450,
    regulation = 0.650
WHERE funktionsklasse_id = 1; -- performanzsteigerung

UPDATE frzk_funktionsklasse_weight
SET kognition = 0.260,
    sozial    = 0.050,
    motivation = 0.350,
    regulation = 0.300
WHERE funktionsklasse_id = 206; -- performanz_pos

/* 4) negative Gegenklassen moderat nachschärfen,
      damit Einschränkung/Defizit nicht zu schwach abgebildet wird */
UPDATE frzk_funktionsklasse_weight
SET sozial     = -0.280,
    motivation = -0.380,
    regulation = -0.980
WHERE funktionsklasse_id = 205; -- regulation_negativ

UPDATE frzk_funktionsklasse_weight
SET sozial     = -0.050,
    motivation = -0.350,
    regulation = -0.280
WHERE funktionsklasse_id = 208; -- performanz_negativ

COMMIT;
