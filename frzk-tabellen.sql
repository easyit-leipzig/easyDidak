-- ==========================================================
-- 1. Operatorenkaskade (σ, M, R, E)
-- Abbildung der im FRZK beschriebenen didaktischen Operatoren
-- ==========================================================
drop table frzk_operatoren;
CREATE TABLE frzk_operatoren (
    operator_id INT AUTO_INCREMENT PRIMARY KEY,         -- Eindeutige ID für den Operatoreneintrag
    teilnehmer_id INT NOT NULL comment 'Referenz auf Teilnehmer (Verknüpfung zu ue_zuweisung_teilnehmer)',                         -- Referenz auf Teilnehmer (Verknüpfung zu ue_zuweisung_teilnehmer)
    zeitpunkt DATETIME NOT NULL comment 'Zeitpunkt der Beobachtung / Messung',                        -- Zeitpunkt der Beobachtung / Messung
    sigma_level DECIMAL(5,2) comment 'Stärke der σ-Operatoren (Semantisierung, Bedeutungserzeugung)',                           -- Stärke der σ-Operatoren (Semantisierung, Bedeutungserzeugung)
    m_level DECIMAL(5,2) comment 'Stärke der M-Operatoren (Meta-Reflexion, Abstraktion)',                               -- Stärke der M-Operatoren (Meta-Reflexion, Abstraktion)
    r_level DECIMAL(5,2) comment 'Stärke der R-Operatoren (Resonanz, Rückkopplung)',                               -- Stärke der R-Operatoren (Resonanz, Rückkopplung)
    e_level DECIMAL(5,2) comment 'Stärke der E-Operatoren (Emergenz, neues Auftreten)',                               -- Stärke der E-Operatoren (Emergenz, neues Auftreten)
    bemerkung TEXT  comment 'Freitextfeld für qualitative Notizen oder Kodierungen'                                     -- Freitextfeld für qualitative Notizen oder Kodierungen
);

-- ==========================================================
-- 2. Raumzeit-Loops
-- Modelliert die Zyklen von Verdichtung und Pause im Lernprozess
-- ==========================================================
drop table frzk_loops;
CREATE TABLE frzk_loops (
    loop_id INT AUTO_INCREMENT PRIMARY KEY,             -- Eindeutige ID für den Loop
    teilnehmer_id INT NOT NULL  comment 'Referenz auf Teilnehmer',                         -- Referenz auf Teilnehmer
    startzeit DATETIME NOT NULL  comment 'Beginn des Loop-Prozesses',                        -- Beginn des Loop-Prozesses
    endzeit DATETIME  comment 'Ende des Loop-Prozesses (NULL = noch offen)',                                   -- Ende des Loop-Prozesses (NULL = noch offen)
    verdichtungsgrad DECIMAL(5,2)  comment 'Maß für die semantische Verdichtung in diesem Loop (z. B. aus σ-Werten berechnet)',                      -- Maß für die semantische Verdichtung in diesem Loop (z. B. aus σ-Werten berechnet)
    pausenmarker BOOLEAN DEFAULT FALSE  comment 'Kennzeichnung, ob es sich um eine Pause/Stillstand-Phase handelt',                 -- Kennzeichnung, ob es sich um eine Pause/Stillstand-Phase handelt
    anmerkung TEXT  comment 'Freitext für Beobachtungen zum Loop (z. B. Wechsel von stabil → instabil)'                                      -- Freitext für Beobachtungen zum Loop (z. B. Wechsel von stabil → instabil)
);

