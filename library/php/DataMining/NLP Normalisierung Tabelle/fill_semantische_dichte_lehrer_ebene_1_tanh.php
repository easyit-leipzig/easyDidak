<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("TRUNCATE frzk_semantische_dichte_lehrer");

/* ============================================================
   FRZK-MODELLPARAMETER MIT TANH-STABILISIERUNG
============================================================ */

$params = [
    'beta'   => 0.15,   // Dämpfungsstärke
    'lambda' => 0.25,   // Interaktionsstärke
    'delta'  => 0.20,   // Resonanzstärke
    'alpha'  => 1.20    // Steilheit der tanh-Transformation
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
    $operators[$row['name']] = $row;
}

/* ============================================================
   1. SATZ-IDS ERMITTELN
============================================================ */

$stmtSentences = $pdo->query("
    SELECT
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    GROUP BY mtr_rueckkopplung_datenmaske_values_id
    ORDER BY MIN(id) ASC
");

$sentences = $stmtSentences->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   2. ÜBER JEDE SATZ-ID ITERIEREN
============================================================ */

foreach ($sentences as $meta) {

    $datensatzId = $meta['id_mtr_rueckkopplung_datenmaske'];
    $sentenceId  = $meta['mtr_rueckkopplung_datenmaske_values_id'];

    /* --------------------------------------------------------
       TOKENS DES SATZES LADEN
    -------------------------------------------------------- */

    $stmtTokens = $pdo->prepare("
        SELECT *
        FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
        WHERE mtr_rueckkopplung_datenmaske_values_id = ?
        ORDER BY id ASC
    ");

    $stmtTokens->execute([$sentenceId]);
    $tokens = $stmtTokens->fetchAll(PDO::FETCH_ASSOC);

    if (!$tokens) {
        continue;
    }

    /* --------------------------------------------------------
       3. FRZK-VEKTORBERECHNUNG
    -------------------------------------------------------- */

    $V = array_fill(0, 7, 0.0);

    $sentenceOperators  = [];
    $nextTokenOperators = [];
    $tokenCount = 0;
    $funktionsklassenUsed = [];

    foreach ($tokens as $token) {

        if ((int)$token['funktionsklasse_id'] === 0 && $token['wortart'] === 'divisor') {
            continue;
        }

        $lexem = $token['lexem'];

        /* -------- OPERATOR-ERKENNUNG -------- */

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
                    for ($i = 0; $i < 7; $i++) {
                        $V[$i] *= (float)$op['faktor'];
                    }
                    break;
            }

            continue;
        }

        $tokenCount++;
        $funktionsklassenUsed[$token['funktionsklasse_id']] = true;

        /* ----------------------------------------------------
           ROHVEKTOR DES TOKENS
        ---------------------------------------------------- */

        $wRaw = [
            (float)$token['kognition'],
            (float)$token['sozial'],
            (float)$token['affektiv'],
            (float)$token['motivation'],
            (float)$token['methodik'],
            (float)$token['performanz'],
            (float)$token['regulation']
        ];

        /* ----------------------------------------------------
           TANH-TRANSFORMATION DES TOKENVEKTORS

           Wichtig:
           - Vorzeichen bleiben erhalten.
           - Werte werden begrenzt.
           - extreme Verstärkung wird verhindert.
        ---------------------------------------------------- */

        $w = [];

        for ($i = 0; $i < 7; $i++) {
            $w[$i] = tanh($params['alpha'] * $wRaw[$i]);
        }

        /* -------- SATZOPERATOREN ANWENDEN -------- */

        foreach ($sentenceOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= (float)$op['faktor'];
            }
        }

        /* -------- NEXT-TOKEN-OPERATOREN ANWENDEN -------- */

        foreach ($nextTokenOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= (float)$op['faktor'];
            }
        }

        $nextTokenOperators = [];

        /* -------- REKURSIVES UPDATE -------- */

        $V = updateFRZKState($V, $w, $params);
    }

    /* --------------------------------------------------------
       4. SATZABSCHLUSS
    -------------------------------------------------------- */

    $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $V)));

    $epsilon = 1e-5;

    if ($norm > 0) {
        $Vnorm = array_map(fn($x) => $x / ($norm + $epsilon), $V);
    } else {
        $Vnorm = array_fill(0, 7, 0.0);
    }

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

    foreach ($V as $i => $val) {
        if (abs($val) > abs($maxVal)) {
            $maxVal = $val;
            $dominantDim = $dimensionNames[$i];
        }
    }

    $sumAll = array_sum($V);
    $polaritaet = $sumAll > 0 ? 1 : ($sumAll < 0 ? -1 : 0);

    /* --------------------------------------------------------
       5. SPEICHERN
    -------------------------------------------------------- */

    $stmtInsert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte_lehrer
        (
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,

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
        $datensatzId,
        $sentenceId,

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

echo "FRZK Aggregation mit tanh-Stabilisierung vollständig abgeschlossen.\n";


/* ============================================================
   FRZK UPDATE-FUNKTION MIT TANH-DÄMPFUNG
============================================================ */

function updateFRZKState($V, $w, $params)
{
    $beta   = (float)$params['beta'];
    $lambda = (float)$params['lambda'];
    $delta  = (float)$params['delta'];

    $normV = sqrt(array_sum(array_map(fn($x) => $x * $x, $V)));

    /*
       Alte Variante:
       $damping = exp(-$beta * $normV);

       Neue Variante:
       tanh-basierte Dämpfung.

       Für normV = 0:
       damping = 1

       Für große normV:
       damping nähert sich 0

       Dadurch bleibt die Dämpfung begrenzt,
       ohne exponentielle Kompression zu erzeugen.
    */

    $damping = 1.0 - tanh($beta * $normV);

    $dot = 0.0;
    $normW = 0.0;

    for ($i = 0; $i < 7; $i++) {
        $dot   += $V[$i] * $w[$i];
        $normW += $w[$i] * $w[$i];
    }

    $normW = sqrt($normW);

    $cosine = ($normV > 0 && $normW > 0)
        ? $dot / ($normV * $normW)
        : 0.0;

    /*
       Sicherheitsbegrenzung:
       numerische Rundungsfehler können cosine minimal über +/-1 schieben.
    */

    if ($cosine > 1.0) {
        $cosine = 1.0;
    } elseif ($cosine < -1.0) {
        $cosine = -1.0;
    }

    $Vnew = [];

    for ($i = 0; $i < 7; $i++) {

        /*
           Interaktion bleibt erhalten.
           Resonanz wird zusätzlich tanh-stabilisiert.
        */

        $interaction = $lambda * ($V[$i] * $w[$i]);
        $resonance   = $delta * tanh($cosine) * $w[$i];

        $Vnew[$i] =
            $V[$i]
            + ($w[$i] * $damping)
            - $interaction
            + $resonance;
    }

    return $Vnew;
}

?>