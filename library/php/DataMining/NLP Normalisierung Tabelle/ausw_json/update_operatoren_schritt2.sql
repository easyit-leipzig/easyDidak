-- Schritt 2: erste, minimalinvasive Anpassung bestehender Operatorfaktoren
-- Ziel: vorhandene Operatoren kalibrieren, ohne neue Operatoren einzuführen.
-- Logik:
-- 1) contrast-Operatoren im left_context etwas abschwächen, damit der linke Kontext
--    nicht zu stark entwertet wird.
-- 2) ausgewählte dampener etwas weniger stark dämpfen, damit Motivation/Regulation/
--    soziale Anteile nicht unnötig kollabieren.
-- 3) Negationen und Intensifier in Schritt 2 bewusst unverändert lassen.

START TRANSACTION;

-- Backup der betroffenen Datensätze
CREATE TABLE IF NOT EXISTS frzk_operator_backup_schritt2 AS
SELECT * FROM frzk_operator WHERE 1=0;

DELETE FROM frzk_operator_backup_schritt2
WHERE id IN (12,13,14,15,16,17,18,19,20,34,37,38,39,40,41);

INSERT INTO frzk_operator_backup_schritt2
SELECT *
FROM frzk_operator
WHERE id IN (12,13,14,15,16,17,18,19,20,34,37,38,39,40,41);

-- Dampener: etwas weniger stark dämpfen
UPDATE frzk_operator SET faktor = 0.72 WHERE id = 12 AND name = 'etwas';
UPDATE frzk_operator SET faktor = 0.82 WHERE id = 13 AND name = 'teilweise';
UPDATE frzk_operator SET faktor = 0.80 WHERE id = 14 AND name = 'eher';
UPDATE frzk_operator SET faktor = 0.85 WHERE id = 15 AND name = 'relativ';
UPDATE frzk_operator SET faktor = 0.82 WHERE id = 16 AND name = 'leicht';
UPDATE frzk_operator SET faktor = 0.48 WHERE id = 34 AND name = 'kaum';
UPDATE frzk_operator SET faktor = 0.68 WHERE id = 37 AND name = 'weniger';
UPDATE frzk_operator SET faktor = 0.76 WHERE id = 38 AND name = 'gelegentlich';
UPDATE frzk_operator SET faktor = 0.76 WHERE id = 39 AND name = 'manchmal';
UPDATE frzk_operator SET faktor = 0.76 WHERE id = 40 AND name = 'punktuell';
UPDATE frzk_operator SET faktor = 0.68 WHERE id = 41 AND name = 'vereinzelt';

-- Contrast: left_context weniger hart abwerten
UPDATE frzk_operator SET faktor = 0.65 WHERE id = 17 AND name = 'aber';
UPDATE frzk_operator SET faktor = 0.68 WHERE id = 18 AND name = 'jedoch';
UPDATE frzk_operator SET faktor = 0.62 WHERE id = 19 AND name = 'dennoch';
UPDATE frzk_operator SET faktor = 0.62 WHERE id = 20 AND name = 'trotzdem';

COMMIT;
