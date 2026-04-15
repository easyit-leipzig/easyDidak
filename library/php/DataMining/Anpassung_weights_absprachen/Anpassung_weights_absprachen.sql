-- 1. Handler-Gewichte definieren
DROP TEMPORARY TABLE IF EXISTS tmp_handler_weight;
CREATE TABLE tmp_handler_weight (
    handler varchar(100) PRIMARY KEY,
    gewicht decimal(6,3) NOT NULL
);

INSERT INTO tmp_handler_weight (handler, gewicht) VALUES
('allgemeine Bewertung', 1.000),
('Pädagoge',             1.200),
('Schüler',              0.900),
('Eltern',               0.900),
('neutrale Person',      1.000),
('Operator',             1.150),
('Kohärenzprüfer',       1.250),
('Emergenz-Beobachter',  1.100),
('Hub-Detektor',         1.050);

-- 2. Gewichtete Konsenswerte aus ausw_werte/type=4 pro wert_id erzeugen
DROP TEMPORARY TABLE IF EXISTS tmp_type4_konsens;
CREATE TEMPORARY TABLE tmp_type4_konsens AS
SELECT
    aw.wert_id,
    SUM(aw.x_kognition  * hw.gewicht) / SUM(hw.gewicht) AS kognition,
    SUM(aw.x_sozial     * hw.gewicht) / SUM(hw.gewicht) AS sozial,
    SUM(aw.x_affektiv   * hw.gewicht) / SUM(hw.gewicht) AS affektiv,
    SUM(aw.x_motivation * hw.gewicht) / SUM(hw.gewicht) AS motivation,
    SUM(aw.x_methodik   * hw.gewicht) / SUM(hw.gewicht) AS methodik,
    SUM(aw.x_performanz * hw.gewicht) / SUM(hw.gewicht) AS performanz,
    SUM(aw.x_regulation * hw.gewicht) / SUM(hw.gewicht) AS regulation
FROM ausw_werte aw
JOIN ausw_werte_reihe_beschr arb
  ON arb.id = aw.reihe
JOIN tmp_handler_weight hw
  ON hw.handler = arb.handler
WHERE aw.type = 4
GROUP BY aw.wert_id;

CREATE TABLE tmp_type4_konsens AS
SELECT
    aw.wert_id,
    SUM(aw.x_kognition  * hw.gewicht) / SUM(hw.gewicht) AS kognition,
    SUM(aw.x_sozial     * hw.gewicht) / SUM(hw.gewicht) AS sozial,
    SUM(aw.x_affektiv   * hw.gewicht) / SUM(hw.gewicht) AS affektiv,
    SUM(aw.x_motivation * hw.gewicht) / SUM(hw.gewicht) AS motivation,
    SUM(aw.x_methodik   * hw.gewicht) / SUM(hw.gewicht) AS methodik,
    SUM(aw.x_performanz * hw.gewicht) / SUM(hw.gewicht) AS performanz,
    SUM(aw.x_regulation * hw.gewicht) / SUM(hw.gewicht) AS regulation
FROM ausw_werte aw
JOIN ausw_werte_reihe_beschr arb
  ON arb.id = aw.reihe
JOIN tmp_handler_weight hw
  ON hw.handler = arb.handler
WHERE aw.type = 4
GROUP BY aw.wert_id;

CREATE TABLE frzk_funktionsklassen_weight_absprachen_map (
    id_absprachen int NOT NULL,
    wert_id int NOT NULL,
    PRIMARY KEY (id_absprachen),
    KEY (wert_id)
);

UPDATE frzk_funktionsklassen_weight_absprachen f
JOIN frzk_funktionsklassen_weight_absprachen_map m
  ON m.id_absprachen = f.id
JOIN tmp_type4_konsens k
  ON k.wert_id = m.wert_id
SET
  f.kognition  = ROUND(k.kognition, 3),
  f.sozial     = ROUND(k.sozial, 3),
  f.affektiv   = ROUND(k.affektiv, 3),
  f.motivation = ROUND(k.motivation, 3),
  f.methodik   = ROUND(k.methodik, 3),
  f.performanz = ROUND(k.performanz, 3),
  f.regulation = ROUND(k.regulation, 3);

  ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN token INT DEFAULT 0;

