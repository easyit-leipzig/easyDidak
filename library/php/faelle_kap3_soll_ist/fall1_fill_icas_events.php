<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/*
 STEP 1: Quelldaten laden
*/
$sql = "
SELECT
    gruppe_id,
    zeitpunkt,
    z_affektiv,
    kohärenz,
    stabilitaet,
    dynamik
FROM frzk_group_emotion
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/*
 STEP 2: Ziel leeren (Reproduzierbarkeit!)
*/
$pdo->exec("TRUNCATE TABLE icas_events");

/*
 STEP 3: Insert vorbereiten
*/
$stmt = $pdo->prepare("
INSERT INTO icas_events
(entity_id, context_id, activation_value, source_field)
VALUES (?, ?, ?, ?)
");

/*
 STEP 4: Zerlegung in atomare Events
*/
foreach ($rows as $r) {
    $entity = 'group_' . $r['gruppe_id'];
    $time   = $r['zeitpunkt'];

    $fields = [
        'z_affektiv' => $r['z_affektiv'],
        'kohärenz'   => $r['kohärenz'],
        'stabilitaet'=> $r['stabilitaet'],
        'dynamik'    => $r['dynamik']
    ];

    foreach ($fields as $field => $value) {
        if ($value !== null) {
            $stmt->execute([
                $entity,
                $time,
                (float)$value,
                $field
            ]);
        }
    }
}

echo "icas_events erfolgreich befüllt.\n";
