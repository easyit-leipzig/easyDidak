START TRANSACTION;

-- ============================================================
-- 0. BACKUP
-- ============================================================

DROP TABLE IF EXISTS frzk_funktionsklasse_weight_backup_step1_global;

CREATE TABLE frzk_funktionsklasse_weight_backup_step1_global AS
SELECT *
FROM frzk_funktionsklasse_weight;

-- ============================================================
-- 1. GLOBALES UPDATE ALLER FUNKTIONSKLASSEN
--    gedämpfte Faktoren aus Δ-Matrix
-- ============================================================

UPDATE frzk_funktionsklasse_weight
SET
    kognition  = GREATEST(-1.000000, LEAST(1.000000, kognition  * 1.013698)),
    sozial     = GREATEST(-1.000000, LEAST(1.000000, sozial     * 1.986580)),
    affektiv   = GREATEST(-1.000000, LEAST(1.000000, affektiv   * 0.882893)),
    motivation = GREATEST(-1.000000, LEAST(1.000000, motivation * 1.458798)),
    methodik   = GREATEST(-1.000000, LEAST(1.000000, methodik   * 1.202249)),
    performanz = GREATEST(-1.000000, LEAST(1.000000, performanz * 1.248523)),
    regulation = GREATEST(-1.000000, LEAST(1.000000, regulation * 1.344707))
WHERE funktionsklasse_id NOT IN (0, 10);

-- ============================================================
-- 2. REPROJEKTION AUF DIE TOKEN-TABELLE
--    wichtig, weil das PHP-Skript aus
--    frzk_lexem_datenmaske_lexem_funktionsklasse_weight liest
-- ============================================================

UPDATE frzk_lexem_datenmaske_lexem_funktionsklasse_weight l
JOIN frzk_funktionsklasse_weight f
  ON l.funktionsklasse_id = f.funktionsklasse_id
SET
    l.kognition  = f.kognition,
    l.sozial     = f.sozial,
    l.affektiv   = f.affektiv,
    l.motivation = f.motivation,
    l.methodik   = f.methodik,
    l.performanz = f.performanz,
    l.regulation = f.regulation
WHERE l.funktionsklasse_id NOT IN (0, 10);

COMMIT;