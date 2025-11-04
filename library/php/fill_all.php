<?php
// fill_semantische_dichte_combined.php
// Dynamische Befüllung der FRZK-Semantikmatrix aus Skalen + Emotionsdaten
// Ergebnis: frzk_semantische_dichte (vollständig inkl. emotions-Feld) + JSON-Datei

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// truncate tables
$pdo->query("truncate frzk_interdependenz")->execute();
$pdo->query("truncate frzk_loops")->execute();
$pdo->query("truncate frzk_operatoren")->execute();
$pdo->query("truncate frzk_reflexion")->execute();
$pdo->query("truncate frzk_semantische_dichte")->execute();
$pdo->query("truncate frzk_transitions")->execute();

// --- Gewichtungen für Emotionstypen ---
$weights = [
    "positiv"  =>  1.0,
    "negativ"  => -1.0,
    "kognitiv" =>  0.5
];

// --- Klassische Skalenfelder ---
$kognitiv_fields = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial_fields   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv_fields = ["fleiss","lernfortschritt"];

// --- Emotion → Kategorie laden ---
$emotionsMap = [];   // z.B. "Freude" => "affektiv"
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch()) {
    $emotionsMap[trim($row["emotion"])] = strtolower(trim($row["type_name"]));
}

// --- Rückkopplungsdaten ---
$sql = "SELECT teilnehmer_id, gruppe_id, "
     . implode(",", array_merge($kognitiv_fields,$sozial_fields,$affektiv_fields))
     . ", emotions FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// --- Emotionen aus Detailtabelle ---
$emoStmt = $pdo->prepare("SELECT emotions FROM mtr_emotions WHERE teilnehmer_id = :tid");

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte
    (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung,
     dh_dt, cluster_id, stabilitaet_score, transitions_marker, bemerkung, emotions)
    VALUES
    (:tid, :zeitpunkt, :x, :y, :z, :h, :dh, :cluster, :stab, :marker, :bem, :emo)
");

// --- Variablen vorbereiten ---
$jsonData = [];
$prevH = [];

