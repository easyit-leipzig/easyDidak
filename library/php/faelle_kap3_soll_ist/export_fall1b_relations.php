<?php
/**
 * Fall 1b – Emergenz durch Kontext-Binning
 * ----------------------------------------
 * context_id enthält Zeitstempel (YYYY-MM-DD HH:MM:SS)
 * → wird explizit in Unix-Zeit konvertiert
 * → zeitliches Fenster-Binning
 * → Ko-Aktivierungen → Relationen
 * → Export als fall1b_relations.json
 */

/* ===============================
 * 0. Datenbankverbindung
 * =============================== */
$pdo = new PDO(
    "mysql:host=localhost;dbname=ICAS;charset=utf8",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

/* ===============================
 * 1. Parameter
 * =============================== */
$windowSizeSeconds = 7200; // 5 Minuten Fenster

/* ===============================
 * 2. Events laden
 * =============================== */
$sql = "
SELECT
    entity_id,
    context_id
FROM icas_events
ORDER BY context_id
";

$events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
 * 3. Kontext-Binning (RELATIV)
 * =============================== */

// frühesten Zeitstempel bestimmen
$timestamps = [];

foreach ($events as $e) {
    $ts = strtotime($e['context_id']);
    if ($ts !== false) {
        $timestamps[] = $ts;
    }
}

if (count($timestamps) === 0) {
    die("Keine gültigen Zeitstempel gefunden.\n");
}

$t0 = min($timestamps); // Referenzzeit

$contexts = [];

foreach ($events as $e) {
    $ts = strtotime($e['context_id']);
    if ($ts === false) {
        continue;
    }

    // RELATIVES Binning
    $bin = floor(($ts - $t0) / $windowSizeSeconds);
    $contexts[$bin][] = $e['entity_id'];
}
/* ===============================
 * 4. Ko-Aktivierungen zählen
 * =============================== */
$relations = [];
$entities  = [];

foreach ($contexts as $bin => $entityList) {
    $unique = array_unique($entityList);

    // mindestens zwei Entitäten nötig
    if (count($unique) < 2) {
        continue;
    }

    foreach ($unique as $i) {
        $entities[$i] = true;

        foreach ($unique as $j) {
            if ($i === $j) {
                continue;
            }

            if (!isset($relations[$i][$j])) {
                $relations[$i][$j] = 0;
            }

            $relations[$i][$j]++;
        }
    }
}

/* ===============================
 * 5. Adjazenzmatrix erzeugen
 * =============================== */
$labels = array_keys($entities);
sort($labels);

$index = array_flip($labels);
$n = count($labels);

// leere Matrix
$matrix = array_fill(0, $n, array_fill(0, $n, 0));

foreach ($relations as $i => $targets) {
    foreach ($targets as $j => $count) {
        $matrix[$index[$i]][$index[$j]] = $count;
    }
}

/* ===============================
 * 6. JSON exportieren
 * =============================== */
$output = [
    "labels" => $labels,
    "relation_matrix" => $matrix
];

file_put_contents(
    __DIR__ . "/fall1b_relations.json",
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✔ fall1b_relations.json erfolgreich erzeugt\n";
