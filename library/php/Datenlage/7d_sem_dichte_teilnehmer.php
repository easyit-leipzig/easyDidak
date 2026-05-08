<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("TRUNCATE TABLE frzk_semantische_dichte_teilnehmer_7d");

/*
    Notenskala:
    1 = sehr positiv
    6 = sehr negativ

    Transformation:
    1 -> +1
    3.5 -> 0
    6 -> -1
*/
function noteToSigned(?float $note): ?float
{
    if ($note === null || $note < 1 || $note > 6) {
        return null;
    }
    return (3.5 - $note) / 2.5;
}

function addWeighted(array &$sum, array &$weight, string $dim, ?float $value, float $w = 1.0): void
{
    if ($value === null) {
        return;
    }
    $sum[$dim] += $value * $w;
    $weight[$dim] += $w;
}

function norm7(array $v): float
{
    $s = 0.0;
    foreach ($v as $x) {
        $s += $x * $x;
    }
    return sqrt($s);
}

$dimensions = [
    'kognition',
    'sozial',
    'affektiv',
    'motivation',
    'methodik',
    'performanz',
    'regulation'
];

/*
    Emotionsdaten laden.
    _mtr_emotionen enthält id, type_name, fine_label, emotion, valenz, aktivierung.
*/
$emotionMap = [];

