<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("TRUNCATE frzk_semantische_dichte_lehrer_nachhilfe_operator");

/* =========================================
   NACHHILFE-PARAMETER
========================================= */

$paramsN = [
    'beta'   => 0.10,
    'lambda' => 0.25,
    'delta'  => 0.30
];

$N_weights = [
    1.00, // kognition
    1.05, // sozial
    1.10, // affektiv
    1.20, // motivation
    1.15, // methodik
    1.00, // performanz
    1.25  // regulation
];

/* =========================================
   DATEN LADEN
========================================= */

$stmt = $pdo->query("
    SELECT *
    FROM frzk_semantische_dichte_lehrer
    ORDER BY id ASC
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   ITERATION
========================================= */

foreach ($data as $row) {

    $V = [
        (float)$row['sum_kognition'],
        (float)$row['sum_sozial'],
        (float)$row['sum_affektiv'],
        (float)$row['sum_motivation'],
        (float)$row['sum_methodik'],
        (float)$row['sum_performanz'],
        (float)$row['sum_regulation']
    ];

    /* -------------------------------------
       NACHHILFE-TRANSFORMATION
    ------------------------------------- */

    $Vn = [];

    for ($i = 0; $i < 7; $i++) {
        $Vn[$i] = $V[$i] * $N_weights[$i];
    }

    /* -------------------------------------
       FRZK UPDATE MIT NEUEN PARAMETERN
    ------------------------------------- */

    $V_new = updateFRZKState($V, $Vn, $paramsN);

    /* -------------------------------------
       NORM & META
    ------------------------------------- */

    $norm = sqrt(array_sum(array_map(fn($x) => $x*$x, $V_new)));
    $epsilon = 1e-5;

    $Vnorm = array_map(fn($x) => $x / ($norm + $epsilon), $V_new);

    $dimensionNames = [
        'kognition','sozial','affektiv',
        'motivation','methodik','performanz','regulation'
    ];

    $maxVal = 0;
    $dominantDim = null;

    foreach ($V_new as $i => $val) {
        if (abs($val) > abs($maxVal)) {
            $maxVal = $val;
            $dominantDim = $dimensionNames[$i];
        }
    }

    $sumAll = array_sum($V_new);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    /* -------------------------------------
       INSERT
    ------------------------------------- */

    $stmtInsert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte_lehrer_nachhilfe_operator
        (
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,

            x_kognition, x_sozial, x_affektiv, x_motivation,
            x_methodik, x_performanz, x_regulation,

            sum_kognition, sum_sozial, sum_affektiv, sum_motivation,
            sum_methodik, sum_performanz, sum_regulation,

            token_anzahl,
            funktionsklassen_anzahl_gesamt,

            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmtInsert->execute([
        $row['id_mtr_rueckkopplung_datenmaske'],
        $row['mtr_rueckkopplung_datenmaske_values_id'],

        ...$Vnorm,
        ...$V_new,

        $row['token_anzahl'],
        $row['funktionsklassen_anzahl_gesamt'],

        $dominantDim,
        $maxVal,
        $polaritaet,
        $norm
    ]);
}

echo "Nachhilfe-Operator vollständig berechnet.\n";

/* =========================================
   UPDATE-FUNKTION (IDENTISCH)
========================================= */

function updateFRZKState($V, $w, $params)
{
    $beta   = $params['beta'];
    $lambda = $params['lambda'];
    $delta  = $params['delta'];

    $normV = sqrt(array_sum(array_map(fn($x) => $x*$x, $V)));
    $damping = exp(-$beta * $normV);

    $dot = 0;
    $normW = 0;

    for ($i = 0; $i < 7; $i++) {
        $dot   += $V[$i] * $w[$i];
        $normW += $w[$i] * $w[$i];
    }

    $normW = sqrt($normW);

    $cosine = ($normV > 0 && $normW > 0)
        ? $dot / ($normV * $normW)
        : 0;

    $Vnew = [];

    for ($i = 0; $i < 7; $i++) {

        $interaction = $lambda * ($V[$i] * $w[$i]);
        $resonance   = $delta  * $cosine * $w[$i];

        $Vnew[$i] =
            $V[$i]
            + ($w[$i] * $damping)
            - $interaction
            + $resonance;
    }

    return $Vnew;
}