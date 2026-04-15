-- Schritt 3: Hinzufügung fehlender Operatoren (nur Einzelwörter)
-- Ziel: Begrenzungen, Abschwächungen und leichte Gradierungen besser abbilden,
-- ohne den Vektoransatz oder bestehende Operatoren umzubauen.

START TRANSACTION;

-- Sicherheits-Backup der neu einzufügenden Operatoren in einer temporären Tabelle
CREATE TEMPORARY TABLE IF NOT EXISTS _frzk_operator_step3_backup AS
SELECT * FROM frzk_operator WHERE 1=0;

INSERT INTO _frzk_operator_step3_backup
SELECT *
FROM frzk_operator
WHERE name IN (
  'nur','selten','wenig','bedingt','eingeschränkt','sporadisch',
  'gelegentlich','vereinzelt','geringfügig','mittelmäßig','einigermaßen',
  'überwiegend','meistens','oftmals','regelmäßig'
);

-- Fehlende Operatoren ergänzen; vorhandene Einträge bleiben unberührt
INSERT INTO frzk_operator (name, typ, faktor, scope_typ, aktiv)
SELECT * FROM (
  SELECT 'nur' AS name, 'dampener' AS typ, 0.75 AS faktor, 'sentence' AS scope_typ, 1 AS aktiv
  UNION ALL SELECT 'selten',        'dampener', 0.78, 'sentence',   1
  UNION ALL SELECT 'wenig',         'dampener', 0.72, 'next_token', 1
  UNION ALL SELECT 'bedingt',       'dampener', 0.70, 'next_token', 1
  UNION ALL SELECT 'eingeschränkt', 'dampener', 0.55, 'next_token', 1
  UNION ALL SELECT 'sporadisch',    'dampener', 0.60, 'next_token', 1
  UNION ALL SELECT 'gelegentlich',  'dampener', 0.82, 'next_token', 1
  UNION ALL SELECT 'vereinzelt',    'dampener', 0.78, 'next_token', 1
  UNION ALL SELECT 'geringfügig',   'dampener', 0.88, 'next_token', 1
  UNION ALL SELECT 'mittelmäßig',   'dampener', 0.68, 'next_token', 1
  UNION ALL SELECT 'einigermaßen',  'dampener', 0.85, 'next_token', 1
  UNION ALL SELECT 'überwiegend',   'intensifier', 1.12, 'sentence', 1
  UNION ALL SELECT 'meistens',      'intensifier', 1.10, 'sentence', 1
  UNION ALL SELECT 'oftmals',       'intensifier', 1.12, 'sentence', 1
  UNION ALL SELECT 'regelmäßig',    'intensifier', 1.15, 'sentence', 1
) AS new_ops
WHERE NOT EXISTS (
  SELECT 1 FROM frzk_operator fo WHERE fo.name = new_ops.name
);

COMMIT;

-- Empfohlener Folge-Schritt nach dem Einspielen:
-- 1) Reihe 1 neu berechnen
-- 2) erneut gegen Reihen 8-16 validieren
