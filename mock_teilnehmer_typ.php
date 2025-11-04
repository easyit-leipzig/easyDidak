<?php
/**
 * ============================================================
 *  ICAS – Monatliche Aggregation von Persoenlichkeitsprofilen
 * ============================================================
 * Generiert oder aktualisiert monatliche Datensaetze in
 * `mtr_persoenlichkeit` auf Basis der Emotionen aus
 * `mtr_rueckkopplung_teilnehmer`.
 *
 * Erweiterungen:
 *  - Berücksichtigung ALLER aktiven Teilnehmer (std_teilnehmer)
 *  - Neue Felder: problemloesefaehigkeit, ko_kreationsfaehigkeit
 *  - Neutrale Standardwerte, wenn keine Rückmeldung vorliegt
 *  - Berechnungslogik basierend auf Emotionsvalenz & Aktivierung
 *
 * Cron-Empfehlung:
 *   0 2 1 * * /usr/bin/php /var/www/html/library/php/mock_aggregation_persoenlichkeit.php
 * ============================================================
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insert_count = 0;
$update_count = 0;
$errors = [];

// ============================================================
// 1. Emotionstabelle laden
// ============================================================
$emotionen = [];
$res = $pdo->query("SELECT id AS code, valenz, aktivierung FROM _mtr_emotionen");
foreach ($res as $r) {
    $emotionen[strtolower($r['code'])] = [
        'v' => (float)$r['valenz'],
        'a' => (float)$r['aktivierung']
    ];
}

// ============================================================
// 2. Alle aktiven Teilnehmer + Emotionen je Monat laden
// ============================================================
$sql = "
SELECT 
    t.id AS teilnehmer_id,
    DATE_FORMAT(r.erfasst_am, '%Y-%m-01') AS monat,
    GROUP_CONCAT(r.emotions SEPARATOR ',') AS alle_emotionen
FROM std_teilnehmer t
LEFT JOIN mtr_rueckkopplung_teilnehmer r 
       ON t.id = r.teilnehmer_id
WHERE t.show_tn = 1 
  AND t.teilnehmer_typ = 1
GROUP BY t.id, monat
ORDER BY t.id, monat
";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 3. SQL-Statements vorbereiten
// ============================================================
$select_p = $pdo->prepare("
    SELECT id FROM mtr_persoenlichkeit
    WHERE teilnehmer_id = ? AND datum = ?
    LIMIT 1
");

$update_p = $pdo->prepare("
    UPDATE mtr_persoenlichkeit
    SET offenheit_erfahrungen=?, gewissenhaftigkeit=?, extraversion=?, vertraeglichkeit=?,
        zielorientierung=?, lernfaehigkeit=?, stressbewaeltigung=?, kreativitaet_innovation=?,
        resonanzfaehigkeit=?, bedeutungsbildung=?, metakognition=?, problemloesefaehigkeit=?,
        ko_kreationsfaehigkeit=?, note=?
    WHERE id=?
");

$insert_p = $pdo->prepare("
    INSERT INTO mtr_persoenlichkeit
    (teilnehmer_id, datum, offenheit_erfahrungen, gewissenhaftigkeit, extraversion, vertraeglichkeit,
     zielorientierung, lernfaehigkeit, stressbewaeltigung, kreativitaet_innovation, resonanzfaehigkeit,
     bedeutungsbildung, metakognition, problemloesefaehigkeit, ko_kreationsfaehigkeit, note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// ============================================================
// 4. Hauptverarbeitung
// ============================================================
foreach ($data as $d) {
    try {
        $tid   = (int)$d['teilnehmer_id'];
        $monat = $d['monat'] ?: date('Y-m-01'); // Fallback: aktueller Monat
        $codes = array_filter(array_map('trim', explode(',', strtolower($d['alle_emotionen']))));

        // Emotionale Mittelwerte berechnen oder neutral setzen
        if (empty($codes)) {
            $v_avg = 0.0;
            $a_avg = 0.0;
        } else {
            $val_sum = 0;
            $act_sum = 0;
            $count   = 0;
            foreach ($codes as $c) {
                if (!isset($emotionen[$c])) continue;
                $val_sum += $emotionen[$c]['v'];
                $act_sum += $emotionen[$c]['a'];
                $count++;
            }
            $v_avg = $count ? $val_sum / $count : 0.0;
            $a_avg = $count ? $act_sum / $count : 0.0;
        }

        // ====================================================
        // 5. Berechnung der Persoenlichkeitsparameter
        // ====================================================
        $offenheit = round(2.5 + 2.5 * $v_avg + 0.5 * $a_avg, 1);
        $gewissen  = round(2.0 + 1.5 * $a_avg, 1);
        $extra     = round(1.5 + 2.0 * $a_avg, 1);
        $vertraeg  = round(2.5 + 1.5 * $v_avg, 1);
        $ziel      = round(2.0 + 1.5 * $a_avg + 0.5 * $v_avg, 1);
        $lern      = round(3.0 + 0.8 * ($v_avg + $a_avg) / 2, 1);
        $stress    = round(5.5 - (1.5 * $a_avg + 0.5 * (1 - $v_avg)), 1);
        $kreativ   = round(3.0 + 1.2 * $v_avg, 1);
        $resonanz  = round(2.5 + 1.0 * $v_avg + 0.5 * (1 - abs($a_avg - 0.5)), 1);
        $bedeutung = round(3.0 + 0.7 * $v_avg, 1);
        $metakogn  = round(2.5 + 1.0 * $a_avg + 0.5 * $v_avg, 1);

        // Neue Felder:
        $problem   = round(2.5 + 1.8 * $a_avg + 0.8 * $v_avg, 1);
        $ko_kr     = round(2.8 + 1.2 * $v_avg + 1.0 * (1 - abs($a_avg - 0.6)), 1);

        // Gesamtnote (gewichtetes Mittel)
        $note      = round(($offenheit + $ziel + $lern + $stress + $bedeutung + $problem + $ko_kr) / 7, 1);

        // Begrenzen auf 1.0–5.5
        foreach ([
            &$offenheit, &$gewissen, &$extra, &$vertraeg, &$ziel,
            &$lern, &$stress, &$kreativ, &$resonanz, &$bedeutung,
            &$metakogn, &$problem, &$ko_kr, &$note
        ] as &$x) {
            $x = max(1.0, min(5.5, $x));
        }

        // ====================================================
        // 6. Datensatz speichern (Insert/Update)
        // ====================================================
        $select_p->execute([$tid, $monat]);
        $id = $select_p->fetchColumn();

        if ($id) {
            $update_p->execute([
                $offenheit, $gewissen, $extra, $vertraeg, $ziel,
                $lern, $stress, $kreativ, $resonanz, $bedeutung,
                $metakogn, $problem, $ko_kr, $note, $id
            ]);
            $update_count++;
        } else {
            $insert_p->execute([
                $tid, $monat, $offenheit, $gewissen, $extra, $vertraeg,
                $ziel, $lern, $stress, $kreativ, $resonanz, $bedeutung,
                $metakogn, $problem, $ko_kr, $note
            ]);
            $insert_count++;
        }

    } catch (PDOException $e) {
        $errors[] = "Fehler bei Teilnehmer $tid ($monat): " . $e->getMessage();
    }
}

// ============================================================
// 7. Abschlussbericht
// ============================================================
echo "✅ Monatsaggregation abgeschlossen.\n";
echo "Neue Datensaetze: $insert_count\n";
echo "Aktualisiert: $update_count\n";
if ($errors) {
    echo "⚠️ Fehler:\n - " . implode("\n - ", $errors) . "\n";
} else {
    echo "Keine Fehler aufgetreten.\n";
}
?>