UPDATE frzk_funktionsklassen_weight_absprachen
SET token =
CASE
    WHEN TRIM(COALESCE(NULLIF(konv_value,''), real_value)) = '' THEN 0
    ELSE
        1 + (
            LENGTH(TRIM(COALESCE(NULLIF(konv_value,''), real_value))) 
            - LENGTH(REPLACE(TRIM(COALESCE(NULLIF(konv_value,''), real_value)), ' / ', ''))
        ) / LENGTH(' / ')
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN dominante_dimension VARCHAR(50);

UPDATE frzk_funktionsklassen_weight_absprachen
SET dominante_dimension =
CASE
    WHEN ABS(kognition)  >= ABS(sozial)
     AND ABS(kognition)  >= ABS(affektiv)
     AND ABS(kognition)  >= ABS(motivation)
     AND ABS(kognition)  >= ABS(methodik)
     AND ABS(kognition)  >= ABS(performanz)
     AND ABS(kognition)  >= ABS(regulation)
        THEN 'kognition'

    WHEN ABS(sozial) >= ABS(affektiv)
     AND ABS(sozial) >= ABS(motivation)
     AND ABS(sozial) >= ABS(methodik)
     AND ABS(sozial) >= ABS(performanz)
     AND ABS(sozial) >= ABS(regulation)
        THEN 'sozial'

    WHEN ABS(affektiv) >= ABS(motivation)
     AND ABS(affektiv) >= ABS(methodik)
     AND ABS(affektiv) >= ABS(performanz)
     AND ABS(affektiv) >= ABS(regulation)
        THEN 'affektiv'

    WHEN ABS(motivation) >= ABS(methodik)
     AND ABS(motivation) >= ABS(performanz)
     AND ABS(motivation) >= ABS(regulation)
        THEN 'motivation'

    WHEN ABS(methodik) >= ABS(performanz)
     AND ABS(methodik) >= ABS(regulation)
        THEN 'methodik'

    WHEN ABS(performanz) >= ABS(regulation)
        THEN 'performanz'

    ELSE 'regulation'
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN polaritaet_gesamt INT;


UPDATE frzk_funktionsklassen_weight_absprachen
SET polaritaet_gesamt =
CASE
    WHEN (
        COALESCE(kognition,0) +
        COALESCE(sozial,0) +
        COALESCE(affektiv,0) +
        COALESCE(motivation,0) +
        COALESCE(methodik,0) +
        COALESCE(performanz,0) +
        COALESCE(regulation,0)
    ) > 0 THEN 1

    WHEN (
        COALESCE(kognition,0) +
        COALESCE(sozial,0) +
        COALESCE(affektiv,0) +
        COALESCE(motivation,0) +
        COALESCE(methodik,0) +
        COALESCE(performanz,0) +
        COALESCE(regulation,0)
    ) < 0 THEN -1

    ELSE 0
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN dominante_dimension_wert DOUBLE;

UPDATE frzk_funktionsklassen_weight_absprachen
SET dominante_dimension_wert =
CASE dominante_dimension
    WHEN 'kognition'  THEN kognition
    WHEN 'sozial'     THEN sozial
    WHEN 'affektiv'   THEN affektiv
    WHEN 'motivation' THEN motivation
    WHEN 'methodik'   THEN methodik
    WHEN 'performanz' THEN performanz
    WHEN 'regulation' THEN regulation
    ELSE NULL
END;


INSERT INTO `frzk_semantische_dichte_lehrer_gesamt` ( `type`,  `id_mtr_rueckkopplung_datenmaske`,  `x_kognition`, `x_sozial`, `x_affektiv`, `x_motivation`, `x_methodik`, `x_performanz`, `x_regulation`, `token_anzahl`, `dominante_dimension`, `dominante_dimension_wert`, `polaritaet_gesamt`) 
select
  2 as type,
