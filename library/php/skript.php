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

// INSERT mit Update bei Unique-Key-Konflikt
$columns = "ue_zuweisung_teilnehmer_id, emotions, " . implode(", ", $emotions_list);
$placeholders = ":ueid, :emotions, " . implode(", ", array_map(fn($e) => ":$e", $emotions_list));

$update_parts = [];
foreach ($emotions_list as $emo) {
    $update_parts[] = "$emo = VALUES($emo)";
}
$update_parts[] = "emotions = VALUES(emotions)"; // den Originalstring ebenfalls aktualisieren

$sqlInsert = "
    INSERT INTO emotion_flags ($columns)
    VALUES ($placeholders)
    ON DUPLICATE KEY UPDATE " . implode(", ", $update_parts);

$insertStmt = $db_pdo->prepare($sqlInsert);

// Alle Rückmeldungen abrufen
$sql = "SELECT id, ue_zuweisung_teilnehmer_id, val_emotions FROM mtr_rueckkopplung_teilnehmer";
foreach ($db_pdo->query($sql) as $row) {
    // Alle Flags = 0 setzen
    $flags = array_fill_keys($emotions_list, 0);

    // Emotions-String splitten
    $emotions = array_map('trim', explode(",", $row['val_emotions']));
    foreach ($emotions as $emo) {
        if (in_array($emo, $emotions_list)) {
            $flags[$emo] = 1;
        }
    }

    // Parameter vorbereiten
    $params = array_merge(
        [
            ':ueid'     => (int)$row['ue_zuweisung_teilnehmer_id'],
            ':emotions' => $row['val_emotions']
        ],
        array_combine(
            array_map(fn($e) => ":$e", array_keys($flags)),
            array_values($flags)
        )
    );

    try {
        $insertStmt->execute($params);
    } catch (PDOException $e) {
        echo "❌ Fehler bei Teilnehmer-ID {$row['ue_zuweisung_teilnehmer_id']}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Fertig – emotion_flags mit PDO befüllt/aktualisiert.\n";
?>
