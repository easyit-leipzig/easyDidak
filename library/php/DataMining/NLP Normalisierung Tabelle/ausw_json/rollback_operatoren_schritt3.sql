-- Rollback für Schritt 3: entfernt ausschließlich die in Schritt 3 neu ergänzten Einzelwort-Operatoren

START TRANSACTION;

DELETE FROM frzk_operator
WHERE name IN (
  'nur','selten','wenig','bedingt','eingeschränkt','sporadisch',
  'gelegentlich','vereinzelt','geringfügig','mittelmäßig','einigermaßen',
  'überwiegend','meistens','oftmals','regelmäßig'
)
AND created_at >= '2026-03-28 00:00:00';

COMMIT;