id_mtr_rueckkopplung_datenmaske,   
kognition,
sozial,
 affektiv,
 motivation,
 methodik,
  performanz,
  regulation,
  token,
  dominante_dimension,
  dominante_dimension_wert,
  polaritaet_gesamt
from sql_mapping_absprachen_weight

UPDATE frzk_semantische_dichte_lehrer_gesamt
SET dominante_dimension_wert =
CASE dominante_dimension
    WHEN 'kognition'  THEN x_kognition
    WHEN 'sozial'     THEN x_sozial
    WHEN 'affektiv'   THEN x_affektiv
    WHEN 'motivation' THEN x_motivation
    WHEN 'methodik'   THEN x_methodik
    WHEN 'performanz' THEN x_performanz
    WHEN 'regulation' THEN x_regulation
    ELSE NULL
END;

semantische dichte type = 3 (absprachen + vektor)
-- 1. Handler-Gewichte definieren
DROP TEMPORARY TABLE IF EXISTS tmp_handler_weight;
CREATE TABLE tmp_handler_weight (
    handler varchar(100) PRIMARY KEY,
    gewicht decimal(6,3) NOT NULL
);

INSERT INTO tmp_handler_weight (handler, gewicht) VALUES
('allgemeine Bewertung', 1.000),
('Pädagoge',             1.200),
('Schüler',              0.900),
('Eltern',               0.900),
('neutrale Person',      1.000),
('Operator',             1.150),
('Kohärenzprüfer',       1.250),
('Emergenz-Beobachter',  1.100),
('Hub-Detektor',         1.050);

-- 2. Gewichtete Konsenswerte aus ausw_werte/type=4 pro wert_id erzeugen
DROP TEMPORARY TABLE IF EXISTS tmp_type4_konsens;
CREATE TEMPORARY TABLE tmp_type4_konsens AS
SELECT
    aw.wert_id,
    SUM(aw.x_kognition  * hw.gewicht) / SUM(hw.gewicht) AS kognition,
    SUM(aw.x_sozial     * hw.gewicht) / SUM(hw.gewicht) AS sozial,
    SUM(aw.x_affektiv   * hw.gewicht) / SUM(hw.gewicht) AS affektiv,
    SUM(aw.x_motivation * hw.gewicht) / SUM(hw.gewicht) AS motivation,
    SUM(aw.x_methodik   * hw.gewicht) / SUM(hw.gewicht) AS methodik,
    SUM(aw.x_performanz * hw.gewicht) / SUM(hw.gewicht) AS performanz,
    SUM(aw.x_regulation * hw.gewicht) / SUM(hw.gewicht) AS regulation
FROM ausw_werte aw
JOIN ausw_werte_reihe_beschr arb
  ON arb.id = aw.reihe
JOIN tmp_handler_weight hw
  ON hw.handler = arb.handler
WHERE aw.type = 4
GROUP BY aw.wert_id;

CREATE TABLE tmp_type4_konsens AS
SELECT
    aw.wert_id,
    SUM(aw.x_kognition  * hw.gewicht) / SUM(hw.gewicht) AS kognition,
    SUM(aw.x_sozial     * hw.gewicht) / SUM(hw.gewicht) AS sozial,
    SUM(aw.x_affektiv   * hw.gewicht) / SUM(hw.gewicht) AS affektiv,
    SUM(aw.x_motivation * hw.gewicht) / SUM(hw.gewicht) AS motivation,
    SUM(aw.x_methodik   * hw.gewicht) / SUM(hw.gewicht) AS methodik,
    SUM(aw.x_performanz * hw.gewicht) / SUM(hw.gewicht) AS performanz,
    SUM(aw.x_regulation * hw.gewicht) / SUM(hw.gewicht) AS regulation
FROM ausw_werte aw
JOIN ausw_werte_reihe_beschr arb
  ON arb.id = aw.reihe
JOIN tmp_handler_weight hw
  ON hw.handler = arb.handler
WHERE aw.type = 4
GROUP BY aw.wert_id;

