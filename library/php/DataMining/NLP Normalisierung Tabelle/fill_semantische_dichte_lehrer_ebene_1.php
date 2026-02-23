<?php
/**
 * FRZK Ebene 1 – Nichtlineares Zustandsmodell
 * Rekursive semantische Transformation pro Bewertungssatz
 * Gruppierung ausschließlich über id_mtr_rueckkopplung_datenmaske
 */

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* ============================================================
   PARAMETER DES NICHTLINEAREN MODELLS
============================================================ */

$params = [
    'beta'   => 0.15,  // Sättigungsdämpfung
    'lambda' => 0.05,  // Interdimensionale Wechselwirkung
    'delta'  => 0.20   // Resonanzterm
];

/* ============================================================
   OPERATOR-TABELLE LADEN
============================================================ */

$operators = [];

$stmtOp = $pdo->query("
    SELECT name, typ, faktor, scope_typ
    FROM frzk_operator
    WHERE aktiv = 1
");

while ($row = $stmtOp->fetch(PDO::FETCH_ASSOC)) {
    $operators[$row['name']] = [
        'typ'       => $row['typ'],
        'faktor'    => (float)$row['faktor'],
        'scope_typ' => $row['scope_typ']
    ];
}
/* ============================================================
   TOKENS LADEN – SORTIERT NACH SATZ UND REIHENFOLGE
============================================================ */

$stmt = $pdo->query("
    SELECT *
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    ORDER BY id_mtr_rueckkopplung_datenmaske, id
");

$currentSentenceId = null;
$sentenceTokens = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $sentenceId = $row['id_mtr_rueckkopplung_datenmaske'];

    if ($currentSentenceId !== $sentenceId) {

        if (!empty($sentenceTokens)) {
            processSentence($sentenceTokens, $operators, $params, $pdo);
        }

        $sentenceTokens = [];
        $currentSentenceId = $sentenceId;
    }

    $sentenceTokens[] = $row;
}

if (!empty($sentenceTokens)) {
    processSentence($sentenceTokens, $operators, $params, $pdo);
}

echo "FRZK Ebene 1 abgeschlossen.\n";


/* ============================================================
   SATZVERARBEITUNG
============================================================ */

function processSentence($tokens, $operators, $params, $pdo)
{
    $V = array_fill(0, 7, 0.0); // Zustandsvektor

    $activeOperator = null;
    $operatorFactor = 1.0;

    $tokenCount = 0;
    $funktionsklassenUsed = [];

    foreach ($tokens as $token) {

        // Divisor ignorieren
        if ($token['funktionsklasse_id'] == 0 && $token['wortart'] === 'divisor') {
            continue;
        }

        $lexem = $token['lexem'];

        // Operator?
        if (isset($operators[$lexem])) {
            $op = $operators[$lexem];
            $activeOperator = $op['typ'];
            $operatorFactor = $op['faktor'];
            continue;
        }

        $tokenCount++;
        $funktionsklassenUsed[$token['funktionsklasse_id']] = true;

        $w = [
            (float)$token['kognition'],
            (float)$token['sozial'],
            (float)$token['affektiv'],
            (float)$token['motivation'],
            (float)$token['methodik'],
            (float)$token['performanz'],
            (float)$token['regulation']
        ];

        // Operator anwenden
        if ($activeOperator !== null) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= $operatorFactor;
            }
        }

        // Nichtlineares Update
        $V = updateFRZKState($V, $w, $params);
    }

    /* ============================
       Norm und Dichte
    ============================ */

    $norm = 0.0;
    foreach ($V as $value) {
        $norm += $value * $value;
    }
    $norm = sqrt($norm);

    $epsilon = 0.00001;
    $Vnorm = [];

    foreach ($V as $value) {
        $Vnorm[] = $value / ($norm + $epsilon);
    }

    /* ============================
       Dominante Dimension
    ============================ */

    $dimensionNames = [
        'kognition',
        'sozial',
        'affektiv',
        'motivation',
        'methodik',
        'performanz',
        'regulation'
    ];

    $maxVal = 0.0;
    $dominantDim = null;

    foreach ($V as $i => $value) {
        if (abs($value) > abs($maxVal)) {
            $maxVal = $value;
            $dominantDim = $dimensionNames[$i];
        }
    }

    /* ============================
       Polarität
    ============================ */

    $sumAll = array_sum($V);
    $polaritaet = 0;
    if ($sumAll > 0) $polaritaet = 1;
    if ($sumAll < 0) $polaritaet = -1;

    /* ============================
       Speicherung
    ============================ */

    $meta = $tokens[0];

    $stmtInsert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte_lehrer
        (
            ue_id,
            teilnehmer_id,
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
        $meta['ue_id'],
        $meta['teilnehmer_id'],

        $Vnorm[0],
        $Vnorm[1],
        $Vnorm[2],
        $Vnorm[3],
        $Vnorm[4],
        $Vnorm[5],
        $Vnorm[6],

        $V[0],
        $V[1],
        $V[2],
        $V[3],
        $V[4],
        $V[5],
        $V[6],

        $tokenCount,
        count($funktionsklassenUsed),
        $dominantDim,
        $maxVal,
        $polaritaet,
        $norm
    ]);
}


/* ============================================================
   NICHTLINEARE UPDATE-FUNKTION
============================================================ */

function updateFRZKState($V, $w, $params)
{
    $beta   = $params['beta'];
    $lambda = $params['lambda'];
    $delta  = $params['delta'];

    // Norm Zustand
    $normV = 0.0;
    foreach ($V as $val) {
        $normV += $val * $val;
    }
    $normV = sqrt($normV);

    // Sättigungsdämpfung
    $damping = exp(-$beta * $normV);

    $w_damped = [];
    for ($i = 0; $i < 7; $i++) {
        $w_damped[$i] = $w[$i] * $damping;
    }

    // Kosinus
    $dot = 0.0;
    $normW = 0.0;

    for ($i = 0; $i < 7; $i++) {
        $dot += $V[$i] * $w[$i];
        $normW += $w[$i] * $w[$i];
    }

    $normW = sqrt($normW);

    $cosine = 0.0;
    if ($normV > 0 && $normW > 0) {
        $cosine = $dot / ($normV * $normW);
    }

    // Update
    $V_new = [];

    for ($i = 0; $i < 7; $i++) {

        $interaction = $lambda * ($V[$i] * $w[$i]);
        $resonance   = $delta * $cosine * $w[$i];

        $V_new[$i] =
            $V[$i]
            + $w_damped[$i]
            - $interaction
            + $resonance;
    }

    return $V_new;
}