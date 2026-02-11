<?php
/**
 * frzk_unterrichtsleistung_dm.php
 *
 * Dieses Skript erstellt aus der Tabelle `mtr_rueckkopplung_teilnehmer` 
 * aggregierte Leistungskennzahlen pro Teilnehmer und füllt die Tabelle
 * `frzk_unterrichtsleistung_dm`.
 *
 * Quellenbasierte Begründung:
 * - Kognitive Dimensionen (x_kognition): Mitarbeit, Selbstständigkeit, Konzentration, Basiswissen, Vorbereitung
 *   → basierend auf klassischen FRZK-Skalen (vgl. Helmke, 2017; Meyer, 2015)
 * - Soziale Dimensionen (y_sozial): Absprachen, Themenauswahl, Individualisierung, Zielgruppenorientierung
 *   → abgeleitet aus sozial-interaktiven Lernzielen (vgl. Hattie, 2009)
 * - Affektive Dimensionen (z_affektiv): Fleiß, Lernfortschritt
 *   → Motivation, Engagement und Lernfortschritt (vgl. Deci & Ryan, 2000)
 * - Aggregation erfolgt als Mittelwert der Skalenwerte.
 * - Optional: Einbindung von Emotionsdaten möglich, z. B. über _mtr_emotionen
 */

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo -> exec("truncate frzk_unterrichtsleistung_dm");
// --------------------------------------------------------------------------
// Klassische Skalenfelder
// --------------------------------------------------------------------------
$kognitiv_fields = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial_fields   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv_fields = ["fleiss","lernfortschritt"];

// --------------------------------------------------------------------------
// Rückkopplungsdaten laden
// --------------------------------------------------------------------------
echo "Lade Rückkopplungsdaten...\n";
$sql = "SELECT teilnehmer_id, gruppe_id, erfasst_am, "
     . implode(",", array_merge($kognitiv_fields,$sozial_fields,$affektiv_fields))
     . ", emotions FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();
$total = count($rows);
echo "→ $total Datensätze gefunden\n";

// --------------------------------------------------------------------------
// Insert vorbereiten
// --------------------------------------------------------------------------
$insert = $pdo->prepare("
    INSERT INTO frzk_unterrichtsleistung_dm
    (teilnehmer_id, gruppe_id, zeitpunkt,
     x_kognition, y_sozial, z_affektiv,
     bemerkung, emotions)
    VALUES
    (:tid, :gid, :zeitpunkt,
     :x, :y, :z,
     :bem, :emo)
");

// --------------------------------------------------------------------------
// Hauptberechnung
// --------------------------------------------------------------------------
echo "Berechne Unterrichtsleistung...\n";
$jsonData = [];

foreach ($rows as $row) {
    $tid = (int)$row["teilnehmer_id"];
    $gid = isset($row["gruppe_id"]) ? (int)$row["gruppe_id"] : null;
    $zeitpunkt = !empty($row["erfasst_am"]) ? $row["erfasst_am"] : date("Y-m-d H:i:s");

    // --- Skalenwerte extrahieren ---
    $xSkalen = array_map(fn($f)=> (float)$row[$f], $kognitiv_fields);
    $ySkalen = array_map(fn($f)=> (float)$row[$f], $sozial_fields);
    $zSkalen = array_map(fn($f)=> (float)$row[$f], $affektiv_fields);

    // --- Mittelwerte berechnen ---
    $x = count($xSkalen) ? array_sum($xSkalen)/count($xSkalen) : 0;
    $y = count($ySkalen) ? array_sum($ySkalen)/count($ySkalen) : 0;
    $z = count($zSkalen) ? array_sum($zSkalen)/count($zSkalen) : 0;

    // --- Bemerkung + Emotionen ---
    $bem = sprintf("K:%.2f S:%.2f A:%.2f", $x, $y, $z);
    $emotionsString = !empty($row["emotions"]) ? implode(", ", array_map("trim", explode(",", $row["emotions"]))) : "";

    // --- Insert ---
    $insert->execute([
        ":tid"      => $tid,
        ":gid"      => $gid,
        ":zeitpunkt"=> $zeitpunkt,
        ":x"        => $x,
        ":y"        => $y,
        ":z"        => $z,
        ":bem"      => $bem,
        ":emo"      => $emotionsString
    ]);

    // --- JSON-Datensatz ---
    $jsonData[] = [
        "teilnehmer_id" => $tid,
        "gruppe_id"     => $gid,
        "zeitpunkt"     => $zeitpunkt,
        "x_kognition"   => $x,
        "y_sozial"      => $y,
        "z_affektiv"    => $z,
        "bemerkung"     => $bem,
        "emotions"      => $emotionsString
    ];
}

// --------------------------------------------------------------------------
// JSON schreiben
// --------------------------------------------------------------------------
file_put_contents(__DIR__ . "/frzk_unterrichtsleistung_dm.json",
    json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "→ Fertig: $total Datensätze verarbeitet\n";