CREATE TABLE frzk_funktionsklassen_weight_absprachen_map (
    id_absprachen int NOT NULL,
    wert_id int NOT NULL,
    PRIMARY KEY (id_absprachen),
    KEY (wert_id)
);

UPDATE frzk_funktionsklassen_weight_absprachen f
JOIN frzk_funktionsklassen_weight_absprachen_map m
  ON m.id_absprachen = f.id
JOIN tmp_type4_konsens k
  ON k.wert_id = m.wert_id
SET
  f.kognition  = ROUND(k.kognition, 3),
  f.sozial     = ROUND(k.sozial, 3),
  f.affektiv   = ROUND(k.affektiv, 3),
  f.motivation = ROUND(k.motivation, 3),
  f.methodik   = ROUND(k.methodik, 3),
  f.performanz = ROUND(k.performanz, 3),
  f.regulation = ROUND(k.regulation, 3);

  ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN token INT DEFAULT 0;

UPDATE frzk_funktionsklassen_weight_absprachen
SET token =
CASE
    WHEN TRIM(COALESCE(NULLIF(konv_value,''), real_value)) = '' THEN 0
    ELSE
        1 + (
            LENGTH(TRIM(COALESCE(NULLIF(konv_value,''), real_value))) 
            - LENGTH(REPLACE(TRIM(COALESCE(NULLIF(konv_value,''), real_value)), ' / ', ''))
        ) / LENGTH(' / ')
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN dominante_dimension VARCHAR(50);

UPDATE frzk_funktionsklassen_weight_absprachen
SET dominante_dimension =
CASE
    WHEN ABS(kognition)  >= ABS(sozial)
     AND ABS(kognition)  >= ABS(affektiv)
     AND ABS(kognition)  >= ABS(motivation)
     AND ABS(kognition)  >= ABS(methodik)
     AND ABS(kognition)  >= ABS(performanz)
     AND ABS(kognition)  >= ABS(regulation)
        THEN 'kognition'

    WHEN ABS(sozial) >= ABS(affektiv)
     AND ABS(sozial) >= ABS(motivation)
     AND ABS(sozial) >= ABS(methodik)
     AND ABS(sozial) >= ABS(performanz)
     AND ABS(sozial) >= ABS(regulation)
        THEN 'sozial'

    WHEN ABS(affektiv) >= ABS(motivation)
     AND ABS(affektiv) >= ABS(methodik)
     AND ABS(affektiv) >= ABS(performanz)
     AND ABS(affektiv) >= ABS(regulation)
        THEN 'affektiv'

    WHEN ABS(motivation) >= ABS(methodik)
     AND ABS(motivation) >= ABS(performanz)
     AND ABS(motivation) >= ABS(regulation)
        THEN 'motivation'

    WHEN ABS(methodik) >= ABS(performanz)
     AND ABS(methodik) >= ABS(regulation)
        THEN 'methodik'

    WHEN ABS(performanz) >= ABS(regulation)
        THEN 'performanz'

    ELSE 'regulation'
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN polaritaet_gesamt INT;


UPDATE frzk_funktionsklassen_weight_absprachen
SET polaritaet_gesamt =
CASE
    WHEN (
        COALESCE(kognition,0) +
        COALESCE(sozial,0) +
        COALESCE(affektiv,0) +
        COALESCE(motivation,0) +
        COALESCE(methodik,0) +
        COALESCE(performanz,0) +
        COALESCE(regulation,0)
    ) > 0 THEN 1

    WHEN (
        COALESCE(kognition,0) +
        COALESCE(sozial,0) +
        COALESCE(affektiv,0) +
        COALESCE(motivation,0) +
        COALESCE(methodik,0) +
        COALESCE(performanz,0) +
        COALESCE(regulation,0)
    ) < 0 THEN -1

    ELSE 0
END;

ALTER TABLE frzk_funktionsklassen_weight_absprachen
ADD COLUMN dominante_dimension_wert DOUBLE;

