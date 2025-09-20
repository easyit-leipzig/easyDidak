<?php
$dsn = 'mysql:host=localhost;dbname=icas;charset=utf8mb4';
$username = 'root'; // oder dein Benutzername
$password = '';     // oder dein Passwort

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Schritt 1: Alle Einträge mit val_emotions abrufen
    $stmt = $pdo->query("SELECT id, ue_zuweisung_teilnehmer_id, val_emotions FROM mtr_rueckkopplung_teilnehmer WHERE val_emotions IS NOT NULL AND val_emotions != ''");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $teilnehmerId = $row['ue_zuweisung_teilnehmer_id'];
        $emotionIds = array_filter(array_map('intval', explode(',', $row['val_emotions'])));

        if (empty($emotionIds)) {
            continue;
        }

        // Array aller möglichen Emotionen aus _mtr_emotionen holen
        $emotionStmt = $pdo->query("SELECT id, emotion FROM _mtr_emotionen");
        $emotions = $emotionStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => emotion]

        // Template für Insert
        $columns = ['ue_zuweisung_teilnehmer_id', 'emotions'];
        $placeholders = [':ue_zuweisung_teilnehmer_id', ':emotions'];
        $values = [
            ':ue_zuweisung_teilnehmer_id' => $teilnehmerId,
            ':emotions' => implode(',', $emotionIds)
        ];
$columns = [];
$placeholders = [];
$values = [];

        foreach ($emotionIds as $eid) {
            if (isset($emotions[$eid])) {
                $emotionName = $emotions[$eid];
                $columns[] = "`$emotionName`";
                $placeholders[] = ":$emotionName";
                $values[":$emotionName"] = 1;
            }
        }
foreach ($emotionIds as $eid) {
    if (isset($emotionsMap[$eid])) {
        $spaltenname = $emotionsMap[$eid]; // z. B. 'Erfüllung'
        
        // Erzeuge einen gültigen Platzhalternamen (ASCII-only, z. B. 'Erfuellung')
        $paramName = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'],
            $spaltenname
        );
        $paramName = preg_replace('/[^A-Za-z0-9_]/', '', $paramName); // Rest bereinigen

        $columns[] = "`$spaltenname`";
        $placeholders[] = ':' . $paramName;
        $values[':' . $paramName] = 1;
    }
}

        // Dynamisches SQL-Statement
        $sql = "INSERT INTO mtr_emotions (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
echo "<pre>";
print_r($sql);
print_r($values);
echo "</pre>";
        $insertStmt = $pdo->prepare($sql);
        $insertStmt->execute($values);
    }

    echo "Import abgeschlossen.\n";

} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
