<?php
$dsn = 'mysql:host=localhost;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Mapping laden: emotion_id -> map_field
    $mapStmt = $pdo->query("SELECT id, map_field FROM _mtr_emotionen");
    $mapping = [];
    foreach ($mapStmt as $row) {
        $mapping[$row['id']] = $row['map_field'];
    }

    // Rückkopplungseinträge holen
    $stmt = $pdo->query("
        SELECT id, ue_zuweisung_teilnehmer_id, teilnehmer_id, erfasst_am, emotions
        FROM mtr_rueckkopplung_teilnehmer
        WHERE emotions IS NOT NULL AND emotions <> ''
    ");

    $pdo->beginTransaction();

    foreach ($stmt as $row) {
        $ids = array_filter(array_map('trim', explode(',', $row['emotions'])));
        if (empty($ids)) continue;

        // Basiswerte
        $params = [
            ':ueid'  => $row['ue_zuweisung_teilnehmer_id'],
            ':tid'   => $row['teilnehmer_id'],
            ':datum' => $row['erfasst_am'],
            ':emotions' => $row['emotions'],
        ];

        // Grund-Insert
        $setCols = [];
        foreach ($ids as $eid) {
            if (!isset($mapping[$eid])) continue;
            $col = $mapping[$eid];
            $setCols[] = "`$col`=VALUES(`$col`)";
        }

        if (!empty($setCols)) {
            $sql = "INSERT INTO mtr_emotions 
                        (ue_zuweisung_teilnehmer_id, teilnehmer_id, datum, emotions, " 
                        . implode(',', array_map(fn($eid) => '`' . $mapping[$eid] . '`', $ids)) . ")
                    VALUES (:ueid, :tid, :datum, :emotions, " 
                        . rtrim(str_repeat('1,', count($ids)), ',') . ")
                    ON DUPLICATE KEY UPDATE " . implode(', ', $setCols);

            $insertStmt = $pdo->prepare($sql);
            $insertStmt->execute($params);
        }
    }

    $pdo->commit();
    echo "Migration abgeschlossen.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Fehler: " . $e->getMessage() . "\n";
}
