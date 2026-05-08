<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("TRUNCATE frzk_semantische_dichte_lehrer_gesamt_type_1");

$dimensions = [
    'kognition',
    'sozial',
    'affektiv',
    'motivation',
    'methodik',
    'performanz',
    'regulation'
];

$sql = "
SELECT
    m.datum,
    m.lehrkraft_id,
    m.wochentag,
    m.gruppe_id,

    f.id,
    f.ue_id,
    f.id_mtr_rueckkopplung_datenmaske,
    f.mtr_rueckkopplung_datenmaske_values_id,
    f.teilnehmer_id,

    f.x_kognition,
    f.x_sozial,
    f.x_affektiv,
    f.x_motivation,
    f.x_methodik,
    f.x_performanz,
    f.x_regulation,

    f.sum_kognition,
    f.sum_sozial,
    f.sum_affektiv,
    f.sum_motivation,
    f.sum_methodik,
    f.sum_performanz,
    f.sum_regulation,

    f.token_anzahl,
    f.funktionsklassen_anzahl_gesamt,
    f.dominante_dimension,
    f.dominante_dimension_wert,
    f.polaritaet_gesamt,
    f.d_semantisch,
    f.created_at

FROM frzk_semantische_dichte_lehrer f
JOIN mtr_rueckkopplung_datenmaske m
    ON m.id = f.id_mtr_rueckkopplung_datenmaske where (fach='MAT' or fach='PHY')
ORDER BY f.id_mtr_rueckkopplung_datenmaske, f.id
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$groups = [];

foreach ($rows as $row) {
    $key = (int)$row['id_mtr_rueckkopplung_datenmaske'];

    if (!isset($groups[$key])) {
        $groups[$key] = [
            'meta' => [
                'id_mtr_rueckkopplung_datenmaske' => $key,
                'datum' => $row['datum'],
                'lehrkraft_id' => $row['lehrkraft_id'],
                'wochentag' => $row['wochentag'],
                'gruppe_id' => $row['gruppe_id']
            ],
            'rows' => []
        ];
    }

    $groups[$key]['rows'][] = $row;
}

