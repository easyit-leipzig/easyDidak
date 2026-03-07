<?php
/**
 * ============================================================
 * FRZK – EBENE 1
 * Nichtlineares rekursives Zustandsmodell
 * Vollständig modellkonform mit Scope-Handling
 *
 * Implementiert:
 * - Rekursive Zustandsdynamik gemäß Kapitel 6.x
 * - Operator-Scope: next_token, sentence, left_context
 * - Mehrfachoperator-Stack
 * - Theoretisch konsistente Rücksetzung
 *
 * Autor: FRZK
 * ============================================================
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
   MODELLPARAMETER (Kapitel 6.x)
   ------------------------------------------------------------
   beta   : Sättigungsdämpfung
   lambda : Interdimensionale Wechselwirkung
   delta  : Resonanzverstärkung
============================================================ */

$params = [
    'beta'   => 0.15,
    'lambda' => 0.25,
    'delta'  => 0.20
];

/* ============================================================
   OPERATOR-TABELLE LADEN
   ------------------------------------------------------------
   name        : lexikalische Form
   typ         : semantischer Operator-Typ
   faktor      : Skalierungs-/Transformationsfaktor
   scope_typ   : next_token | sentence | left_context
============================================================ */

$operators = [];

$stmtOp = $pdo->query("
    SELECT name, typ, faktor, scope_typ
    FROM frzk_operator
    WHERE aktiv = 1
");

while ($row = $stmtOp->fetch(PDO::FETCH_ASSOC)) {
    $operators[$row['name']] = $row;
}

/* ============================================================
   TOKEN LADEN – SATZWEISE SORTIERT
============================================================ */

$stmt = $pdo->query("
    SELECT *
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    ORDER BY mtr_rueckkopplung_datenmaske_values_id ASC, id ASC
");

$currentSentenceId = null;
$sentenceTokens = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $sentenceId = $row['mtr_rueckkopplung_datenmaske_values_id'];

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

echo "FRZK Ebene 1 vollständig modellkonform abgeschlossen.\n";

/* ============================================================
   SATZVERARBEITUNG
============================================================ */

function processSentence($tokens, $operators, $params, $pdo)
{
    /*
     * V = 7-dimensionaler FRZK-Zustandsvektor
     * Reihenfolge:
     * kognition, sozial, affektiv, motivation,
     * methodik, performanz, regulation
     */
    $V = array_fill(0, 7, 0.0);

    /*
     * Operator-Stack
     * Ermöglicht verschachtelte Operatoren
     */
    $sentenceOperators = [];
    $nextTokenOperators = [];

    $tokenCount = 0;
    $funktionsklassenUsed = [];

    foreach ($tokens as $index => $token) {

        if ($token['funktionsklasse_id'] == 0 && $token['wortart'] === 'divisor') {
            continue;
        }

        $lexem = $token['lexem'];

        /* =====================================================
           1. OPERATOR ERKENNEN
        ===================================================== */

        if (isset($operators[$lexem])) {

            $op = $operators[$lexem];

            switch ($op['scope_typ']) {

                case 'sentence':
                    $sentenceOperators[] = $op;
                    break;

                case 'next_token':
                    $nextTokenOperators[] = $op;
                    break;

                case 'left_context':
                    /*
                     * LEFT_CONTEXT:
                     * Transformation des bisherigen Zustandsvektors
                     */
                    for ($i = 0; $i < 7; $i++) {
                        $V[$i] *= $op['faktor'];
                    }
                    break;
            }

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

        /* =====================================================
           2. OPERATOR-ANWENDUNG
           -----------------------------------------------------
           Reihenfolge:
           1. sentence-Operatoren
           2. next_token-Operatoren
        ===================================================== */

        foreach ($sentenceOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= $op['faktor'];
            }
        }

        foreach ($nextTokenOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= $op['faktor'];
            }
        }

        /*
         * next_token gilt nur einmal
         */
        $nextTokenOperators = [];

        /* =====================================================
           3. REKURSIVES UPDATE
           V_{i+1} = V_i + ...
        ===================================================== */

        $V = updateFRZKState($V, $w, $params);
    }

    /* =====================================================
       SATZABSCHLUSS – NORM & META
    ===================================================== */

    $norm = sqrt(array_sum(array_map(fn($x) => $x*$x, $V)));

    $epsilon = 1e-5;
    $Vnorm = array_map(fn($x) => $x / ($norm + $epsilon), $V);

    $dimensionNames = [
        'kognition','sozial','affektiv','motivation',
        'methodik','performanz','regulation'
    ];

    $maxVal = 0;
    $dominantDim = null;

    foreach ($V as $i => $val) {
        if (abs($val) > abs($maxVal)) {
            $maxVal = $val;
            $dominantDim = $dimensionNames[$i];
        }
    }

    $sumAll = array_sum($V);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    $meta = $tokens[0];

    $stmtInsert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte_lehrer
        (
            ue_id, teilnehmer_id, id_mtr_rueckkopplung_datenmaske,
            x_kognition, x_sozial, x_affektiv, x_motivation,
            x_methodik, x_performanz, x_regulation,
            sum_kognition, sum_sozial, sum_affektiv, sum_motivation,
            sum_methodik, sum_performanz, sum_regulation,
            token_anzahl, funktionsklassen_anzahl_gesamt,
            dominante_dimension, dominante_dimension_wert,
            polaritaet_gesamt, d_semantisch
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmtInsert->execute([
        $meta['ue_id'],
        $meta['teilnehmer_id'],
        $meta['id_mtr_rueckkopplung_datenmaske'],
        ...$Vnorm,
        ...$V,
        $tokenCount,
        count($funktionsklassenUsed),
        $dominantDim,
        $maxVal,
        $polaritaet,
        $norm
    ]);
}

/* ============================================================
   UPDATE-FUNKTION – MATHEMATISCHE KERNFORMEL
============================================================ */

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