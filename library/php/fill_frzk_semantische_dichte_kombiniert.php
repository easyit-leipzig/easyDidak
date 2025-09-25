<?php
// fill_semantische_dichte_combined.php
// Kombiniert mtr_rueckkopplung_teilnehmer (Skalen + CSV-Emotions)
// und mtr_emotions (Detailtabelle). Ergebnis in frzk_semantische_dichte + JSON.

header('Content-Type: text/plain; charset=utf-8');

// DB-Verbindung
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Gewichtungen für Emotionstypen ---
$weights = [
    "positiv"  =>  1.0,
    "negativ"  => -1.0,
    "kognitiv" =>  0.5
];

// Spalten-Gruppen
$kognitiv = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv = ["fleiss","lernfortschritt"]; // klassische Skalen

// --- Mapping Emotion → Kategorie laden ---
$emotionsMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch()) {
    $emotionsMap[trim($row["emotion"])] = strtolower(trim($row["type_name"]));
}

// --- Rückkopplungsdaten holen ---
$sql = "SELECT teilnehmer_id, gruppe_id, " 
     . implode(",", array_merge($kognitiv,$sozial,$affektiv))
     . ", emotions
        FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// --- Emotionen-Query aus mtr_emotions ---
$emoStmt = $pdo->prepare("SELECT emotions FROM mtr_emotions WHERE teilnehmer_id = :tid");

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte
    (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung)
    VALUES (:teilnehmer_id, :zeitpunkt, :x, :y, :z, :h)
");

$jsonData = [];

foreach ($rows as $row) {
    $tid = (int)$row["teilnehmer_id"];

    // --- Kognition ---
    $werteK = []; foreach ($kognitiv as $c) { if ($row[$c] !== null) $werteK[] = (float)$row[$c]; }
    $x = count($werteK) ? array_sum($werteK)/count($werteK) : 0;

    // --- Sozial ---
    $werteS = []; foreach ($sozial as $c) { if ($row[$c] !== null) $werteS[] = (float)$row[$c]; }
    $y = count($werteS) ? array_sum($werteS)/count($werteS) : 0;

    // --- Affektiv (klassische Skalen) ---
    $werteA = []; foreach ($affektiv as $c) { if ($row[$c] !== null) $werteA[] = (float)$row[$c]; }
    $z_num = count($werteA) ? array_sum($werteA)/count($werteA) : 0;

    // --- Emotionen aus CSV-Feld in mtr_rueckkopplung_teilnehmer ---
    $emotionsScore = 0; $countE = 0;
    if (!empty($row["emotions"])) {
        $emotions = array_map("trim", explode(",", $row["emotions"]));
        foreach ($emotions as $emo) {
            if (isset($emotionsMap[$emo])) {
                $typ = $emotionsMap[$emo];
                if (isset($weights[$typ])) {
                    $emotionsScore += $weights[$typ];
                    $countE++;
                }
            }
        }
    }

    // --- Emotionen zusätzlich aus mtr_emotions ---
    $emoStmt->execute([":tid" => $tid]);
    $emoRows = $emoStmt->fetchAll();
    foreach ($emoRows as $erow) {
        if (!empty($erow["emotions"])) {
            $emos = array_map("trim", explode(",", $erow["emotions"]));
            foreach ($emos as $emo) {
                if (isset($emotionsMap[$emo])) {
                    $typ = $emotionsMap[$emo];
                    if (isset($weights[$typ])) {
                        $emotionsScore += $weights[$typ];
                        $countE++;
                    }
                }
            }
        }
    }

    // --- Kombinierter Emotions-Score ---
    $z_emotions = $countE > 0 ? $emotionsScore/$countE : 0;

    // Gesamter affektiver Wert
    $z = ($z_num + $z_emotions) / 2;

    // --- Gesamtdichte h ---
    $all = array_merge($werteK,$werteS,$werteA);
    if ($countE > 0) $all[] = $z_emotions;
    $h   = count($all) ? array_sum($all)/count($all) : 0;

    // Insert in DB
    $insert->execute([
        ":teilnehmer_id" => $tid,
        ":zeitpunkt"     => date("Y-m-d H:i:s"),
        ":x"             => $x,
        ":y"             => $y,
        ":z"             => $z,
        ":h"             => $h
    ]);

    // JSON-Datensatz
    $jsonData[] = [
        "teilnehmer_id" => $tid,
        "zeitpunkt"     => date("c"),
        "x_kognition"   => $x,
        "y_sozial"      => $y,
        "z_affektiv"    => $z,
        "h_bedeutung"   => $h
    ];
}

// JSON-Datei speichern
file_put_contents(__DIR__ . "/frzk_semantische_dichte.json", json_encode($jsonData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo "Tabelle frzk_semantische_dichte erfolgreich befüllt UND als JSON gespeichert (Kombination aus beiden Tabellen).\n";
