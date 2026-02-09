<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/*
 STEP 1: Events mit Kontext-Binning laden
*/
$sql = "
SELECT
    DATE_FORMAT(context_id, '%Y-%m-%d %H:00:00') AS context_bin,
    entity_id,
    activation_value
FROM icas_events
ORDER BY context_bin
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/*
 STEP 2: Gruppieren
*/
$contexts = [];
foreach ($rows as $r) {
    $contexts[$r['context_bin']][] = $r;
}                    

/*
 STEP 3: Relationen aggregieren
*/
$relations = [];
$total_pairs = 0;

foreach ($contexts as $ctx => $events) {
    if (count($events) < 2) {
        continue;
    }

    $n = count($events);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {

            if ($events[$i]['entity_id'] === $events[$j]['entity_id']) {
                continue;
            }

            $a = $events[$i]['entity_id'];
            $b = $events[$j]['entity_id'];

            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }

            $key = $a . '|' . $b;

            if (!isset($relations[$key])) {
                $relations[$key] = 0.0;
            }

            $relations[$key] += min(
                (float)$events[$i]['activation_value'],
                (float)$events[$j]['activation_value']
            );

            $total_pairs++;
        }
    }
}

/*
 STEP 4: Schreiben
*/
$pdo->exec("TRUNCATE TABLE frzk_relations");

$stmt = $pdo->prepare("
INSERT INTO frzk_relations (source_id, target_id, relation_strength)
VALUES (?, ?, ?)
");

foreach ($relations as $key => $value) {
    [$a, $b] = explode('|', $key);
    $stmt->execute([$a, $b, $value]);
    $stmt->execute([$b, $a, $value]);
}

echo "Kontexte: " . count($contexts) . "\n";
echo "Relationen (Paare): $total_pairs\n";
echo "Gespeicherte Relationen: " . count($relations) * 2 . "\n";
