<?php
/*
// fill_semantische_dichte.php
header('Content-Type: text/plain; charset=utf-8');

// DB-Verbindung
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");

// Emotionen-Tabelle laden (Mapping: Emotion → Kategorie)
$emotionsMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $emotionsMap[$row["emotion"]] = strtolower($row["type_name"]);
}

// Spalten-Gruppen definieren
$kognitiv = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv = ["fleiss","lernerfolg"]; // Emotions werden extra berechnet

// Alle Rückkopplungsdaten holen
$sql = "SELECT teilnehmer_id, gruppe_id, " 
     . implode(",", array_merge($kognitiv,$sozial,$affektiv))
     . ", emotions
        FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Insert vorbereiten
$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte
    (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung)
    VALUES (:teilnehmer_id, :zeitpunkt, :x, :y, :z, :h)
");

foreach ($rows as $row) {
    // Kognition
    $werteK = []; foreach ($kognitiv as $c) { if (isset($row[$c])) $werteK[] = (float)$row[$c]; }
    $x = count($werteK) ? array_sum($werteK)/count($werteK) : 0;

    // Sozial
    $werteS = []; foreach ($sozial as $c) { if (isset($row[$c])) $werteS[] = (float)$row[$c]; }
    $y = count($werteS) ? array_sum($werteS)/count($werteS) : 0;

    // Affektiv (klassische Skalen)
    $werteA = []; foreach ($affektiv as $c) { if (isset($row[$c])) $werteA[] = (float)$row[$c]; }
    $z_num = count($werteA) ? array_sum($werteA)/count($werteA) : 0;

    // --- Emotionsfeld parsen ---
    $emotionsScore = 0; $countE = 0;
    if (!empty($row["emotions"])) {
        $emotions = array_map("trim", explode(",", $row["emotions"]));
        foreach ($emotions as $emo) {
            if (isset($emotionsMap[$emo])) {
                switch ($emotionsMap[$emo]) {
                    case "positiv": $emotionsScore += 1; break;
                    case "negativ": $emotionsScore -= 1; break;
                    case "kognitiv": $emotionsScore += 0.5; break;
                }
                $countE++;
            }
        }
    }
    $z_emotions = $countE > 0 ? $emotionsScore/$countE : 0;

    // Gesamter affektiver Wert = Kombination
    $z = ($z_num + $z_emotions) / 2;

    // Gesamtdichte h
    $all = array_merge($werteK,$werteS,$werteA);
    if ($countE > 0) $all[] = $z_emotions;
    $h   = count($all) ? array_sum($all)/count($all) : 0;

    $insert->execute([
        ":teilnehmer_id" => $row["teilnehmer_id"],
        ":zeitpunkt"     => date("Y-m-d H:i:s"),
        ":x"             => $x,
        ":y"             => $y,
        ":z"             => $z,
        ":h"             => $h
    ]);
}

echo "Tabelle frzk_semantische_dichte erfolgreich befüllt.\n";
// kommentar beachten
/*
🔹 Ergebnis

Das Skript zieht sich pro Teilnehmer:

Kognitive Dimension (Durchschnitt über $kognitiv)

Soziale Dimension (Durchschnitt über $sozial)

Affektive Dimension: kombiniert klassische Werte (Fleiß, Lernerfolg) + Emotionen aus CSV

Emotionen werden automatisch kategorisiert nach der _mtr_emotionen-Tabelle.

h_bedeutung = Gesamtmittelwert.


🔹 PHP-Skript mit parametrisierbarer Gewichtung
<?php
*/
// fill_semantische_dichte.php
header('Content-Type: text/plain; charset=utf-8');

// DB-Verbindung
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");

// --- PARAMETER: Gewichtungen für Emotionstypen ---
$weights = [
    "positiv"  =>  1.0,   // z. B. +1
    "negativ"  => -1.0,   // z. B. -1
    "kognitiv" =>  0.5    // z. B. +0.5
];

// Spalten-Gruppen definieren
$kognitiv = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv = ["fleiss","lernfortschritt"]; // Emotions kommen extra dazu

// Emotionen-Tabelle laden (Mapping: Emotion → Kategorie)
$emotionsMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $emotionsMap[$row["emotion"]] = strtolower($row["type_name"]);
}

// Rückkopplungsdaten holen
$sql = "SELECT teilnehmer_id, gruppe_id, " 
     . implode(",", array_merge($kognitiv,$sozial,$affektiv))
     . ", emotions
        FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Insert vorbereiten
$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte
    (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung)
    VALUES (:teilnehmer_id, :zeitpunkt, :x, :y, :z, :h)
");

foreach ($rows as $row) {
    // --- Kognition ---
    $werteK = []; foreach ($kognitiv as $c) { if (isset($row[$c])) $werteK[] = (float)$row[$c]; }
    $x = count($werteK) ? array_sum($werteK)/count($werteK) : 0;

    // --- Sozial ---
    $werteS = []; foreach ($sozial as $c) { if (isset($row[$c])) $werteS[] = (float)$row[$c]; }
    $y = count($werteS) ? array_sum($werteS)/count($werteS) : 0;

    // --- Affektiv (klassische Skalen) ---
    $werteA = []; foreach ($affektiv as $c) { if (isset($row[$c])) $werteA[] = (float)$row[$c]; }
    $z_num = count($werteA) ? array_sum($werteA)/count($werteA) : 0;

    // --- Emotionen aus CSV ---
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
    $z_emotions = $countE > 0 ? $emotionsScore/$countE : 0;

    // Gesamter affektiver Wert = Kombination klassisch + Emotionen
    $z = ($z_num + $z_emotions) / 2;

    // --- Gesamtdichte h ---
    $all = array_merge($werteK,$werteS,$werteA);
    if ($countE > 0) $all[] = $z_emotions;
    $h   = count($all) ? array_sum($all)/count($all) : 0;

    $insert->execute([
        ":teilnehmer_id" => $row["teilnehmer_id"],
        ":zeitpunkt"     => date("Y-m-d H:i:s"),
        ":x"             => $x,
        ":y"             => $y,
        ":z"             => $z,
        ":h"             => $h
    ]);
}

echo "Tabelle frzk_semantische_dichte erfolgreich befüllt (mit Emotionen-Gewichtung).\n";
/*
🔹 Vorteile

Du kannst die Gewichtung der Emotionskategorien einfach oben anpassen:

$weights = [
    "positiv"  =>  2.0,  // z. B. doppelt gewichten
    "negativ"  => -0.5,  // z. B. nur halb so stark
    "kognitiv" =>  1.0
];


Dadurch steuerst du sehr flexibel, wie stark positive/negative/kognitive Emotionen in z_affektiv und h_bedeutung einfließen.
*/