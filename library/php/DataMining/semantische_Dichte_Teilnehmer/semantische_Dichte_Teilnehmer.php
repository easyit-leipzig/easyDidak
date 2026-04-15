<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/*
 * WICHTIG:
 * Dieses Skript arbeitet PARALLEL zur bestehenden Pipeline.
 * Es verändert NICHT:
 *   - frzk_semantische_dichte
 *   - frzk_transitions
 *   - frzk_hubs
 *   - frzk_group_*
 */

$truncateBeforeFill = true;

if ($truncateBeforeFill) {
    $pdo->exec("TRUNCATE TABLE frzk_semantische_dichte_teilnehmer_ue");
}

/* ------------------------------------------------------------
   Emotionen laden
------------------------------------------------------------ */

$emotionMap = [];

$stmtEmotion = $pdo->query("
    SELECT id, emotion, map_field, valenz, aktivierung
    FROM _mtr_emotionen
");

while ($row = $stmtEmotion->fetch(PDO::FETCH_ASSOC)) {
    $emotionMap[(int)$row['id']] = [
        'emotion'      => $row['emotion'] ?? '',
        'map_field'    => $row['map_field'] ?? '',
        'valenz'       => isset($row['valenz']) ? (float)$row['valenz'] : 0.0,
        'aktivierung'  => isset($row['aktivierung']) ? (float)$row['aktivierung'] : 0.0,
    ];
}

/* ------------------------------------------------------------
   Teilnehmer-Rückmeldungen laden
------------------------------------------------------------ */

$stmt = $pdo->query("
    SELECT
        id,
        ue_id,
        ue_zuweisung_teilnehmer_id,
        teilnehmer_id,
        gruppe_id,
        einrichtung_id,
        erfasst_am,
        mitarbeit,
        absprachen,
        selbststaendigkeit,
        konzentration,
        fleiss,
        lernfortschritt,
        beherrscht_thema,
        transferdenken,
        basiswissen,
        vorbereitet,
        themenauswahl,
        materialien,
        methodenvielfalt,
        individualisierung,
        aufforderung,
        zielgruppen,
        emotions,
        bemerkungen
    FROM mtr_rueckkopplung_teilnehmer
    ORDER BY id ASC
");

$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte_teilnehmer_ue
    (
        ue_id,
        ue_zuweisung_teilnehmer_id,
        teilnehmer_id,
        gruppe_id,
        einrichtung_id,
        erfasst_am,

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

        emotionen_ids,
        emotionen_anzahl,
        emotionen_valenz_mittel,
        emotionen_aktivierung_mittel,
        emotionen_details,

        dominante_dimension,
        dominante_dimension_wert,
        polaritaet_gesamt,
        d_semantisch,
        bemerkung
    )
    VALUES
    (
        ?,?,?,?,?,?,
        ?,?,?,?,?,?,?,
        ?,?,?,?,?,?,?,
        ?,?,?,?,?,
        ?,?,?,?,?
    )
");

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Rohwerte
    $mitarbeit         = nz($r['mitarbeit']);
    $absprachen        = nz($r['absprachen']);
    $selbststaendigkeit= nz($r['selbststaendigkeit']);
    $konzentration     = nz($r['konzentration']);
    $fleiss            = nz($r['fleiss']);
    $lernfortschritt   = nz($r['lernfortschritt']);
    $beherrscht_thema  = nz($r['beherrscht_thema']);
    $transferdenken    = nz($r['transferdenken']);
    $basiswissen       = nz($r['basiswissen']);
    $vorbereitet       = nz($r['vorbereitet']);
    $themenauswahl     = nz($r['themenauswahl']);
    $materialien       = nz($r['materialien']);
    $methodenvielfalt  = nz($r['methodenvielfalt']);
    $individualisierung= nz($r['individualisierung']);
    $aufforderung      = nz($r['aufforderung']);
    $zielgruppen       = nz($r['zielgruppen']);

    /* --------------------------------------------------------
       Emotionen aus CSV auflösen
    -------------------------------------------------------- */

    $emotionIds = parseEmotionIds($r['emotions'] ?? '');
    $emotionCount = count($emotionIds);

    $valenzValues = [];
    $aktivierungValues = [];
    $emotionDetails = [];

    foreach ($emotionIds as $eid) {
        if (!isset($emotionMap[$eid])) {
            continue;
        }

        $em = $emotionMap[$eid];
        $valenzValues[] = $em['valenz'];
        $aktivierungValues[] = $em['aktivierung'];

        $emotionDetails[] = json_encode([
            'id' => $eid,
            'emotion' => $em['emotion'],
            'map_field' => $em['map_field'],
            'valenz' => $em['valenz'],
            'aktivierung' => $em['aktivierung'],
        ], JSON_UNESCAPED_UNICODE);
    }

    $emotionValenzMean = mean($valenzValues);
    $emotionAktivierungMean = mean($aktivierungValues);

    /*
     * Emotionsmodulation:
     * neutral, klein, parallel zur Affektachse
     * bei Bedarf später stärker kalibrierbar
     */
    $emotionBoost = 0.0;
    if ($emotionCount > 0) {
        $emotionBoost = (($emotionValenzMean + $emotionAktivierungMean) / 2.0) * 0.15;
    }

    /* --------------------------------------------------------
       7D-Projektion
    -------------------------------------------------------- */

    $sum_kognition  = $lernfortschritt + $beherrscht_thema + $transferdenken + $basiswissen;
    $sum_sozial     = $absprachen + $aufforderung + $zielgruppen;
    $sum_affektiv   = $mitarbeit + $fleiss + $emotionBoost;
    $sum_motivation = $mitarbeit + $fleiss + $themenauswahl;
    $sum_methodik   = $materialien + $methodenvielfalt + $individualisierung;
    $sum_performanz = $beherrscht_thema + $transferdenken + $lernfortschritt;
    $sum_regulation = $selbststaendigkeit + $konzentration + $vorbereitet;

    $x_kognition  = $sum_kognition / 4.0;
    $x_sozial     = $sum_sozial / 3.0;
    $x_affektiv   = $sum_affektiv / 2.0;
    $x_motivation = $sum_motivation / 3.0;
    $x_methodik   = $sum_methodik / 3.0;
    $x_performanz = $sum_performanz / 3.0;
    $x_regulation = $sum_regulation / 3.0;

    $vector = [
        'kognition'  => $x_kognition,
        'sozial'     => $x_sozial,
        'affektiv'   => $x_affektiv,
        'motivation' => $x_motivation,
        'methodik'   => $x_methodik,
        'performanz' => $x_performanz,
        'regulation' => $x_regulation,
    ];

    $dominante_dimension = null;
    $dominante_dimension_wert = null;

    foreach ($vector as $dim => $val) {
        if ($dominante_dimension === null || abs($val) > abs($dominante_dimension_wert)) {
            $dominante_dimension = $dim;
            $dominante_dimension_wert = $val;
        }
    }

    $sumAll = array_sum($vector);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    $d_semantisch = sqrt(
        $x_kognition * $x_kognition +
        $x_sozial * $x_sozial +
        $x_affektiv * $x_affektiv +
        $x_motivation * $x_motivation +
        $x_methodik * $x_methodik +
        $x_performanz * $x_performanz +
        $x_regulation * $x_regulation
    );

    $insert->execute([
        $r['ue_id'],
        $r['ue_zuweisung_teilnehmer_id'],
        $r['teilnehmer_id'],
        $r['gruppe_id'],
        $r['einrichtung_id'],
        $r['erfasst_am'],

        $x_kognition,
        $x_sozial,
        $x_affektiv,
        $x_motivation,
        $x_methodik,
        $x_performanz,
        $x_regulation,

        $sum_kognition,
        $sum_sozial,
        $sum_affektiv,
        $sum_motivation,
        $sum_methodik,
        $sum_performanz,
        $sum_regulation,

        $r['emotions'],
        $emotionCount,
        $emotionValenzMean,
        $emotionAktivierungMean,
        implode(" | ", $emotionDetails),

        $dominante_dimension,
        $dominante_dimension_wert,
        $polaritaet,
        $d_semantisch,
        $r['bemerkungen']
    ]);
}

echo "frzk_semantische_dichte_teilnehmer_ue wurde erfolgreich neu aufgebaut.\n";

/* ------------------------------------------------------------
   Hilfsfunktionen
------------------------------------------------------------ */

function nz($v): float
{
    return $v === null || $v === '' ? 0.0 : (float)$v;
}

function mean(array $values): float
{
    if (!$values) {
        return 0.0;
    }
    return array_sum($values) / count($values);
}

function parseEmotionIds(string $csv): array
{
    $csv = trim($csv);
    if ($csv === '') {
        return [];
    }

    $parts = array_map('trim', explode(',', $csv));
    $ids = [];

    foreach ($parts as $p) {
        if ($p === '' || !is_numeric($p)) {
            continue;
        }
        $ids[] = (int)$p;
    }

    return array_values(array_unique($ids));
}