UPDATE frzk_funktionsklassen_weight_absprachen
SET dominante_dimension_wert =
CASE dominante_dimension
    WHEN 'kognition'  THEN kognition
    WHEN 'sozial'     THEN sozial
    WHEN 'affektiv'   THEN affektiv
    WHEN 'motivation' THEN motivation
    WHEN 'methodik'   THEN methodik
    WHEN 'performanz' THEN performanz
    WHEN 'regulation' THEN regulation
    ELSE NULL
END;


INSERT INTO `frzk_semantische_dichte_lehrer_gesamt` ( `type`,  `id_mtr_rueckkopplung_datenmaske`,  `x_kognition`, `x_sozial`, `x_affektiv`, `x_motivation`, `x_methodik`, `x_performanz`, `x_regulation`, `token_anzahl`, `dominante_dimension`, `dominante_dimension_wert`, `polaritaet_gesamt`) 
select
  2 as type,
id_mtr_rueckkopplung_datenmaske,   
kognition,
sozial,
 affektiv,
 motivation,
 methodik,
  performanz,
  regulation,
  token,
  dominante_dimension,
  dominante_dimension_wert,
  polaritaet_gesamt
from sql_mapping_absprachen_weight

UPDATE frzk_semantische_dichte_lehrer_gesamt
SET dominante_dimension_wert =
CASE dominante_dimension
    WHEN 'kognition'  THEN x_kognition
    WHEN 'sozial'     THEN x_sozial
    WHEN 'affektiv'   THEN x_affektiv
    WHEN 'motivation' THEN x_motivation
    WHEN 'methodik'   THEN x_methodik
    WHEN 'performanz' THEN x_performanz
    WHEN 'regulation' THEN x_regulation
    ELSE NULL
END;

semantische dichte type = 3 (absprachen + vektor)