// --- Hauptschleife über Teilnehmer ---
foreach ($rows as $row) {
    $tid = (int)$row["teilnehmer_id"];
    $zeitpunkt = date("Y-m-d H:i:s");

    // ----------------------
    // 1. Skalenwerte
    // ----------------------
    $xSkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $kognitiv_fields));
    $ySkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $sozial_fields));
    $zSkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $affektiv_fields));

    // ----------------------
    // 2. Emotionen sammeln (aus beiden Tabellen)
    // ----------------------
    $emotionenGesamt = [];

    // Aus mtr_rueckkopplung_teilnehmer
    if (!empty($row["emotions"])) {
        $csvEmos = array_map("trim", explode(",", $row["emotions"]));
        foreach ($csvEmos as $e) {
            if ($e !== "") $emotionenGesamt[] = $e;
        }
    }

    // Aus mtr_emotions
    $emoStmt->execute([":tid" => $tid]);
    $emoRows = $emoStmt->fetchAll();
    foreach ($emoRows as $erow) {
        if (!empty($erow["emotions"])) {
            $emos = array_map("trim", explode(",", $erow["emotions"]));
            foreach ($emos as $emo) {
                if ($emo !== "") $emotionenGesamt[] = $emo;
            }
        }
    }

    $emotionenGesamt = array_unique($emotionenGesamt);

    // ----------------------
    // 3. Dynamische Emotionseinsortierung nach FRZK-Dimension
    // ----------------------
    $x_emotions = [];
    $y_emotions = [];
    $z_emotions = [];

    foreach ($emotionenGesamt as $emo) {
        if (isset($emotionsMap[$emo])) {
            $typ = $emotionsMap[$emo];
            if (isset($weights[$typ])) {
                $val = $weights[$typ];
                switch ($typ) {
                    case "kognitiv": $x_emotions[] = $val; break;
                    case "sozial":   $y_emotions[] = $val; break;
                    case "affektiv": $z_emotions[] = $val; break;
                    default: break;
                }
            }
        }
    }

    // ----------------------
    // 4. Dimensionen berechnen (Skalen + Emotionen)
    // ----------------------
    $x = count($xSkalen) || count($x_emotions) ? 
         (array_sum($xSkalen) + array_sum($x_emotions)) / max(1, count($xSkalen) + count($x_emotions)) : 0;

    $y = count($ySkalen) || count($y_emotions) ? 
         (array_sum($ySkalen) + array_sum($y_emotions)) / max(1, count($ySkalen) + count($y_emotions)) : 0;

    $z = count($zSkalen) || count($z_emotions) ? 
         (array_sum($zSkalen) + array_sum($z_emotions)) / max(1, count($zSkalen) + count($z_emotions)) : 0;

    // ----------------------
    // 5. Gesamtdichte h
    // ----------------------
    $allVals = array_merge($xSkalen, $ySkalen, $zSkalen, $x_emotions, $y_emotions, $z_emotions);
    $h = count($allVals) ? array_sum($allVals) / count($allVals) : 0;

    // ----------------------
    // 6. dh/dt (Differenz zum vorherigen H)
    // ----------------------
    $dh_dt = isset($prevH[$tid]) ? $h - $prevH[$tid] : 0.0;
    $prevH[$tid] = $h;

    // ----------------------
    // 7. Stabilität
    // ----------------------
    $values = [$x,$y,$z];
    $mean = array_sum($values)/3;
    $variance = array_sum(array_map(fn($v)=>pow($v-$mean,2),$values))/3;
    $stabilitaet = max(0, 1 - $variance);

    // ----------------------
    // 8. Cluster-ID
    // ----------------------
    if ($h < 1.5) $cluster = 1;
    elseif ($h < 2.2) $cluster = 2;
    else $cluster = 3;

    // ----------------------
    // 9. Transition Marker
    // ----------------------
    if (abs($dh_dt) > 0.5) $marker = "Sprung";
    elseif (abs($dh_dt) > 0.2) $marker = "Übergang";
    else $marker = "Stabil";

    // ----------------------
    // 10. Bemerkung & Emotionen
    // ----------------------
    $bem = sprintf("K:%.2f S:%.2f A:%.2f h:%.2f Δh:%.2f", $x, $y, $z, $h, $dh_dt);
    $emotionsString = implode(", ", $emotionenGesamt);

    // ----------------------
    // 11. INSERT in Datenbank
    // ----------------------
    $insert->execute([
        ":tid"      => $tid,
        ":zeitpunkt"=> $zeitpunkt,
        ":x"        => $x,
        ":y"        => $y,
        ":z"        => $z,
        ":h"        => $h,
        ":dh"       => $dh_dt,
        ":cluster"  => $cluster,
        ":stab"     => $stabilitaet,
        ":marker"   => $marker,
        ":bem"      => $bem,
        ":emo"      => $emotionsString
    ]);

    // ----------------------
    // 12. JSON-Datensatz
    // ----------------------
    $jsonData[] = [
        "teilnehmer_id" => $tid,
        "zeitpunkt"     => date("c"),
        "x_kognition"   => $x,
        "y_sozial"      => $y,
        "z_affektiv"    => $z,
        "h_bedeutung"   => $h,
        "dh_dt"         => $dh_dt,
        "cluster_id"    => $cluster,
        "stabilitaet_score" => $stabilitaet,
        "transitions_marker" => $marker,
        "bemerkung"     => $bem,
        "emotions"      => $emotionsString
    ];
}

// ----------------------
// 13. JSON speichern
// ----------------------
file_put_contents(__DIR__ . "/frzk_semantische_dichte.json",
    json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ FRZK-Semantische Dichte erfolgreich befüllt (dynamisch inkl. Emotionen) und JSON erzeugt.\n";
require_once("fill_frzk_interdependenz.php");
require_once("fill_frzk_loops.php");
require_once("fill_frzk_operatoren.php");
require_once("fill_frzk_reflexion.php");
require_once("fill_frzk_transitions.php");
?>
