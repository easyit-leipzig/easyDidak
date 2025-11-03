<?php
/**
 * ICAS – Monatliche Aggregation von Persönlichkeitsprofilen
 * ---------------------------------------------------------
 * Erzeugt oder aktualisiert monatliche Datensätze in mtr_persoenlichkeit
 * basierend auf Emotionen aus mtr_rueckkopplung_teilnehmer.
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insert_count = 0;
$update_count = 0;
$errors = [];

// ===========================================================
// 1. Emotionen laden
// ===========================================================
$emotionen = [];
$res = $pdo->query("SELECT id AS code, valenz, aktivierung FROM _mtr_emotionen");
foreach ($res as $r) {
    $emotionen[strtolower($r['code'])] = [
        'v' => (float)$r['valenz'],
        'a' => (float)$r['aktivierung']
    ];
}

// ===========================================================
// 2. Rückmeldungen nach Monat + Teilnehmer laden
// ===========================================================
$sql = "
SELECT teilnehmer_id,
       DATE_FORMAT(erfasst_am, '%Y-%m-01') AS monat,
       GROUP_CONCAT(emotions SEPARATOR ',') AS alle_emotionen
FROM mtr_rueckkopplung_teilnehmer
GROUP BY teilnehmer_id, monat
";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ===========================================================
// 3. SQL für INSERT/UPDATE vorbereiten
// ===========================================================
$select_p = $pdo->prepare("
    SELECT id FROM mtr_persoenlichkeit
    WHERE teilnehmer_id = ? AND datum = ?
    LIMIT 1
");

$update_p = $pdo->prepare("
    UPDATE mtr_persoenlichkeit
    SET offenheit_erfahrungen=?, gewissenhaftigkeit=?, Extraversion=?, vertraeglichkeit=?,
        zielorientierung=?, lernfaehigkeit=?, stressbewaeltigung=?, kreativität_innovation=?,
        resonanzfähigkeit=?, bedeutungsbildung=?, metakognition=?, note=?
    WHERE id=?
");

$insert_p = $pdo->prepare("
    INSERT INTO mtr_persoenlichkeit
    (teilnehmer_id, datum, offenheit_erfahrungen, gewissenhaftigkeit, Extraversion, vertraeglichkeit,
     zielorientierung, lernfaehigkeit, stressbewaeltigung, kreativität_innovation, resonanzfähigkeit,
     bedeutungsbildung, metakognition, note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// ===========================================================
// 4. Aggregation und Transformation
// ===========================================================
foreach ($data as $d) {
    try {
        $tid   = (int)$d['teilnehmer_id'];
        $monat = $d['monat'];
        $codes = array_filter(array_map('trim', explode(',', strtolower($d['alle_emotionen']))));

        $val_sum = 0;
        $act_sum = 0;
        $count   = 0;

        foreach ($codes as $c) {
            if (!isset($emotionen[$c])) continue;
            $val_sum += $emotionen[$c]['v'];
            $act_sum += $emotionen[$c]['a'];
            $count++;
        }

        if ($count === 0) continue;
        $v_avg = $val_sum / $count;
        $a_avg = $act_sum / $count;

        // ===================================================
        // 5. Berechnung der Persönlichkeitswerte
        // ===================================================
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
        $note      = round(($offenheit + $ziel + $lern + $stress + $bedeutung) / 5, 1);

        foreach ([
            &$offenheit, &$gewissen, &$extra, &$vertraeg, &$ziel,
            &$lern, &$stress, &$kreativ, &$resonanz, &$bedeutung, &$metakogn, &$note
        ] as &$x) {
            $x = max(1.0, min(5.5, $x));
        }

        // ===================================================
        // 6. Existenz prüfen und speichern
        // ===================================================
        $select_p->execute([$tid, $monat]);
        $id = $select_p->fetchColumn();

        if ($id) {
            $update_p->execute([
                $offenheit, $gewissen, $extra, $vertraeg, $ziel,
                $lern, $stress, $kreativ, $resonanz, $bedeutung, $metakogn, $note, $id
            ]);
            $update_count++;
        } else {
            $insert_p->execute([
                $tid, $monat, $offenheit, $gewissen, $extra, $vertraeg,
                $ziel, $lern, $stress, $kreativ, $resonanz, $bedeutung, $metakogn, $note
            ]);
            $insert_count++;
        }

    } catch (PDOException $e) {
        $errors[] = "Fehler bei Teilnehmer $tid ($monat): " . $e->getMessage();
    }
}

// ===========================================================
// 7. Abschlussbericht
// ===========================================================
echo "✅ Monatsaggregation abgeschlossen.\n";
echo "Neue Datensätze: $insert_count\n";
echo "Aktualisiert: $update_count\n";
if ($errors) {
    echo "⚠️ Fehler:\n - " . implode("\n - ", $errors) . "\n";
} else {
    echo "Keine Fehler aufgetreten.\n";
}
?>
