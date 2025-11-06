<?php
/**
 * aggregate_persoenlichkeit.php (robustierte Version)
 * - fängt NULLs ab
 * - ersetzt fehlende Metriken durch Mittel vorhandener Metriken (oder Default 0.5)
 * - skaliert sauber von metr [0..1] -> note-scale [1..6] per formula 1 + metr*5
 * - clamped auf [1.0, 6.0]
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function clamp($v) {
    return max(1.0, min(6.0, round((float)$v, 2)));
}

// Aggregation: durchschnittliche metriken pro Teilnehmer/Monat + avg_note falls vorhanden
$sql = "
SELECT 
    d.teilnehmer_id,
    DATE_FORMAT(d.datum, '%Y-%m-01') AS monatsdatum,
    COUNT(*) AS n,
    AVG(d.metr_kognition)   AS avg_kog,
    AVG(d.metr_sozial)      AS avg_soz,
    AVG(d.metr_affektiv)    AS avg_aff,
    AVG(d.metr_metakog)     AS avg_meta,
    AVG(d.metr_kohärenz)    AS avg_koh,
    AVG(p.note)             AS avg_note
FROM mtr_rueckkopplung_datenmaske d
LEFT JOIN mtr_persoenlichkeit p 
       ON p.teilnehmer_id = d.teilnehmer_id
      AND DATE_FORMAT(p.datum, '%Y-%m') = DATE_FORMAT(d.datum, '%Y-%m')
GROUP BY d.teilnehmer_id, monatsdatum
";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Gefundene Teilnehmer-Monats-Gruppen: " . count($data) . "\n\n";

$selExists = $pdo->prepare("SELECT id FROM mtr_persoenlichkeit WHERE teilnehmer_id = :tid AND datum = :datum");
$insert = $pdo->prepare("
INSERT INTO mtr_persoenlichkeit (
    teilnehmer_id, datum,
    offenheit_erfahrungen, gewissenhaftigkeit, Extraversion, vertraeglichkeit,
    zielorientierung, lernfaehigkeit, anpassungsfaehigkeit, soziale_interaktion,
    metakognition, stressbewaeltigung, bedeutungsbildung, belastbarkeit,
    problemloesefaehigkeit, kreativitaet_innovation, ko_kreationsfaehigkeit,
    resonanzfaehigkeit, handlungsdichte, performanz_effizienz, basiswissen, note
) VALUES (
    :tid, :datum,
    :off, :gew, :ext, :ver,
    :ziel, :lern, :anp, :sozial,
    :meta, :stress, :bedeu, :bel,
    :prob, :kreat, :ko, :reso,
    :hand, :perf, :basis, :note
)
");
$update = $pdo->prepare("
UPDATE mtr_persoenlichkeit SET
    offenheit_erfahrungen = :off,
    gewissenhaftigkeit = :gew,
    Extraversion = :ext,
    vertraeglichkeit = :ver,
    zielorientierung = :ziel,
    lernfaehigkeit = :lern,
    anpassungsfaehigkeit = :anp,
    soziale_interaktion = :sozial,
    metakognition = :meta,
    stressbewaeltigung = :stress,
    bedeutungsbildung = :bedeu,
    belastbarkeit = :bel,
    problemloesefaehigkeit = :prob,
    kreativitaet_innovation = :kreat,
    ko_kreationsfaehigkeit = :ko,
    resonanzfaehigkeit = :reso,
    handlungsdichte = :hand,
    performanz_effizienz = :perf,
    basiswissen = :basis,
    note = :note
WHERE teilnehmer_id = :tid AND datum = :datum
");

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($data as $r) {
    $tid = (int)$r['teilnehmer_id'];
    $datum = $r['monatsdatum'];
    $n = (int)$r['n'];

    // Wenn keine tatsächlichen Rückmeldungen, skip (sollte nicht passieren)
    if ($n === 0) { $skipped++; continue; }

    // Rohwerte (können NULL sein)
    $raw = [
        'avg_kog' => isset($r['avg_kog']) ? (float)$r['avg_kog'] : null,
        'avg_soz' => isset($r['avg_soz']) ? (float)$r['avg_soz'] : null,
        'avg_aff' => isset($r['avg_aff']) ? (float)$r['avg_aff'] : null,
        'avg_meta'=> isset($r['avg_meta'])? (float)$r['avg_meta'] : null,
        'avg_koh' => isset($r['avg_koh']) ? (float)$r['avg_koh'] : null
    ];

    // Prüfe ob ALLE Metriken NULL -> skip (vermeidet 1er-Fälle)
    $nonNull = array_filter($raw, function($v){ return $v !== null; });
    if (count($nonNull) === 0) {
        $skipped++;
        continue;
    }

    // Fallback: fehlende Werte durch Mittel der vorhandenen Metriken
    $meanAvail = array_sum($nonNull) / count($nonNull);
    foreach ($raw as $k => $v) {
        if ($v === null) $raw[$k] = $meanAvail; // sinnvoller Fallback
    }

    // Note: falls avg_note NULL -> set default 4.0 (mittlere Note)
    $note = isset($r['avg_note']) && $r['avg_note'] !== null ? (float)$r['avg_note'] : 4.0;
    // clamp note to [1..6]
    $note = max(1.0, min(6.0, $note));

    // Faktor (du kannst die Formel anpassen). Hier: bessere Note -> leicht höhere Skala.
    $faktor = 1 + (4.0 - $note) * 0.05; // bei note=4 -> faktor=1

    // Mapping: metr [0..1] -> scale [1..6] using 1 + metr*5, then apply faktor, then clamp
    $scale = function($metr) use ($faktor) {
        // ensure metr in [0..1]
        $m = max(0.0, min(1.0, (float)$metr));
        $val = 1.0 + $m * 5.0;        // now in [1..6]
        $val *= $faktor;             // apply weighting
        return clamp($val);
    };

    // Now compute personality fields using mapped values (examples)
    $off   = $scale($raw['avg_kog']);                              // Offenheit ~ Kognition
    $gew   = $scale($raw['avg_meta']);                             // Gewissenhaftigkeit ~ Metakog
    $ext   = $scale($raw['avg_aff']);                              // Extraversion ~ Affektiv
    $ver   = $scale($raw['avg_soz']);                              // Verträglichkeit ~ Sozial
    $ziel  = $scale($raw['avg_meta']);                             // Zielorientierung ~ Metakog
    $lern  = clamp((($scale($raw['avg_kog']) + $scale($raw['avg_meta']))/2)); // Lernfähigkeit
    $anp   = $scale($raw['avg_koh']);                              // Anpassungsfähigkeit ~ Kohärenz
    $sozial= $scale($raw['avg_soz']);
    $meta  = $scale($raw['avg_meta']);
    // stressbewaeltigung: interpretative mapping (high affektiv -> lower stress control)
    $stressRaw = $raw['avg_aff']; // 0..1
    $stressVal = 1.0 + (1.0 - $stressRaw) * 5.0; // if aff high (1) => 1.0; if aff low (0) => 6.0
    $stress = clamp($stressVal * $faktor);

    $bedeu = $scale($raw['avg_koh']);
    $bel   = clamp((($scale($raw['avg_aff']) + $scale($raw['avg_meta']))/2));
    $prob  = $scale($raw['avg_koh']);
    $kreat = $scale($raw['avg_koh']);
    $ko    = $scale($raw['avg_soz']);
    $reso  = $scale($raw['avg_aff']);
    $hand  = clamp((($scale($raw['avg_meta']) + $scale($raw['avg_koh']))/2));
    $perf  = clamp((($scale($raw['avg_kog']) + $scale($raw['avg_koh']))/2));
    $basis = clamp(1.0 + $raw['avg_kog'] * 5.0); // direct mapping without faktor for basiswissen

    // Prepare params
    $params = [
        ':off'=>$off,':gew'=>$gew,':ext'=>$ext,':ver'=>$ver,
        ':ziel'=>$ziel,':lern'=>$lern,':anp'=>$anp,':sozial'=>$sozial,
        ':meta'=>$meta,':stress'=>$stress,':bedeu'=>$bedeu,':bel'=>$bel,
        ':prob'=>$prob,':kreat'=>$kreat,':ko'=>$ko,':reso'=>$reso,
        ':hand'=>$hand,':perf'=>$perf,':basis'=>$basis,':note'=>$note,
        ':tid'=>$tid,':datum'=>$datum
    ];

    // Existenz prüfen
    $selExists->execute([':tid'=>$tid, ':datum'=>$datum]);
    $exists = $selExists->fetchColumn();

    if ($exists) {
        $update->execute($params);
        $updated++;
    } else {
        $insert->execute($params);
        $inserted++;
    }

    // Debug kurz ausgeben (entfernbar)
    echo sprintf("tid=%d month=%s n=%d avg_kog=%s avg_soz=%s avg_aff=%s avg_meta=%s avg_koh=%s avg_note=%s -> off=%.2f\n",
        $tid, $datum, $n,
        var_export($r['avg_kog'], true), var_export($r['avg_soz'], true),
        var_export($r['avg_aff'], true), var_export($r['avg_meta'], true),
        var_export($r['avg_koh'], true), var_export($r['avg_note'], true),
        $off
    );
}

echo "\nFertig. Inserted: $inserted, Updated: $updated, Skipped(empty metrics): $skipped\n";
