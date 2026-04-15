-- Rollback für Schritt 2: Wiederherstellung der ursprünglichen Operatorfaktoren

START TRANSACTION;

UPDATE frzk_operator o
JOIN frzk_operator_backup_schritt2 b ON o.id = b.id
SET o.name = b.name,
    o.typ = b.typ,
    o.faktor = b.faktor,
    o.scope_typ = b.scope_typ,
    o.aktiv = b.aktiv,
    o.created_at = b.created_at
WHERE o.id IN (12,13,14,15,16,17,18,19,20,34,37,38,39,40,41);

COMMIT;