$insert = $pdo->prepare("
INSERT INTO frzk_semantische_dichte_lehrer_gesamt_type_1
(
    id_mtr_rueckkopplung_datenmaske,
    datum,
    lehrkraft_id,
    wochentag,
    gruppe_id,

    satz_anzahl,
    token_anzahl_gesamt,
    funktionsklassen_anzahl_gesamt,

    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation,

    sum_kognition,
    sum_sozial,
    sum_affektiv,
    sum_motivation,
    sum_methodik,
    sum_performanz,
    sum_regulation,

    dominante_dimension,
    dominante_dimension_wert,
    dominante_dimension_anteil,

    polaritaet_gesamt,
    polaritaet_spannung,

    d_semantisch,
    d_semantisch_mean,
    d_semantisch_max,

    streuung_gesamt,
    `kohärenz_index`,
    konflikt_index
)
VALUES
(
    ?,?,?,?,?,
    ?,?,?,
    ?,?,?,?,?,?,?,
    ?,?,?,?,?,?,?,
    ?,?,?,
    ?,?,
    ?,?,?,
    ?,?,?
)
");

foreach ($groups as $group) {

    $meta = $group['meta'];
    $items = $group['rows'];
    $n = count($items);

    if ($n === 0) {
        continue;
    }

    $sum = array_fill_keys($dimensions, 0.0);
    $sumRaw = array_fill_keys($dimensions, 0.0);
    $absEnergy = array_fill_keys($dimensions, 0.0);

    $tokenTotal = 0;
    $funktionsklassenTotal = 0;
    $weightTotal = 0.0;

    $dichteSum = 0.0;
    $dichteMax = 0.0;

    $positive = 0.0;
    $negative = 0.0;
    $neutral = 0.0;

    $vectors = [];

    foreach ($items as $r) {

        $tokenOriginal = (int)$r['token_anzahl'];
        $tokenWeight = max(1, $tokenOriginal);
        $weightTotal += $tokenWeight;

        $vec = [];

        foreach ($dimensions as $dim) {
            $x = (float)$r["x_$dim"];
            $s = (float)$r["sum_$dim"];

            $sum[$dim] += $x * $tokenWeight;
            $sumRaw[$dim] += $s * $tokenWeight;
            $absEnergy[$dim] += abs($s) * $tokenWeight;

            $vec[$dim] = $s;
        }

        $vectors[] = [
            'vector' => $vec,
            'weight' => $tokenWeight
        ];

        $tokenTotal += $tokenOriginal;
        $funktionsklassenTotal += (int)$r['funktionsklassen_anzahl_gesamt'];

        $d = (float)$r['d_semantisch'];
        $dichteSum += $d * $tokenWeight;
        $dichteMax = max($dichteMax, $d);

        $pol = (int)$r['polaritaet_gesamt'];

        if ($pol > 0) {
            $positive += $tokenWeight;
        } elseif ($pol < 0) {
            $negative += $tokenWeight;
        } else {
            $neutral += $tokenWeight;
        }
    }

    $xAgg = [];
    $sumAgg = [];

    foreach ($dimensions as $dim) {
        $xAgg[$dim] = $weightTotal > 0 ? $sum[$dim] / $weightTotal : 0.0;
        $sumAgg[$dim] = $weightTotal > 0 ? $sumRaw[$dim] / $weightTotal : 0.0;
    }

    $normAgg = euclideanNorm($sumAgg);

    $dominanteDimension = null;
    $dominanteWert = 0.0;

    foreach ($sumAgg as $dim => $value) {
        if ($dominanteDimension === null || abs($value) > abs($dominanteWert)) {
            $dominanteWert = $value;
            $dominanteDimension = $dim;
        }
    }

    $totalAbsEnergy = array_sum($absEnergy);

    $dominanzAnteil = ($dominanteDimension !== null && $totalAbsEnergy > 0)
        ? $absEnergy[$dominanteDimension] / $totalAbsEnergy
        : 0.0;

    $sumAll = array_sum($sumAgg);

    $polaritaetGesamt = $sumAll > 0
        ? 1
        : ($sumAll < 0 ? -1 : 0);

    $polaritaetSpannung = ($positive + $negative) > 0
        ? min($positive, $negative) / max($positive, $negative)
        : 0.0;

    $dichteMean = $weightTotal > 0 ? $dichteSum / $weightTotal : 0.0;

    $streuung = meanVectorDistance($vectors, $sumAgg);

    $kohIndex = 1 / (1 + $streuung);

    $konfliktIndex = ($streuung * 0.7) + ($polaritaetSpannung * 0.3);

    $insert->execute([
        $meta['id_mtr_rueckkopplung_datenmaske'],
        $meta['datum'],
        $meta['lehrkraft_id'],
        $meta['wochentag'],
        $meta['gruppe_id'],

        $n,
        $tokenTotal,
        $funktionsklassenTotal,

        $xAgg['kognition'],
        $xAgg['sozial'],
        $xAgg['affektiv'],
        $xAgg['motivation'],
        $xAgg['methodik'],
        $xAgg['performanz'],
        $xAgg['regulation'],

        $sumAgg['kognition'],
        $sumAgg['sozial'],
        $sumAgg['affektiv'],
        $sumAgg['motivation'],
        $sumAgg['methodik'],
        $sumAgg['performanz'],
        $sumAgg['regulation'],

        $dominanteDimension,
        $dominanteWert,
        $dominanzAnteil,

        $polaritaetGesamt,
        $polaritaetSpannung,

        $normAgg,
        $dichteMean,
        $dichteMax,

        $streuung,
        $kohIndex,
        $konfliktIndex
    ]);
}

echo "FRZK-konforme tokengewichtete Aggregation pro id_mtr_rueckkopplung_datenmaske abgeschlossen.\n";


function euclideanNorm(array $vector): float
{
    $sum = 0.0;

    foreach ($vector as $v) {
        $sum += $v * $v;
    }

    return sqrt($sum);
}


function meanVectorDistance(array $vectors, array $center): float
{
    if (count($vectors) === 0) {
        return 0.0;
    }

    $total = 0.0;
    $weightTotal = 0.0;

    foreach ($vectors as $entry) {
        $vec = $entry['vector'];
        $weight = $entry['weight'];

        $diff = [];

        foreach ($center as $dim => $centerValue) {
            $diff[$dim] = ((float)$vec[$dim]) - ((float)$centerValue);
        }

        $total += euclideanNorm($diff) * $weight;
        $weightTotal += $weight;
    }

    return $weightTotal > 0 ? $total / $weightTotal : 0.0;
}