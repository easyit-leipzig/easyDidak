<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("TRUNCATE frzk_semantische_dichte_lehrer");

/* ============================================================
   FRZK-MODELLPARAMETER
============================================================ */

$params = [
    'beta'   => 0.15,
    'lambda' => 0.25,
    'delta'  => 0.20
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

while ($row = $stmtOp->fetch()) {
    $operators[$row['name']] = $row;
}

/* ============================================================
   SATZ-IDS ERMITTELN
============================================================ */

$stmtSentences = $pdo->query("
    SELECT
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    GROUP BY mtr_rueckkopplung_datenmaske_values_id
    ORDER BY MIN(id) ASC
");

$sentences = $stmtSentences->fetchAll();

$stmtTokens = $pdo->prepare("
    SELECT *
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    WHERE mtr_rueckkopplung_datenmaske_values_id = ?
    ORDER BY id ASC
");

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

/* ============================================================
   SATZWEISE VEKTORBILDUNG
============================================================ */

foreach ($sentences as $meta) {

    $datensatzId = $meta['id_mtr_rueckkopplung_datenmaske'];
    $sentenceId  = $meta['mtr_rueckkopplung_datenmaske_values_id'];

    $stmtTokens->execute([$sentenceId]);
    $tokens = $stmtTokens->fetchAll();

    if (!$tokens) {
        continue;
    }

    $V = array_fill(0, 7, 0.0);

    $sentenceOperators  = [];
    $nextTokenOperators = [];

    $sentenceModulators  = [];
    $nextTokenModulators = [];

    $tokenCount = 0;
    $funktionsklassenUsed = [];

    foreach ($tokens as $token) {

        $lexem = $token['lexem'];

        /* ====================================================
           DIVISOR / FUNKTIONALE NULLTOKEN IGNORIEREN
        ==================================================== */

        if ((int)$token['funktionsklasse_id'] === 0 && $token['wortart'] === 'divisor') {
            continue;
        }

        /* ====================================================
           MODULATOR-ERKENNUNG
           Modulatoren werden NICHT als semantisches Token gezählt.
           Sie wirken auf nachfolgende echte Tokens.
        ==================================================== */

        if ($token['wortart'] === 'modulator') {

            $mod = [
                'id'             => $token['modulator_id'],
                'polaritaet'     => $token['modulator_polaritaet'],
                'intensitaet'    => $token['modulator_intensitaet'],
                'ziel_dimension' => $token['modulator_ziel_dimension']
            ];

            /*
               Standardlogik:
               - globale Intensitätsmarker wie "deutlich", "etwas", "teilweise"
                 wirken auf das nächste Token.
               - dimensionsbezogene Polaritätsmarker können ebenfalls auf das nächste Token wirken.
            */

            $nextTokenModulators[] = $mod;

            continue;
        }

        /* ====================================================
           KLASSISCHE OPERATOR-ERKENNUNG
        ==================================================== */

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

        /* ====================================================
           NUR ECHTE FUNKTIONSKLASSEN-TOKENS VERARBEITEN
        ==================================================== */

        if ((int)$token['funktionsklasse_id'] === 0) {
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

        /* ====================================================
           1. SATZOPERATOREN ANWENDEN
        ==================================================== */

        foreach ($sentenceOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= (float)$op['faktor'];
            }
        }

        /* ====================================================
           2. NEXT-TOKEN-OPERATOREN ANWENDEN
        ==================================================== */

        foreach ($nextTokenOperators as $op) {
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= (float)$op['faktor'];
            }
        }

        $nextTokenOperators = [];

        /* ====================================================
           3. SATZMODULATOREN ANWENDEN
        ==================================================== */

        foreach ($sentenceModulators as $mod) {
            $w = applyFRZKModulator($w, $mod);
        }

        /* ====================================================
           4. NEXT-TOKEN-MODULATOREN ANWENDEN
        ==================================================== */

        foreach ($nextTokenModulators as $mod) {
            $w = applyFRZKModulator($w, $mod);
        }

        $nextTokenModulators = [];

        /* ====================================================
           5. REKURSIVES FRZK-UPDATE
        ==================================================== */

        $V = updateFRZKState($V, $w, $params);
    }

    /* ========================================================
       SATZABSCHLUSS
    ======================================================== */

    $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $V)));
    $epsilon = 1e-5;

    $Vnorm = array_map(
        fn($x) => $x / ($norm + $epsilon),
        $V
    );

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

    $stmtInsert->execute([
        $datensatzId,
        $sentenceId,

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

echo "FRZK-Vektorbildung inkl. Modulatoren vollständig abgeschlossen.\n";





/* ============================================================
   MODULATOR-FUNKTION
============================================================ */

function applyFRZKModulator(array $w, array $mod): array
{
    $dimensionIndex = [
        'kognition'  => 0,
        'sozial'     => 1,
        'affektiv'   => 2,
        'motivation' => 3,
        'methodik'   => 4,
        'performanz' => 5,
        'regulation' => 6
    ];

    $polaritaet  = isset($mod['polaritaet']) ? (int)$mod['polaritaet'] : 1;
    $intensitaet = isset($mod['intensitaet']) ? (float)$mod['intensitaet'] : 1.0;
    $ziel        = $mod['ziel_dimension'] ?? 'global';

    if ($intensitaet == 0) {
        $intensitaet = 1.0;
    }

    /*
       GLOBALER MODULATOR:
       wirkt auf alle Dimensionen.
       Beispiel: deutlich, massiv, etwas, teilweise
    */

    if ($ziel === 'global' || $ziel === null || $ziel === '') {
        for ($i = 0; $i < 7; $i++) {
            $w[$i] *= $intensitaet;
        }

        return $w;
    }

    /*
       DIMENSIONALER MODULATOR:
       wirkt primär auf Ziel-Dimension.
       Polarität kippt nur die Ziel-Dimension.
    */

    if (isset($dimensionIndex[$ziel])) {

        $idx = $dimensionIndex[$ziel];

        $w[$idx] *= $polaritaet * $intensitaet;

        /*
           Leichte systemische Nebenwirkung:
           Die übrigen Dimensionen werden nur über Intensität moduliert,
           nicht polar gekippt.
        */

        for ($i = 0; $i < 7; $i++) {
            if ($i !== $idx) {
                $w[$i] *= max(0.25, min(1.50, $intensitaet * 0.75));
            }
        }
    }

    return $w;
}





/* ============================================================
   FRZK UPDATE-FUNKTION
============================================================ */

function updateFRZKState(array $V, array $w, array $params): array
{
    $beta   = $params['beta'];
    $lambda = $params['lambda'];
    $delta  = $params['delta'];

    $normV = sqrt(array_sum(array_map(fn($x) => $x * $x, $V)));
    $damping = exp(-$beta * $normV);

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

?>