SELECT
    3 AS type,
    q.id_mtr_rueckkopplung_datenmaske,

    q.sum_kognition,
    q.sum_sozial,
    q.sum_affektiv,
    q.sum_motivation,
    q.sum_methodik,
    q.sum_performanz,
    q.sum_regulation,

    -- Norm / semantische Dichte
    SQRT(
        POW(q.sum_kognition, 2) +
        POW(q.sum_sozial, 2) +
        POW(q.sum_affektiv, 2) +
        POW(q.sum_motivation, 2) +
        POW(q.sum_methodik, 2) +
        POW(q.sum_performanz, 2) +
        POW(q.sum_regulation, 2)
    ) AS d_semantisch,

    -- normierte x-Werte
    q.sum_kognition / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_kognition,

    q.sum_sozial / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_sozial,

    q.sum_affektiv / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_affektiv,

    q.sum_motivation / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_motivation,

    q.sum_methodik / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_methodik,

    q.sum_performanz / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_performanz,

    q.sum_regulation / NULLIF(
        SQRT(
            POW(q.sum_kognition, 2) +
            POW(q.sum_sozial, 2) +
            POW(q.sum_affektiv, 2) +
            POW(q.sum_motivation, 2) +
            POW(q.sum_methodik, 2) +
            POW(q.sum_performanz, 2) +
            POW(q.sum_regulation, 2)
        ), 0
    ) AS x_regulation,

    CASE
        WHEN ABS(q.sum_kognition) >= ABS(q.sum_sozial)
         AND ABS(q.sum_kognition) >= ABS(q.sum_affektiv)
         AND ABS(q.sum_kognition) >= ABS(q.sum_motivation)
         AND ABS(q.sum_kognition) >= ABS(q.sum_methodik)
         AND ABS(q.sum_kognition) >= ABS(q.sum_performanz)
         AND ABS(q.sum_kognition) >= ABS(q.sum_regulation)
            THEN 'kognition'
        WHEN ABS(q.sum_sozial) >= ABS(q.sum_affektiv)
         AND ABS(q.sum_sozial) >= ABS(q.sum_motivation)
         AND ABS(q.sum_sozial) >= ABS(q.sum_methodik)
         AND ABS(q.sum_sozial) >= ABS(q.sum_performanz)
         AND ABS(q.sum_sozial) >= ABS(q.sum_regulation)
            THEN 'sozial'
        WHEN ABS(q.sum_affektiv) >= ABS(q.sum_motivation)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_methodik)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_performanz)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_regulation)
            THEN 'affektiv'
        WHEN ABS(q.sum_motivation) >= ABS(q.sum_methodik)
         AND ABS(q.sum_motivation) >= ABS(q.sum_performanz)
         AND ABS(q.sum_motivation) >= ABS(q.sum_regulation)
            THEN 'motivation'
        WHEN ABS(q.sum_methodik) >= ABS(q.sum_performanz)
         AND ABS(q.sum_methodik) >= ABS(q.sum_regulation)
            THEN 'methodik'
        WHEN ABS(q.sum_performanz) >= ABS(q.sum_regulation)
            THEN 'performanz'
        ELSE 'regulation'
    END AS dominante_dimension,

    CASE
        WHEN ABS(q.sum_kognition) >= ABS(q.sum_sozial)
         AND ABS(q.sum_kognition) >= ABS(q.sum_affektiv)
         AND ABS(q.sum_kognition) >= ABS(q.sum_motivation)
         AND ABS(q.sum_kognition) >= ABS(q.sum_methodik)
         AND ABS(q.sum_kognition) >= ABS(q.sum_performanz)
         AND ABS(q.sum_kognition) >= ABS(q.sum_regulation)
            THEN q.sum_kognition
        WHEN ABS(q.sum_sozial) >= ABS(q.sum_affektiv)
         AND ABS(q.sum_sozial) >= ABS(q.sum_motivation)
         AND ABS(q.sum_sozial) >= ABS(q.sum_methodik)
         AND ABS(q.sum_sozial) >= ABS(q.sum_performanz)
         AND ABS(q.sum_sozial) >= ABS(q.sum_regulation)
            THEN q.sum_sozial
        WHEN ABS(q.sum_affektiv) >= ABS(q.sum_motivation)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_methodik)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_performanz)
         AND ABS(q.sum_affektiv) >= ABS(q.sum_regulation)
            THEN q.sum_affektiv
        WHEN ABS(q.sum_motivation) >= ABS(q.sum_methodik)
         AND ABS(q.sum_motivation) >= ABS(q.sum_performanz)
         AND ABS(q.sum_motivation) >= ABS(q.sum_regulation)
            THEN q.sum_motivation
        WHEN ABS(q.sum_methodik) >= ABS(q.sum_performanz)
         AND ABS(q.sum_methodik) >= ABS(q.sum_regulation)
            THEN q.sum_methodik
        WHEN ABS(q.sum_performanz) >= ABS(q.sum_regulation)
            THEN q.sum_performanz
        ELSE q.sum_regulation
    END AS dominante_dimension_wert,

    CASE
        WHEN (
            q.sum_kognition + q.sum_sozial + q.sum_affektiv +
            q.sum_motivation + q.sum_methodik + q.sum_performanz + q.sum_regulation
        ) > 0 THEN 1
        WHEN (
            q.sum_kognition + q.sum_sozial + q.sum_affektiv +
            q.sum_motivation + q.sum_methodik + q.sum_performanz + q.sum_regulation
        ) < 0 THEN -1
        ELSE 0
    END AS polaritaet_gesamt

FROM (
    SELECT
        MIN(id_mtr_rueckkopplung_datenmaske) AS id_mtr_rueckkopplung_datenmaske,

        AVG(sum_kognition)  AS sum_kognition,
        AVG(sum_sozial)     AS sum_sozial,
        AVG(sum_affektiv)   AS sum_affektiv,
        AVG(sum_motivation) AS sum_motivation,
        AVG(sum_methodik)   AS sum_methodik,
        AVG(sum_performanz) AS sum_performanz,
        AVG(sum_regulation) AS sum_regulation

    FROM frzk_semantische_dichte_lehrer_gesamt
    WHERE type IN (1,2)
    GROUP BY id_mtr_rueckkopplung_datenmaske
) q;