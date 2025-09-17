<?php
    $settings = parse_ini_file('../../ini/settings.ini', TRUE);
    $dns = $settings['database']['type'] . 
                ':host=' . $settings['database']['host'] . 
                ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
                ';dbname='. $settings['database']['schema'];
    try {
        $db_pdo = new \PDO( $dns, $settings['database']['username'], $settings['database']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
        $db_pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_pdo -> setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false );
    }
    catch( \PDOException $e ) {
        $return -> command = "connect_error";
        $return -> message = $e->getMessage();
        print_r( json_encode( $return ));
        die;
    }
$emotions_list = [
    "Freude", "Zufriedenheit", "Erfüllung", "Motivation", "Dankbarkeit",
    "Hoffnung", "Stolz", "Selbstvertrauen", "Neugier", "Inspiration",
    "Zugehörigkeit", "Vertrauen", "Spaß", "Sicherheit", "Frustration",
    "Überforderung", "Angst", "Langeweile", "Scham", "Zweifel",
    "Resignation", "Erschöpfung", "Interesse", "Verwirrung", "Unsicherheit",
    "Überraschung", "Erwartung", "Erleichterung"
];

try {

    // 1) Mapping laden: id -> emotion (z.B. 1 => "Freude")
    $stmt = $db_pdo->query("SELECT id, emotion FROM _mtr_emotionen ORDER BY id");
    $emotionsById = [];
    while ($r = $stmt->fetch()) {
        $emotionsById[(int)$r['id']] = $r['emotion'];
    }

    if (empty($emotionsById)) {
        throw new RuntimeException("Tabelle _mtr_emotionen scheint leer zu sein oder konnte nicht gelesen werden.");
    }

    // 2) Spalten aus emotion_flags ermitteln (nur die emotionalen Flag-Spalten verwenden)
    $colsStmt = $db_pdo->query("SHOW COLUMNS FROM `emotion_flags`");
    $allColumns = $colsStmt->fetchAll();

    // Erlaubte Spalten: alle bis auf id, ue_zuweisung_teilnehmer_id, emotions
    $excluded = ['id', 'ue_zuweisung_teilnehmer_id', 'emotions'];
    $availableCols = [];
    foreach ($allColumns as $c) {
        $field = $c['Field'];
        if (!in_array($field, $excluded, true)) {
            $availableCols[] = $field;
        }
    }

    if (empty($availableCols)) {
        throw new RuntimeException("Keine Flag-Spalten in emotion_flags gefunden (außer id / ue_zuweisung_teilnehmer_id / emotions).");
    }

    // 3) Bestimme die Reihenfolge der zu setzenden Emotion-Spalten
    //    Wir nehmen die Emotionsnamen in der Reihenfolge der IDs, aber nur wenn die Spalte in der Tabelle existiert.
    $emotionColumns = [];
    foreach ($emotionsById as $id => $name) {
        if (in_array($name, $availableCols, true)) {
            $emotionColumns[] = $name;
        } else {
            // Spalte existiert nicht -> überspringen (kann passieren wenn name nicht exakt übereinstimmt)
            // optional: Log hier
        }
    }

    if (empty($emotionColumns)) {
        throw new RuntimeException("Keine der Emotionsnamen aus _mtr_emotionen passt zu Spalten in emotion_flags.");
    }

    // columns for INSERT: ue_zuweisung_teilnehmer_id + emotionColumns
    $insertColumns = array_merge(['ue_zuweisung_teilnehmer_id'], $emotionColumns);

    // prepare insert SQL (once)
    $colList = implode(', ', array_map(function($c){ return "`$c`"; }, $insertColumns));
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));

    // ON DUPLICATE KEY UPDATE ... (setze alle Flag-Spalten auf die neuen Werte)
    $updateParts = [];
    foreach ($emotionColumns as $col) {
        $updateParts[] = "`$col` = VALUES(`$col`)";
    }
    $updateSql = implode(', ', $updateParts);

    $sql = "INSERT INTO `emotion_flags` ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
    $insertStmt = $db_pdo->prepare($sql);

    // 4) Alle Rückkopplungen lesen
    $rows = $db_pdo->query("SELECT id, ue_zuweisung_teilnehmer_id, val_emotions FROM mtr_rueckkopplung_teilnehmer")->fetchAll();

    if (empty($rows)) {
        echo "Keine rows in mtr_rueckkopplung_teilnehmer gefunden. Nichts zu tun.\n";
        exit;
    }

    // 5) Transaction starten und verarbeiten
    $db_pdo->beginTransaction();
    $count = 0;
    foreach ($rows as $row) {
        $ueId = $row['ue_zuweisung_teilnehmer_id'];
        $valEm = $row['val_emotions'];

        // Basisflags alle 0
        $flags = array_fill_keys($emotionColumns, 0);

        if ($valEm !== null && trim($valEm) !== '') {
            // sichere Aufteilung: "3, 28,1" -> [3,28,1]
            $parts = preg_split('/\s*,\s*/', trim($valEm));
            $parts = array_filter($parts, function($v){ return $v !== '' && is_numeric($v); });
            $parts = array_map('intval', $parts);
            $parts = array_unique($parts);
            foreach ($parts as $id) {
                if (isset($emotionsById[$id])) {
                    $emotionName = $emotionsById[$id];
                    // nur setzen, wenn emotionName als Spalte existiert (sonst ignorieren)
                    if (in_array($emotionName, $emotionColumns, true)) {
                        $flags[$emotionName] = 1;
                    }
                }
                // unbekannte IDs werden stillschweigend ignoriert
            }
        }

        // Parameter zusammenstellen: ue_zuweisung_teilnehmer_id + flags in korrekter Reihenfolge
        $params = array_merge([$ueId], array_values($flags));

        // Insert / Update ausführen
        $insertStmt->execute($params);
        $count++;
    }

    $db_pdo->commit();
    echo "Fertig. $count Datensätze verarbeitet.\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Fehler: " . $e->getMessage() . "\n";
    exit(1);
}
echo "Übertragung abgeschlossen.\n";