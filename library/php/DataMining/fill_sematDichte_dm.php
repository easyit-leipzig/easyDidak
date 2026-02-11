<?php
/**
 * -------------------------------------------------------------------------
 * Data Mining für semantische Dichte und Unterrichtsleistung
 * Tabellen: frzk_semantische_dichte_dm, frzk_unterrichtsleistung_dm
 * -------------------------------------------------------------------------
 *
 * Quellenbasierte Gewichtungen und Modelle:
 *
 * 1. Klassische Skalen:
 *    - Kognition (x): Mitarbeit, Selbstständigkeit, Konzentration, Basiswissen, Vorbereitung
 *      Basisgewicht: 1.0
 *      Quellen: Bloom (1956), Anderson & Krathwohl (2001), Hattie (2009)
 *    - Sozial (y): Absprachen, Themenauswahl, Individualisierung, Zielgruppen
 *      Basisgewicht: 0.8
 *      Quellen: Johnson & Johnson (1989), Fredrickson (2001)
 *    - Affektiv (z): Fleiß, Lernfortschritt
 *      Basisgewicht: 0.7
 *      Quellen: Krathwohl et al. (1964), Pekrun et al. (2002)
 *
 * 2. Emotionen (_mtr_emotionen):
 *    - Valenz: [-1.0, +1.0] nach Russell (1980)
 *    - Aktivierung: [0, 1] nach Russell (1980), Pekrun et al. (2002)
 *    - Positive Emotionen fördern Kognition & Engagement (Fredrickson, 2001)
 *    - Negative Emotionen dämpfen Leistung (Pekrun, 2006)
 *
 * 3. Transitionen / dh/dt:
 *    - Δh <0.05: Homöostatisch ⚖️ (resilient), Thelen & Smith (1994)
 *    - 0.05–0.15: Adaptiv 🌱
 *    - 0.15–0.30: Koordinativ 🔄
 *    - 0.30–0.50: Transformativ 🌊
 *    - 0.50–0.80: Perturbativ ⚡
 *    - >0.80: Kollapsiv 💥
 *    - Stabilität und dh/dt kombinieren → marker Symbolisierung
 *
 * 4. Unterrichtsleistung:
 *    - Aggregierter Engagement-Score = gewichtete Mittelwerte Skalen + Emotionen
 *    - Quellen: Fredricks, Blumenfeld & Paris (2004), Pekrun et al. (2002)
 *
 * Hinweis: Alte Tabellen bleiben unverändert. _dm Tabellen dienen dem experimentellen
 * DataMining.
 */
header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec("truncate frzk_semantische_dichte_dm");
// --------------------------------------------------------------------------
// Einstellungen / Gewichtungen
// --------------------------------------------------------------------------
$weights = [
    "positiv"  =>  1.0,
    "negativ"  => -1.0,
    "kognitiv" =>  0.5
];

// --------------------------------------------------------------------------
// Klassische Skalenfelder
// --------------------------------------------------------------------------
$kognitiv_fields = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial_fields   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv_fields = ["fleiss","lernfortschritt"];


// --------------------------------------------------------------------------
// Emotion → Kategorie & Valenz/Aktivierung laden
// --------------------------------------------------------------------------
echo "Lade Emotionsdaten...\n";
$emotionsMap = [];
$stmt = $pdo->query("SELECT id, emotion, type_name, valenz, aktivierung, map_field FROM _mtr_emotionen");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $emotionsMap[$row["id"]] = [
        "emotion"     => $row["emotion"],
        "type_name"   => strtolower($row["type_name"]),
        "valenz"      => (float)$row["valenz"],
        "aktivierung" => (float)$row["aktivierung"],
        "map_field"   => $row["map_field"]
    ];
}

// --------------------------------------------------------------------------
// Rückkopplungsdaten laden
// --------------------------------------------------------------------------
echo "Lade Rückkopplungsdaten...\n";
$sql = "SELECT teilnehmer_id, gruppe_id, erfasst_am, "
     . implode(",", array_merge($kognitiv_fields,$sozial_fields,$affektiv_fields))
     . ", emotions FROM mtr_rueckkopplung_teilnehmer";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($rows);
echo "→ $total Datensätze gefunden\n";

