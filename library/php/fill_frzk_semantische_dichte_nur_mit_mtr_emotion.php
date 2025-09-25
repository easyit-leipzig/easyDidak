<?php
// fill_semantische_dichte_only_mtr_emotions.php
// PHP 8+, PDO
// Nutzt nur mtr_emotions (Detailtabelle) + _mtr_emotionen (Mapping).
// Befüllt frzk_semantische_dichte und speichert zusätzlich JSON.

/* Konfig:
   - DB: localhost, DB 'icas', user 'root' ohne Passwort (anpassen!)
   - Tabellen: siehe Projekt (mtr_rueckkopplung_teilnehmer, mtr_emotions, _mtr_emotionen, frzk_semantische_dichte)
*/

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

try {
    // DB-Verbindung mit Fehler-Mode
    $pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Gewichtungen für Emotionstypen (anpassbar)
    $weights = [
        "positiv"  =>  1.0,
        "negativ"  => -1.0,
        "kognitiv" =>  0.5
    ];

    // Spalten-Gruppen definieren (werden aus mtr_rueckkopplung_teilnehmer gelesen,
    // dort jedoch nur die numerischen Skalen berücksichtigt; emotions nehmen wir aus mtr_emotions)
    $kognitiv = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
    $sozial   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
    $affektiv = ["fleiss","lernfortschritt"]; // klassische Skalen

    // --- Mapping Emotion → Kategorie laden (_mtr_emotionen) ---
    $emotionsMap = [];
    $stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
    while ($row = $stmt->fetch()) {
        $emotionsMap[trim($row["emotion"])] = strtolower(trim($row["type_name"]));
    }

    // --- Rückkopplungsdaten holen (ohne CSV-emotions) ---
    // wir benötigen Teilnehmer-IDs und die numerischen Skalen aus mtr_rueckkopplung_teilnehmer
    $cols = array_merge($kognitiv, $sozial, $affektiv);
    $sql = "SELECT teilnehmer_id, " . implode(",", $cols) . " FROM mtr_rueckkopplung_teilnehmer";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    // --- Emotionen-Query aus mtr_emotions (Detailtabelle) ---
    $emoStmt = $pdo->prepare("SELECT emotions FROM mtr_emotions WHERE teilnehmer_id = :tid");

    // --- Insert vorbereiten ---
    $insert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte
        (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung)
        VALUES (:teilnehmer_id, :zeitpunkt, :x, :y, :z, :h)
    ");

    $jsonData = [];
    $nowSql = (new DateTimeImmutable("now", new DateTimeZone("UTC")))->format('Y-m-d H:i:s');

    // Optional: Begin Transaction
    $pdo->beginTransaction();

    foreach ($rows as $row) {
        $tid = (int)$row['teilnehmer_id'];

        // --- Kognition ---
        $werteK = [];
        foreach ($kognitiv as $c) {
            if (isset($row[$c]) && $row[$c] !== null && $row[$c] !== '') {
                $werteK[] = (float)$row[$c];
            }
        }
        $x = count($werteK) ? array_sum($werteK)/count($werteK) : 0.0;

        // --- Sozial ---
        $werteS = [];
        foreach ($sozial as $c) {
            if (isset($row[$c]) && $row[$c] !== null && $row[$c] !== '') {
                $werteS[] = (float)$row[$c];
            }
        }
        $y = count($werteS) ? array_sum($werteS)/count($werteS) : 0.0;

        // --- Affektiv (klassische Skalen) ---
        $werteA = [];
        foreach ($affektiv as $c) {
            if (isset($row[$c]) && $row[$c] !== null && $row[$c] !== '') {
                $werteA[] = (float)$row[$c];
            }
        }
        $z_num = count($werteA) ? array_sum($werteA)/count($werteA) : 0.0;

        // --- Emotionen ausschliesslich aus mtr_emotions ---
        $emotionsScore = 0.0;
        $countE = 0;

        $emoStmt->execute([":tid" => $tid]);
        $emoRows = $emoStmt->fetchAll();

        foreach ($emoRows as $erow) {
            if (empty($erow["emotions"])) continue;
            // Feld erwartet CSV-String wie "3,28,1" oder "Freude,Neugier"
            $entries = array_map('trim', explode(",", $erow["emotions"]));
            foreach ($entries as $emo) {
                if ($emo === '') continue;
                // Mapping lookup (exakt wie in _mtr_emotionen.emotion)
                if (isset($emotionsMap[$emo])) {
                    $typ = $emotionsMap[$emo];
                    if (isset($weights[$typ])) {
                        $emotionsScore += (float)$weights[$typ];
                        $countE++;
                    }
                } else {
                    // Falls Einträge numerisch sind (IDs), versuchen wir zusätzlich numerische Lookup:
                    // (optional) wenn emotion numeric, versuchen wir, die emotion-id in _mtr_emotionen.emotion zu finden
                    if (is_numeric($emo)) {
                        $emoId = (int)$emo;
                        // one-time prepared lookup
                        static $idLookupStmt = null;
                        if ($idLookupStmt === null) {
                            $idLookupStmt = $pdo->prepare("SELECT type_name FROM _mtr_emotionen WHERE id = :id LIMIT 1");
                        }
                        $idLookupStmt->execute([':id' => $emoId]);
                        $res = $idLookupStmt->fetchColumn();
                        if ($res !== false) {
                            $typ = strtolower(trim($res));
                            if (isset($weights[$typ])) {
                                $emotionsScore += (float)$weights[$typ];
                                $countE++;
                            }
                        }
                    }
                }
            }
        }

        $z_emotions = $countE > 0 ? $emotionsScore / $countE : 0.0;

        // --- Gesamter affektiver Wert: klassische Skalen + emotions ---
        $z = ($z_num + $z_emotions) / 2.0;

        // --- Gesamtdichte h ---
        $all = array_merge($werteK, $werteS, $werteA);
        if ($countE > 0) $all[] = $z_emotions;
        $h = count($all) ? array_sum($all)/count($all) : 0.0;

        // Insert in DB
        $insert->execute([
            ":teilnehmer_id" => $tid,
            ":zeitpunkt"     => $nowSql,
            ":x"             => $x,
            ":y"             => $y,
            ":z"             => $z,
            ":h"             => $h
        ]);

        // JSON-Datensatz anhängen (ISO 8601 Zeitstempel)
        $jsonData[] = [
            "teilnehmer_id" => $tid,
            "zeitpunkt"     => (new DateTimeImmutable("now", new DateTimeZone("UTC")))->format(DATE_ATOM),
            "x_kognition"   => $x,
            "y_sozial"      => $y,
            "z_affektiv"    => $z,
            "h_bedeutung"   => $h
        ];
    }

    // Commit Transaction
    $pdo->commit();

    // JSON speichern
    $jsonFile = __DIR__ . "/frzk_semantische_dichte.json";
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Erfolgreich: frzk_semantische_dichte befüllt (nur mtr_emotions genutzt). JSON: {$jsonFile}\n";

} catch (Throwable $e) {
    // Rollback, falls Transaction offen
    if (isset($pdo) && $pdo instanceof PDO) {
        try { $pdo->rollBack(); } catch (Exception $ex) { /* ignore */ }
    }
    echo "Fehler: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
