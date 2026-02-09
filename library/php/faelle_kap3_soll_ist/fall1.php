<?php
// --- Datenbankverbindung ---
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// --- relationale Distanzmatrix aufbauen ---
$sql = "
SELECT 
    source_id,
    target_id,
    relation_strength
FROM frzk_relations
ORDER BY source_id, target_id
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Labels sammeln ---
$labels = [];
foreach ($rows as $row) {
    $labels[$row['source_id']] = true;
    $labels[$row['target_id']] = true;
}
$labels = array_keys($labels);
sort($labels);

// --- Indexmapping ---
$index = array_flip($labels);
$n = count($labels);

// --- Matrix initialisieren ---
$matrix = array_fill(0, $n, array_fill(0, $n, 0));

// --- Matrix befüllen ---
foreach ($rows as $row) {
    $i = $index[$row['source_id']];
    $j = $index[$row['target_id']];
    $matrix[$i][$j] = floatval($row['relation_strength']);
    $matrix[$j][$i] = floatval($row['relation_strength']);
}

// --- JSON exportieren ---
$output = [
    "labels" => $labels,
    "relation_matrix" => $matrix
];

file_put_contents(
    "fall1_relations.json",
    json_encode($output, JSON_PRETTY_PRINT)
);

echo "Export abgeschlossen: fall1_relations.json\n";