// --------------------------------------------------------------------------
// Insert vorbereiten
// --------------------------------------------------------------------------
$insert = $pdo->prepare("
INSERT INTO frzk_semantische_dichte_dm
(teilnehmer_id, gruppe_id, zeitpunkt,
 x_kognition, y_sozial, z_affektiv, h_bedeutung,
 dh_dt, cluster_id, stabilitaet_score, transitions_marker,
 bemerkung, emotions, emotions_valenz, emotions_aktivierung, wichtung)
VALUES
(:tid, :gid, :zeitpunkt,
 :x, :y, :z, :h,
 :dh, :cluster, :stab, :marker,
 :bem, :emo, :valenz, :aktiv, :wichtung)
");

// --------------------------------------------------------------------------
// Hauptberechnung
// --------------------------------------------------------------------------
echo "Berechne semantische Dichte...\n";
$prevH = [];
$counter = 0;

foreach ($rows as $row) {
    $counter++;
    $tid = (int)$row["teilnehmer_id"];
    $gid = isset($row["gruppe_id"]) ? (int)$row["gruppe_id"] : null;
    $zeitpunkt = !empty($row["erfasst_am"]) ? $row["erfasst_am"] : date("Y-m-d H:i:s");

    // --- Skalenwerte extrahieren ---
    $xSkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $kognitiv_fields));
    $ySkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $sozial_fields));
    $zSkalen = array_filter(array_map(fn($f)=> (float)$row[$f], $affektiv_fields));

    // --- Emotionen aus Rückkopplung (CSV von IDs) ---
    $emotionenGesamt = [];
    if (!empty($row["emotions"])) {
        $ids = array_map("trim", explode(",", $row["emotions"]));
        foreach ($ids as $id) {
            if (isset($emotionsMap[$id])) {
                $emotionenGesamt[] = $emotionsMap[$id];
            }
        }
    }

    // --- Dimensionen aus Emotionswerten ---
    $x_emotions = [];
    $y_emotions = [];
    $z_emotions = [];
    $valenzListe = [];
    $aktivListe = [];

    foreach ($emotionenGesamt as $emo) {
        $typ = $emo["type_name"];
        $val = $weights[$typ] ?? 1.0;

        switch ($typ) {
            case "kognitiv": $x_emotions[] = $val; break;
            case "sozial":   $y_emotions[] = $val; break;
            case "affektiv": $z_emotions[] = $val; break;
        }

        $valenzListe[] = $emo["valenz"];
        $aktivListe[] = $emo["aktivierung"];
    }

    // --- Dimensionen berechnen ---
    $x = count($xSkalen) || count($x_emotions)
        ? (array_sum($xSkalen)+array_sum($x_emotions))/max(1,count($xSkalen)+count($x_emotions))
        : 0;
    $y = count($ySkalen) || count($y_emotions)
        ? (array_sum($ySkalen)+array_sum($y_emotions))/max(1,count($ySkalen)+count($y_emotions))
        : 0;
    $z = count($zSkalen) || count($z_emotions)
        ? (array_sum($zSkalen)+array_sum($z_emotions))/max(1,count($zSkalen)+count($z_emotions))
        : 0;

    // --- Gesamtdichte h ---
    $allVals = array_merge($xSkalen,$ySkalen,$zSkalen,$x_emotions,$y_emotions,$z_emotions);
    $h = count($allVals) ? array_sum($allVals)/count($allVals) : 0;

    // --- dh/dt ---
    $dh_dt = isset($prevH[$tid]) ? $h-$prevH[$tid] : 0.0;
    $prevH[$tid] = $h;

    // --- Stabilität ---
    $values = [$x,$y,$z];
    $mean = array_sum($values)/3;
    $variance = array_sum(array_map(fn($v)=>pow($v-$mean,2),$values))/3;
    $stabilitaet = max(0,1-$variance);

    // --- Cluster ---
    if ($h<1.5) $cluster=1;
    elseif ($h<2.2) $cluster=2;
    else $cluster=3;

    // --- Transition Marker ---
    $absDh = abs($dh_dt);
    $marker = "Stabil";
    if ($absDh<0.05) $marker="Homöostatisch";
    elseif ($absDh<0.15) $marker="Adaptiv";
    elseif ($absDh<0.30) $marker="Koordinativ";
    elseif ($absDh<0.50) $marker="Transformativ";
    elseif ($absDh<0.80) $marker="Perturbativ";
    else $marker="Kollapsiv";
    if ($stabilitaet<0.3 && $absDh>0.3) $marker.=" (instabil)";
    elseif ($stabilitaet>0.8 && $absDh<0.1) $marker.=" (resilient)";

    // --- Bemerkung ---
    $bem = sprintf("K:%.2f S:%.2f A:%.2f h:%.2f Δh:%.2f", $x,$y,$z,$h,$dh_dt);
    $emotionsString = implode(", ", array_map(fn($e)=>$e["emotion"],$emotionenGesamt));

    // --- Emotionswerte ---
    $valenz = count($valenzListe) ? array_sum($valenzListe)/count($valenzListe) : 0.0;
    $aktiv  = count($aktivListe) ? array_sum($aktivListe)/count($aktivListe) : 0.0;

    // --- Wichtung ---
    $wichtung = count($x_emotions)+count($y_emotions)+count($z_emotions)
        ? array_sum(array_merge($x_emotions,$y_emotions,$z_emotions)) 
          / (count($x_emotions)+count($y_emotions)+count($z_emotions))
        : 1.0;

    // --- Insert ---
    $insert->execute([
        ":tid"      => $tid,
        ":gid"      => $gid,
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
        ":emo"      => $emotionsString,
        ":valenz"   => $valenz,
        ":aktiv"    => $aktiv,
        ":wichtung" => $wichtung
    ]);

    if ($counter % 100 === 0) echo "→ $counter / $total Datensätze verarbeitet\n";
}

echo "Fertig.\n";