$stmtEmo = $pdo->query("
    SELECT id, type_name, fine_label, emotion, valenz, aktivierung
    FROM _mtr_emotionen
");

while ($e = $stmtEmo->fetch(PDO::FETCH_ASSOC)) {
    $emotionMap[(int)$e['id']] = [
        'type_name'    => $e['type_name'],
        'fine_label'   => $e['fine_label'],
        'emotion'      => $e['emotion'],
        'valenz'       => (float)$e['valenz'],
        'aktivierung'  => (float)$e['aktivierung']
    ];
}

/*
    Teilnehmerdaten laden.
*/
$stmt = $pdo->query("
    SELECT *
    FROM mtr_rueckkopplung_teilnehmer
    WHERE erfasst_am IS NOT NULL
    ORDER BY erfasst_am ASC, id ASC
");

$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte_teilnehmer_7d
    (
        rueckkopplung_teilnehmer_id,
        ue_id,
        ue_zuweisung_teilnehmer_id,
        teilnehmer_id,
        gruppe_id,
        zeitpunkt,

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

        emotion_ids,
        emotion_valenz,
        emotion_aktivierung,
        emotion_anzahl,

        dominante_dimension,
        dominante_dimension_wert,
        polaritaet_gesamt,
        d_semantisch
    )
    VALUES
    (
        ?,?,?,?,?,?,
        ?,?,?,?,?,?,?,
        ?,?,?,?,?,?,?,
        ?,?,?,?,
        ?,?,?,?
    )
");

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $sum = array_fill_keys($dimensions, 0.0);
    $weight = array_fill_keys($dimensions, 0.0);

    /*
        Numerische Skalenwerte.
        Die Zuordnung ist bewusst mehrdimensional, weil Items im FRZK nicht eindimensional sind.
    */

    $mitarbeit          = noteToSigned($r['mitarbeit'] ?? null);
    $absprachen         = noteToSigned($r['absprachen'] ?? null);
    $selbst             = noteToSigned($r['selbststaendigkeit'] ?? null);
    $konzentration      = noteToSigned($r['konzentration'] ?? null);
    $fleiss             = noteToSigned($r['fleiss'] ?? null);
    $lernfortschritt    = noteToSigned($r['lernfortschritt'] ?? null);
    $beherrscht         = noteToSigned($r['beherrscht_thema'] ?? null);
    $transfer           = noteToSigned($r['transferdenken'] ?? null);
    $basiswissen        = noteToSigned($r['basiswissen'] ?? null);
    $vorbereitet        = noteToSigned($r['vorbereitet'] ?? null);
    $themenauswahl      = noteToSigned($r['themenauswahl'] ?? null);
    $materialien        = noteToSigned($r['materialien'] ?? null);
    $methodenvielfalt   = noteToSigned($r['methodenvielfalt'] ?? null);
    $individualisierung = noteToSigned($r['individualisierung'] ?? null);
    $aufforderung       = noteToSigned($r['aufforderung'] ?? null);
    $zielgruppen        = noteToSigned($r['zielgruppen'] ?? null);

    // Kognition
    addWeighted($sum, $weight, 'kognition', $lernfortschritt, 1.0);
    addWeighted($sum, $weight, 'kognition', $beherrscht, 1.2);
    addWeighted($sum, $weight, 'kognition', $transfer, 1.3);
    addWeighted($sum, $weight, 'kognition', $basiswissen, 1.2);

    // Sozial
    addWeighted($sum, $weight, 'sozial', $mitarbeit, 1.0);
    addWeighted($sum, $weight, 'sozial', $absprachen, 1.1);
    addWeighted($sum, $weight, 'sozial', $aufforderung, 0.8);
    addWeighted($sum, $weight, 'sozial', $zielgruppen, 0.9);

    // Affektiv
    addWeighted($sum, $weight, 'affektiv', $fleiss, 0.7);
    addWeighted($sum, $weight, 'affektiv', $lernfortschritt, 0.5);
    addWeighted($sum, $weight, 'affektiv', $themenauswahl, 0.5);

    // Motivation
    addWeighted($sum, $weight, 'motivation', $fleiss, 1.2);
    addWeighted($sum, $weight, 'motivation', $vorbereitet, 0.8);
    addWeighted($sum, $weight, 'motivation', $themenauswahl, 0.9);
    addWeighted($sum, $weight, 'motivation', $lernfortschritt, 0.7);

    // Methodik
    addWeighted($sum, $weight, 'methodik', $materialien, 1.0);
    addWeighted($sum, $weight, 'methodik', $methodenvielfalt, 1.1);
    addWeighted($sum, $weight, 'methodik', $individualisierung, 1.0);
    addWeighted($sum, $weight, 'methodik', $themenauswahl, 0.7);

    // Performanz
    addWeighted($sum, $weight, 'performanz', $mitarbeit, 0.8);
    addWeighted($sum, $weight, 'performanz', $lernfortschritt, 1.2);
    addWeighted($sum, $weight, 'performanz', $beherrscht, 1.3);
    addWeighted($sum, $weight, 'performanz', $transfer, 1.0);

    // Regulation
    addWeighted($sum, $weight, 'regulation', $selbst, 1.3);
    addWeighted($sum, $weight, 'regulation', $konzentration, 1.2);
    addWeighted($sum, $weight, 'regulation', $vorbereitet, 1.0);
    addWeighted($sum, $weight, 'regulation', $absprachen, 1.0);

    /*
        Emotionen:
        emotions ist kommagetrennt, z. B. "3, 28, 1, 2".
        Valenz wirkt primär affektiv.
        Aktivierung moduliert Motivation.
        negative Valenz + hohe Aktivierung reduziert Regulation.
        soziale Emotionen erhöhen/vermindern Sozialdimension.
    */

    $emotionIds = [];
    $valenzSum = 0.0;
    $aktivSum = 0.0;
    $emotionCount = 0;

    $rawEmotions = trim((string)($r['emotions'] ?? ''));

    if ($rawEmotions !== '') {
        foreach (explode(',', $rawEmotions) as $part) {
            $eid = (int)trim($part);
            if ($eid <= 0 || !isset($emotionMap[$eid])) {
                continue;
            }

            $emotionIds[] = $eid;
            $emo = $emotionMap[$eid];

            $v = (float)$emo['valenz'];       // -1 bis +1 bzw. empirischer Wertebereich
            $a = (float)$emo['aktivierung'];  // 0 bis 1

            $valenzSum += $v;
            $aktivSum += $a;
            $emotionCount++;

            // Affektive Grundwirkung
            addWeighted($sum, $weight, 'affektiv', $v * (0.65 + 0.35 * $a), 1.0);

            // Motivation: positive Aktivierung verstärkt, negative Aktivierung schwächt
            addWeighted($sum, $weight, 'motivation', $v * $a, 0.6);

            // Regulation: negative hochaktivierte Emotionen destabilisieren
            if ($v < 0) {
                addWeighted($sum, $weight, 'regulation', $v * $a, 0.5);
            } else {
                addWeighted($sum, $weight, 'regulation', $v * (1.0 - $a), 0.25);
            }

            // Sozialdimension, falls Emotionstyp oder Label sozial markiert ist
            $label = strtolower(($emo['type_name'] ?? '') . ' ' . ($emo['fine_label'] ?? '') . ' ' . ($emo['emotion'] ?? ''));
            if (str_contains($label, 'sozial') || str_contains($label, 'dankbarkeit') || str_contains($label, 'stolz')) {
                addWeighted($sum, $weight, 'sozial', $v, 0.4);
            }
        }
    }

    $emotionValenz = $emotionCount > 0 ? $valenzSum / $emotionCount : null;
    $emotionAktivierung = $emotionCount > 0 ? $aktivSum / $emotionCount : null;

    /*
        Mittelwerte pro Dimension.
    */
    $V = [];
    foreach ($dimensions as $dim) {
        $V[$dim] = $weight[$dim] > 0 ? $sum[$dim] / $weight[$dim] : 0.0;
    }

    /*
        Normierung wie in der Lehrkraftsicht:
        x_* = Richtungsvektor
        sum_* = Roh-/Mittelwertstruktur
        d_semantisch = Norm des unnormierten Zustands
    */
    $norm = norm7($V);
    $eps = 1e-9;

    $X = [];
    foreach ($dimensions as $dim) {
        $X[$dim] = $V[$dim] / ($norm + $eps);
    }

    $dominantDim = null;
    $dominantVal = 0.0;

    foreach ($dimensions as $dim) {
        if ($dominantDim === null || abs($V[$dim]) > abs($dominantVal)) {
            $dominantDim = $dim;
            $dominantVal = $V[$dim];
        }
    }

    $sumAll = array_sum($V);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    $insert->execute([
        (int)$r['id'],
        (int)$r['ue_id'],
        (int)$r['ue_zuweisung_teilnehmer_id'],
        (int)$r['teilnehmer_id'],
        (int)$r['gruppe_id'],
        $r['erfasst_am'],

        $X['kognition'],
        $X['sozial'],
        $X['affektiv'],
        $X['motivation'],
        $X['methodik'],
        $X['performanz'],
        $X['regulation'],

        $V['kognition'],
        $V['sozial'],
        $V['affektiv'],
        $V['motivation'],
        $V['methodik'],
        $V['performanz'],
        $V['regulation'],

        implode(',', $emotionIds),
        $emotionValenz,
        $emotionAktivierung,
        $emotionCount,

        $dominantDim,
        $dominantVal,
        $polaritaet,
        $norm
    ]);
}

echo "Teilnehmerdaten wurden in die 7-dimensionale FRZK-Struktur überführt.\n";