-- ==========================================================
-- 3. Emergenz-Marker / Übergangsereignisse
-- Erfasst Kippmomente, die auf neue Strukturen oder Bedeutungen hindeuten
-- ==========================================================
drop table frzk_transitions;
CREATE TABLE frzk_transitions (
    transition_id INT AUTO_INCREMENT PRIMARY KEY,       -- Eindeutige ID des Übergangsereignisses
    cluster_id INT comment 'Referenz auf semantische Cluster (aus frzk_semantische_dichte)',                                     -- Referenz auf semantische Cluster (aus frzk_semantische_dichte)
    teilnehmer_id INT comment 'Beteiligter Akteur',                                  -- Beteiligter Akteur
    zeitpunkt DATETIME NOT NULL comment 'Zeitpunkt des Übergangs',                        -- Zeitpunkt des Übergangs
    typ VARCHAR(50) comment "Art des Übergangs: 'Neue Struktur', 'Stabilisierung', 'Irritation', 'Bedeutungswechsel'",                                    -- Art des Übergangs: 'Neue Struktur', 'Stabilisierung', 'Irritation', 'Bedeutungswechsel'
    indikator_score DECIMAL(5,2) comment 'Quantitatives Maß für die Übergangsstärke (z. B. σ-Sprung)',                       -- Quantitatives Maß für die Übergangsstärke (z. B. σ-Sprung)
    kommentar TEXT comment 'Qualitative Notizen zum Kontext'                                      -- Qualitative Notizen zum Kontext
);

-- ==========================================================
-- 4. Beobachter- / Meta-Ebene
-- Trennt Beobachtungen erster und zweiter Ordnung
-- ==========================================================
drop table frzk_reflexion;
CREATE TABLE frzk_reflexion (
    reflexion_id INT AUTO_INCREMENT PRIMARY KEY ,        -- Eindeutige ID
    teilnehmer_id INT,                                  -- Bezug zur beobachteten Person
    beobachter_id INT comment 'ID des Beobachters (z. B. Lehrkraft, Forscher)',                                  -- ID des Beobachters (z. B. Lehrkraft, Forscher)
    ebene ENUM('Selbst','Gruppe','Lehrkraft','Forscher') comment 'Ebene der Reflexion', -- Ebene der Reflexion
    datum DATETIME NOT NULL comment 'Zeitpunkt der Reflexion',                            -- Zeitpunkt der Reflexion
    reflexionstext TEXT comment 'Inhaltliche Reflexion (z. B. Metakommentar, Tagebuch, Lehrkraftprotokoll)'                                 -- Inhaltliche Reflexion (z. B. Metakommentar, Tagebuch, Lehrkraftprotokoll)
);

-- ==========================================================
-- 5. Interdependenzen der Dimensionen
-- Beschreibt Zusammenhänge zwischen kognitiver, sozialer, affektiver und semantischer Achse
-- ==========================================================
drop table frzk_interdependenz;
CREATE TABLE frzk_interdependenz (
    interdependenz_id INT AUTO_INCREMENT PRIMARY KEY,   -- Eindeutige ID
    zeitpunkt DATETIME NOT NULL comment 'Zeitpunkt der Berechnung',                        -- Zeitpunkt der Berechnung
    x_kognition DECIMAL(5,2) comment 'Mittelwert / Messwert kognitive Dimension',                           -- Mittelwert / Messwert kognitive Dimension
    y_sozial DECIMAL(5,2) comment 'Mittelwert / Messwert soziale Dimension',                              -- Mittelwert / Messwert soziale Dimension
    z_affektiv DECIMAL(5,2) comment 'Mittelwert / Messwert affektive Dimension',                            -- Mittelwert / Messwert affektive Dimension
    h_bedeutung DECIMAL(5,2) comment 'Dichtewert aus semantischer Funktion',                           -- Dichtewert aus semantischer Funktion
    korrelationsscore DECIMAL(5,2) comment 'Berechneter Zusammenhang (z. B. Pearson-Korrelation)',                     -- Berechneter Zusammenhang (z. B. Pearson-Korrelation)
    methode VARCHAR(100) comment 'Angabe der Berechnungsmethode (z. B. Korrelationsanalyse, Regressionsmodell)'                                -- Angabe der Berechnungsmethode (z. B. Korrelationsanalyse, Regressionsmodell)
);
