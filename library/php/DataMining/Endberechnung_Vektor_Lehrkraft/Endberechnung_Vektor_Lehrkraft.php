<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* ============================================================
   ZIELTABELLE
============================================================ */

$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_semantische_dichte_lehrer_gesamt (
  id INT(11) NOT NULL AUTO_INCREMENT,
  type int(1)  default 1 not null,
  ue_id INT(11) NOT NULL,
  id_mtr_rueckkopplung_datenmaske INT(11) NOT NULL,
  teilnehmer_id INT(11) NOT NULL,
  x_kognition DOUBLE DEFAULT NULL,
  x_sozial DOUBLE DEFAULT NULL,
  x_affektiv DOUBLE DEFAULT NULL,
  x_motivation DOUBLE DEFAULT NULL,
  x_methodik DOUBLE DEFAULT NULL,
  x_performanz DOUBLE DEFAULT NULL,
  x_regulation DOUBLE DEFAULT NULL,
  sum_kognition DOUBLE DEFAULT NULL,
  sum_sozial DOUBLE DEFAULT NULL,
  sum_affektiv DOUBLE DEFAULT NULL,
  sum_motivation DOUBLE DEFAULT NULL,
  sum_methodik DOUBLE DEFAULT NULL,
  sum_performanz DOUBLE DEFAULT NULL,
  sum_regulation DOUBLE DEFAULT NULL,
  h_kognition INT(11) DEFAULT NULL,
  h_sozial INT(11) DEFAULT NULL,
  h_affektiv INT(11) DEFAULT NULL,
  h_motivation INT(11) DEFAULT NULL,
  h_methodik INT(11) DEFAULT NULL,
  h_performanz INT(11) DEFAULT NULL,
  h_regulation INT(11) DEFAULT NULL,
  token_anzahl INT(11) DEFAULT NULL,
  funktionsklassen_anzahl_gesamt INT(11) DEFAULT NULL,
  dominante_dimension VARCHAR(50) DEFAULT NULL,
  dominante_dimension_wert DOUBLE DEFAULT NULL,
  polaritaet_gesamt INT(11) DEFAULT NULL,
  d_semantisch DOUBLE DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uq_frzk_gesamt (id_mtr_rueckkopplung_datenmaske, teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$pdo->exec("TRUNCATE frzk_semantische_dichte_lehrer_gesamt");

/* ============================================================
   MODELLPARAMETER
============================================================ */

$params = [
    'beta'   => 0.15,
    'lambda' => 0.25,
    'delta'  => 0.20,
    'alpha'  => 0.08,   // Gewicht Tokenanzahl
    'gamma'  => 0.20,   // Gewicht Funktionsklassenbreite
    'tau'    => 1e-12   // Aktivierungsschwelle für h_*
];

/* ============================================================
   ALLE AGGREGATIONSGRUPPEN LADEN
============================================================ */

$stmtGroups = $pdo->query("
    SELECT
        id_mtr_rueckkopplung_datenmaske,
        COALESCE(teilnehmer_id, 0) AS teilnehmer_id,
        COALESCE(MAX(ue_id), 0) AS ue_id
    FROM frzk_semantische_dichte_lehrer
    GROUP BY id_mtr_rueckkopplung_datenmaske, COALESCE(teilnehmer_id, 0)
    ORDER BY id_mtr_rueckkopplung_datenmaske ASC, COALESCE(teilnehmer_id, 0) ASC
");

$groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   SATZDATEN PRO GRUPPE LADEN
============================================================ */

$stmtRows = $pdo->prepare("
    SELECT
        ue_id,
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id,
        teilnehmer_id,

        sum_kognition,
        sum_sozial,
        sum_affektiv,
        sum_motivation,
        sum_methodik,
        sum_performanz,
        sum_regulation,

        token_anzahl,
        funktionsklassen_anzahl_gesamt
    FROM frzk_semantische_dichte_lehrer
    WHERE id_mtr_rueckkopplung_datenmaske = ?
      AND COALESCE(teilnehmer_id, 0) = ?
    ORDER BY mtr_rueckkopplung_datenmaske_values_id ASC, id ASC
");

/* ============================================================
   INSERT
============================================================ */

$stmtInsert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte_lehrer_gesamt (
        ue_id,
        id_mtr_rueckkopplung_datenmaske,
        teilnehmer_id,

        x_kognition, x_sozial, x_affektiv, x_motivation,
        x_methodik, x_performanz, x_regulation,

        sum_kognition, sum_sozial, sum_affektiv, sum_motivation,
        sum_methodik, sum_performanz, sum_regulation,

        h_kognition, h_sozial, h_affektiv, h_motivation,
        h_methodik, h_performanz, h_regulation,

        token_anzahl,
        funktionsklassen_anzahl_gesamt,
        dominante_dimension,
        dominante_dimension_wert,
        polaritaet_gesamt,
        d_semantisch
    )
    VALUES (
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?
    )
");

/* ============================================================
   AGGREGATION
============================================================ */

foreach ($groups as $group) {

    $datensatzId  = (int)$group['id_mtr_rueckkopplung_datenmaske'];
    $teilnehmerId = (int)$group['teilnehmer_id'];
    $ueId         = (int)$group['ue_id'];

    $stmtRows->execute([$datensatzId, $teilnehmerId]);
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        continue;
    }

    // Rekursiver Gesamtzustand
    $U = array_fill(0, 7, 0.0);

    // Häufigkeitszähler h_*
    $H = array_fill(0, 7, 0);

    // Gesamttokenzahl
    $tokenGesamt = 0;

    // Satzanzahl
    $satzAnzahl = 0;

    foreach ($rows as $row) {
        $satzAnzahl++;

        $tokenCount = (int)$row['token_anzahl'];
        $fkCount    = (int)$row['funktionsklassen_anzahl_gesamt'];

        $tokenGesamt += $tokenCount;

        $S = [
            (float)$row['sum_kognition'],
            (float)$row['sum_sozial'],
            (float)$row['sum_affektiv'],
            (float)$row['sum_motivation'],
            (float)$row['sum_methodik'],
            (float)$row['sum_performanz'],
            (float)$row['sum_regulation']
        ];

        // h_*: dimensionsspezifische Aktivierungshäufigkeit
        for ($i = 0; $i < 7; $i++) {
            if (abs($S[$i]) > $params['tau']) {
                $H[$i]++;
            }
        }

        // Satzgewicht
        $omega = 1.0
            + $params['alpha'] * log(1.0 + max(0, $tokenCount))
            + $params['gamma'] * ($fkCount / 7.0);

        $Sweighted = array_map(
            fn($x) => $x * $omega,
            $S
        );

        // Rekursive FRZK-Aggregation zweiter Ordnung
        $U = updateFRZKState($U, $Sweighted, $params);
    }

    // Norm und normierter Vektor
    $norm = euclideanNorm($U);
    $epsilon = 1e-5;

    $X = array_map(
        fn($x) => $x / ($norm + $epsilon),
        $U
    );

    // Gesamtzahl aktivierter Funktionsklassen
    $funktionsklassenGesamt = 0;
    foreach ($H as $hVal) {
        if ($hVal > 0) {
            $funktionsklassenGesamt++;
        }
    }

    // Dominante Dimension
    $dimensionNames = [
        'kognition',
        'sozial',
        'affektiv',
        'motivation',
        'methodik',
        'performanz',
        'regulation'
    ];

    $dominantDim = null;
    $dominantVal = null;

    foreach ($U as $i => $val) {
        if ($dominantVal === null || abs($val) > abs($dominantVal)) {
            $dominantVal = $val;
            $dominantDim = $dimensionNames[$i];
        }
    }

    // Polarität
    $sumAll = array_sum($U);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    // Speichern
    $stmtInsert->execute([
        $ueId,
        $datensatzId,
        $teilnehmerId,

        $X[0], $X[1], $X[2], $X[3], $X[4], $X[5], $X[6],
        $U[0], $U[1], $U[2], $U[3], $U[4], $U[5], $U[6],

        $H[0], $H[1], $H[2], $H[3], $H[4], $H[5], $H[6],

        $tokenGesamt,
        $funktionsklassenGesamt,
        $dominantDim,
        $dominantVal,
        $polaritaet,
        $norm
    ]);
}

echo "FRZK-Gesamtaggregation mit allen Strukturfeldern abgeschlossen.\n";

/* ============================================================
   HILFSFUNKTIONEN
============================================================ */

function euclideanNorm(array $v): float
{
    return sqrt(array_sum(array_map(fn($x) => $x * $x, $v)));
}

function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;
    $n = count($a);

    for ($i = 0; $i < $n; $i++) {
        $dot   += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }

    $normA = sqrt($normA);
    $normB = sqrt($normB);

    if ($normA <= 0.0 || $normB <= 0.0) {
        return 0.0;
    }

    return $dot / ($normA * $normB);
}

function updateFRZKState(array $U, array $S, array $params): array
{
    $beta   = (float)$params['beta'];
    $lambda = (float)$params['lambda'];
    $delta  = (float)$params['delta'];

    $normU = euclideanNorm($U);
    $damping = exp(-$beta * $normU);

    $cosine = cosineSimilarity($U, $S);

    $Unew = [];
    $n = count($U);

    for ($i = 0; $i < $n; $i++) {
        $interaction = $lambda * ($U[$i] * $S[$i]);
        $resonance   = $delta * $cosine * $S[$i];

        $Unew[$i] =
            $U[$i]
            + ($S[$i] * $damping)
            - $interaction
            + $resonance;
    }

    return $Unew;
}