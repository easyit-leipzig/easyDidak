<?php
$settings = parse_ini_file('../../ini/settings.ini', TRUE);
$dns = $settings['database']['type'] .
        ':host=' . $settings['database']['host'] .
        ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
        ';dbname=' . $settings['database']['schema'];

try {
    $db_pdo = new \PDO($dns, $settings['database']['username'], $settings['database']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    $db_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
} catch (\PDOException $e) {
    $return = new stdClass();
    $return->command = "connect_error";
    $return->message = $e->getMessage();
    print_r(json_encode($return));
    die;
}

// Funktion zur Umwandlung von Umlauten und Sonderzeichen
function sanitize_column_name($str) {
    $str = str_replace(
        ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'],
        ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'],
        $str
    );
    // Nur Buchstaben, Zahlen und Unterstrich erlauben
    return preg_replace('/[^A-Za-z0-9_]/', '', $str);
}

try {
    // 1) Emotions-Mapping laden
    $stmt = $db_pdo->query("SELECT id, emotion FROM _mtr_emotionen ORDER BY id");
    $emotionsById = [];
    while ($r = $stmt->fetch()) {
        $original = $r['emotion'];
        $sanitized = sanitize_column_name($original);
        $emotionsById[(int)$r['id']] = $sanitized;
    }

    if (empty($emotionsById)) {
        throw new RuntimeException("Tabelle _mtr_emotionen scheint leer zu sein oder konnte nicht gelesen werden.");
    }

    // 2) Spalten aus mtr_emotions laden
    $colsStmt = $db_pdo->query("SHOW COLUMNS FROM `mtr_emotions`");
    $allColumns = $colsStmt->fetchAll();

    $excluded = ['id', 'ue_zuweisung_teilnehmer_id', 'emotions', 'datum', 'teilnehmer_id'];
    $availableCols = [];
    foreach ($allColumns as $c) {
        $field = $c['Field'];
        if (!in_array($field, $excluded, true)) {
            $availableCols[] = $field;
        }
    }

    if (empty($availableCols)) {
        throw new RuntimeException("Keine gültigen Spalten in mtr_emotions gefunden.");
    }

    // 3) Nur Spalten übernehmen, die in der Tabelle auch wirklich existieren
    $emotionColumns = [];
    foreach ($emotionsById as $id => $name) {
        if (in_array($name, $availableCols, true)) {
            $emotionColumns[] = $name;
        } else {
            // Optional: Logging bei fehlenden Spalten
            // echo "Spalte '$name' fehlt in mtr_emotions.\n";
        }
    }

    if (empty($emotionColumns)) {
        throw new RuntimeException("Keine passenden Emotionsspalten in mtr_emotions gefunden.");
    }

    // 4) SQL vorbereiten
    $insertColumns = array_merge(['teilnehmer_id'], $emotionColumns);

    $colList = implode(', ', array_map(function ($c) {
        return "`$c`";
    }, $insertColumns));
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));

    $updateParts = [];
    foreach ($emotionColumns as $col) {
        $updateParts[] = "`$col` = VALUES(`$col`)";
    }
    $updateSql = implode(', ', $updateParts);

    $sql = "INSERT INTO `mtr_emotions` ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
    $insertStmt = $db_pdo->prepare($sql);

    // 5) Rückkopplungsdaten laden
    $rows = $db_pdo->query("SELECT id, teilnehmer_id, emotions FROM mtr_rueckkopplung_teilnehmer")->fetchAll();

    if (empty($rows)) {
        echo "Keine Rückkopplungsdaten gefunden.\n";
        exit;
    }

    // 6) Daten verarbeiten
    $db_pdo->beginTransaction();
    $count = 0;
    foreach ($rows as $row) {
        $ueId = $row['teilnehmer_id'];
        $valEm = $row['emotions'];

        // Initial: alle Flags auf 0
        $flags = array_fill_keys($emotionColumns, 0);

        if ($valEm !== null && trim($valEm) !== '') {
            $parts = preg_split('/\s*,\s*/', trim($valEm));
            $parts = array_filter($parts, function ($v) {
                return $v !== '' && is_numeric($v);
            });
            $parts = array_map('intval', $parts);
            $parts = array_unique($parts);

            foreach ($parts as $id) {
                if (isset($emotionsById[$id])) {
                    $emotionName = $emotionsById[$id];
                    if (in_array($emotionName, $emotionColumns, true)) {
                        $flags[$emotionName] = 1;
                    }
                }
            }
        }

        // Daten für Insert vorbereiten
        $params = array_merge([$ueId], array_values($flags));
        $insertStmt->execute($params);
        $count++;
    }

    $db_pdo->commit();
    echo "Fertig. $count Datensätze verarbeitet.\n";

} catch (Exception $e) {
    if (isset($db_pdo) && $db_pdo->inTransaction()) {
        $db_pdo->rollBack();
    }
    echo "Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Übertragung abgeschlossen.\n";
