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

/* ============================================================
   ZIELTABELLE ERWEITERN
============================================================ */

ensureColumn($pdo, 'frzk_semantische_dichte_lehrer', 'operator_count', "
    ALTER TABLE frzk_semantische_dichte_lehrer
    ADD COLUMN operator_count INT DEFAULT 0
");

ensureColumn($pdo, 'frzk_semantische_dichte_lehrer', 'modulator_count', "
    ALTER TABLE frzk_semantische_dichte_lehrer
    ADD COLUMN modulator_count INT DEFAULT 0
");

ensureColumn($pdo, 'frzk_semantische_dichte_lehrer', 'has_operator', "
    ALTER TABLE frzk_semantische_dichte_lehrer
    ADD COLUMN has_operator TINYINT(1) DEFAULT 0
");

ensureColumn($pdo, 'frzk_semantische_dichte_lehrer', 'operator_names', "
    ALTER TABLE frzk_semantische_dichte_lehrer
    ADD COLUMN operator_names TEXT NULL
");

ensureColumn($pdo, 'frzk_semantische_dichte_lehrer', 'modulator_names', "
    ALTER TABLE frzk_semantische_dichte_lehrer
    ADD COLUMN modulator_names TEXT NULL
");

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
   SATZ-IDS ERMITTELN
============================================================ */

$stmtSentences = $pdo->query("
    SELECT
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    WHERE mtr_rueckkopplung_datenmaske_values_id IS NOT NULL
    GROUP BY
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id
    ORDER BY MIN(id) ASC
");

$sentences = $stmtSentences->fetchAll();

echo "Zu verarbeitende Sätze: " . count($sentences) . "\n";

if (count($sentences) === 0) {
    die("ABBRUCH: Keine Satz-IDs in frzk_lexem_datenmaske_lexem_funktionsklasse_weight gefunden.\n");
}

/* ============================================================
   TOKENS INKL. OPERATOR-JOIN LADEN
============================================================ */

$stmtTokens = $pdo->prepare("
    SELECT
        l.*,
        o.name AS op_name,
        o.typ AS op_typ,
        o.faktor AS op_faktor,
        o.scope_typ AS op_scope_typ
    FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight l
    LEFT JOIN frzk_operator o
        ON LOWER(TRIM(l.lexem)) = LOWER(TRIM(o.name))
       AND o.aktiv = 1
    WHERE l.mtr_rueckkopplung_datenmaske_values_id = ?
    ORDER BY l.id ASC
");

/* ============================================================
   INSERT
============================================================ */

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
        d_semantisch,

        operator_count,
        modulator_count,
        has_operator,
        operator_names,
        modulator_names
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

/* ============================================================
   SATZWEISE VEKTORBILDUNG
============================================================ */

$inserted = 0;
$totalOperators = 0;
$totalModulators = 0;

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

    $tokenCount = 0;
    $funktionsklassenUsed = [];

    $operatorCount = 0;
    $modulatorCount = 0;

    $operatorNames = [];
    $modulatorNames = [];

    foreach ($tokens as $token) {

        $lexemRaw = $token['lexem'] ?? '';
        $wortart  = $token['wortart'] ?? '';

        /* ====================================================
           DIVISOR / NULLTOKEN IGNORIEREN
        ==================================================== */

        if ((int)($token['funktionsklasse_id'] ?? 0) === 0 && $wortart === 'divisor') {
            continue;
        }

        /* ====================================================
           OPERATOR-ERKENNUNG DIREKT AUS SQL-JOIN
        ==================================================== */

        $isOperator = !empty($token['op_name']);

        if ($isOperator) {

            $operatorCount++;
            $operatorNames[] = $token['op_name'];

            if ($wortart === 'modulator') {
                $modulatorCount++;
                $modulatorNames[] = $lexemRaw;
            }

            $op = [
                'name'      => $token['op_name'],
                'typ'       => $token['op_typ'],
                'faktor'    => (float)$token['op_faktor'],
                'scope_typ' => $token['op_scope_typ']
            ];

            switch ($op['scope_typ']) {

                case 'sentence':
                    $sentenceOperators[] = $op;
                    break;

                case 'next_token':
                    $nextTokenOperators[] = $op;
                    break;

                case 'left_context':
                    for ($i = 0; $i < 7; $i++) {
                        $V[$i] *= $op['faktor'];
                    }
                    break;
            }

            continue;
        }

        /* ====================================================
           NUR SEMANTISCHE TOKEN VERARBEITEN
        ==================================================== */

        if ((int)($token['funktionsklasse_id'] ?? 0) === 0) {
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
           SATZOPERATOREN ANWENDEN
        ==================================================== */

        foreach ($sentenceOperators as $op) {
            $w = applyOperator($w, $op);
        }

        /* ====================================================
           NEXT-TOKEN-OPERATOREN ANWENDEN
        ==================================================== */

        foreach ($nextTokenOperators as $op) {
            $w = applyOperator($w, $op);
        }

        $nextTokenOperators = [];

        /* ====================================================
           REKURSIVES FRZK-UPDATE
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

    $hasOperator = $operatorCount > 0 ? 1 : 0;

    $operatorNamesText = implode(', ', array_values(array_unique(array_filter($operatorNames))));
    $modulatorNamesText = implode(', ', array_values(array_unique(array_filter($modulatorNames))));

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
        $norm,

        $operatorCount,
        $modulatorCount,
        $hasOperator,
        $operatorNamesText,
        $modulatorNamesText
    ]);

    $inserted++;
    $totalOperators += $operatorCount;
    $totalModulators += $modulatorCount;
}

echo "FRZK-Vektorbildung abgeschlossen.\n";
echo "Geschriebene Datensätze: " . $inserted . "\n";
echo "Operatoren gesamt: " . $totalOperators . "\n";
echo "Modulatoren gesamt: " . $totalModulators . "\n";


/* ============================================================
   OPERATORANWENDUNG
============================================================ */

function applyOperator(array $w, array $op): array
{
    $faktor = isset($op['faktor']) ? (float)$op['faktor'] : 1.0;
    $typ    = $op['typ'] ?? '';

    if ($faktor == 0) {
        $faktor = 1.0;
    }

    switch ($typ) {

        case 'negation':
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= -1 * abs($faktor);
            }
            break;

        case 'intensifier':
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= abs($faktor);
            }
            break;

        case 'dampener':
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= abs($faktor);
            }
            break;

        case 'contrast':
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= -1 * abs($faktor);
            }
            break;

        default:
            for ($i = 0; $i < 7; $i++) {
                $w[$i] *= $faktor;
            }
            break;
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


/* ============================================================
   TABELLENSPALTEN PRÜFEN / ANLEGEN
============================================================ */

function ensureColumn(PDO $pdo, string $table, string $column, string $alterSql): void
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    $stmt->execute([$table, $column]);
    $row = $stmt->fetch();

    if ((int)$row['cnt'] === 0) {
        $pdo->exec($alterSql);
    }
}

?>