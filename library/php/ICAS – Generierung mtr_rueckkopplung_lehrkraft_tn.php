<?php
/**
 * ============================================================
 *  ICAS – Generierung: mtr_rueckkopplung_lehrkraft_tn
 * ============================================================
 * Quellen:
 *   - mtr_persoenlichkeit  (monatliche Persoenlichkeitsdaten)
 *   - mtr_rueckkopplung_teilnehmer (emotionale Selbstwahrnehmung)
 *
 * Ziel:
 *   - Monatliche Lehrkraftsicht pro Teilnehmer erzeugen
 *   - Kognitive Leistungen werden pro Monat um -0.2 abgesenkt
 *   - ko_kreationsfaehigkeit wird zusaetzlich berechnet & gespeichert
 *
 * Ergebnis:
 *   - 1 Datensatz pro Teilnehmer und Monat in mtr_rueckkopplung_lehrkraft_tn
 *
 * Zeitsteuerung (z. B. Cronjob):
 *   0 3 1 * * /usr/bin/php /var/www/html/library/php/mock_rueckkopplung_lehrkraft_tn.php
 * ============================================================
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insert_count = 0;
$update_count = 0;
$errors = [];

// ============================================================
// 1. Persoenlichkeitsdaten je Teilnehmer & Monat laden
// ============================================================
$sql = "
SELECT 
    teilnehmer_id,
    DATE_FORMAT(datum, '%Y-%m-01') AS monat,
    offenheit_erfahrungen,
    gewissenhaftigkeit,
    extraversion,
    vertraeglichkeit,
    zielorientierung,
    lernfaehigkeit,
    stressbewaeltigung,
    bedeutungsbildung,
    belastbarkeit,
    problemloesefaehigkeit,
    kreativitaet_innovation,
    ko_kreationsfaehigkeit,
    resonanzfaehigkeit,
    handlungsdichte,
    performanz_effizienz,
    basiswissen,
    note
FROM mtr_persoenlichkeit
ORDER BY teilnehmer_id, monat
";
$p_data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 2. SQL-Statements vorbereiten
// ============================================================
$select_existing = $pdo->prepare("
    SELECT id FROM mtr_rueckkopplung_lehrkraft_tn
    WHERE teilnehmer_id = ? AND DATE_FORMAT(erfasst_am, '%Y-%m-01') = ?
    LIMIT 1
");

$insert_stmt = $pdo->prepare("
    INSERT INTO mtr_rueckkopplung_lehrkraft_tn
    (teilnehmer_id, erfasst_am,
     basiswissen, problemloesefaehigkeit, kreativitaet, zielorientierung,
     lernverhalten, motivation, ko_kreationsfaehigkeit, kommentar)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$update_stmt = $pdo->prepare("
    UPDATE mtr_rueckkopplung_lehrkraft_tn
    SET basiswissen = ?,
        problemloesefaehigkeit = ?,
        kreativitaet = ?,
        zielorientierung = ?,
        lernverhalten = ?,
        motivation = ?,
        ko_kreationsfaehigkeit = ?,
        kommentar = ?
    WHERE id = ?
");

// ============================================================
// 3. Verarbeitung pro Teilnehmer & Monat
// ============================================================
foreach ($p_data as $row) {
    try {
        $tid = (int)$row['teilnehmer_id'];
        $monat = $row['monat'];

        // -------------------------------------------
        // 3.1 Basiswerte aus mtr_persoenlichkeit
        // -------------------------------------------
        $basiswissen  = (float)$row['basiswissen'];
        $problem      = (float)$row['problemloesefaehigkeit'];
        $kreativ      = (float)$row['kreativitaet_innovation'];
        $ziel         = (float)$row['zielorientierung'];
        $lern         = (float)$row['lernfaehigkeit'];
        $motivation   = (float)$row['resonanzfaehigkeit'];
        $ko_kr        = (float)$row['ko_kreationsfaehigkeit'];
        $note         = (float)$row['note'];

        // -------------------------------------------
        // 3.2 Zeitliche Absenkung (–0.2 pro Monat)
        // -------------------------------------------
        static $start_month = null;
        if (!$start_month) $start_month = strtotime($monat);
        $months_passed = max(0, round((strtotime($monat) - $start_month) / (30 * 24 * 3600)));

        $decay = 0.2 * $months_passed;

        $basiswissen = max(1.0, $basiswissen - $decay);
        $problem     = max(1.0, $problem - $decay);
        $kreativ     = max(1.0, $kreativ - $decay);
        $ziel        = max(1.0, $ziel - ($decay / 2));
        $lern        = max(1.0, $lern - ($decay / 3));
        $motivation  = max(1.0, $motivation - ($decay / 3));
        $ko_kr       = max(1.0, $ko_kr - ($decay / 2));

        // -------------------------------------------
        // 3.3 Kommentartext generieren
        // -------------------------------------------
        $kommentar = sprintf(
            "Lehrkraftsicht (%s): Trend %.1f | Motivation %.1f | Ko-Kreation %.1f",
            $monat, $note, $motivation, $ko_kr
        );

        // -------------------------------------------
        // 3.4 Prüfen, ob bereits ein Datensatz existiert
        // -------------------------------------------
        $select_existing->execute([$tid, $monat]);
        $existing_id = $select_existing->fetchColumn();

        if ($existing_id) {
            // --- UPDATE ---
            $update_stmt->execute([
                $basiswissen,
                $problem,
                $kreativ,
                $ziel,
                $lern,
                $motivation,
                $ko_kr,
                $kommentar,
                $existing_id
            ]);
            $update_count++;
        } else {
            // --- INSERT ---
            $insert_stmt->execute([
                $tid,
                $monat,
                $basiswissen,
                $problem,
                $kreativ,
                $ziel,
                $lern,
                $motivation,
                $ko_kr,
                $kommentar
            ]);
            $insert_count++;
        }

    } catch (PDOException $e) {
        $errors[] = "Fehler bei Teilnehmer $tid ($monat): " . $e->getMessage();
    }
}

// ============================================================
// 4. Abschlussbericht
// ============================================================
echo "✅ Lehrkraft-Rückkopplung abgeschlossen.\n";
echo "Neue Datensaetze: $insert_count\n";
echo "Aktualisiert: $update_count\n";
if ($errors) {
    echo "⚠️ Fehler:\n - " . implode("\n - ", $errors) . "\n";
} else {
    echo "Keine Fehler aufgetreten.\n";
}
?>
