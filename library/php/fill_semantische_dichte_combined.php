<?php
// ==========================================================================
// fill_semantische_dichte_combined.php
// Dynamische Befüllung der FRZK-Semantikmatrix aus Skalen + Emotionsdaten
// Ergebnis: frzk_semantische_dichte (vollständig inkl. emotions-Feld) + JSON-Datei
// ==========================================================================

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabellen leeren (Neuaufbau) ---
echo "Truncate FRZK-Tabellen...\n";
$truncateTables = [
    "frzk_interdependenz",
    "frzk_loops",
    "frzk_operatoren",
    "frzk_reflexion",
    "frzk_semantische_dichte",
    "frzk_transitions"
];
foreach ($truncateTables as $t) {
    $pdo->exec("TRUNCATE TABLE $t");
}

// --------------------------------------------------------------------------
// Gewichtungen für Emotionstypen
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
// Emotion → Kategorie laden
// --------------------------------------------------------------------------
echo "Lade Emotions-Kategorisierung...\n";
$emotionsMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch()) {
    $emotionsMap[trim($row["emotion"])] = strtolower(trim($row["type_name"]));
}

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
// Emotionen aus Detailtabelle vorbereiten
// --------------------------------------------------------------------------
$emoStmt = $pdo->prepare("SELECT emotions FROM mtr_emotions WHERE teilnehmer_id = :tid");

// --------------------------------------------------------------------------
// Insert vorbereiten
// --------------------------------------------------------------------------
$insert = $pdo->prepare("
    INSERT INTO frzk_semantische_dichte
    (teilnehmer_id, gruppe_id, zeitpunkt,
     x_kognition, y_sozial, z_affektiv, h_bedeutung,
     dh_dt, cluster_id, stabilitaet_score, transitions_marker, bemerkung, emotions)
    VALUES
    (:tid, :gid, :zeitpunkt, :x, :y, :z, :h, :dh, :cluster, :stab, :marker, :bem, :emo)
");

// --------------------------------------------------------------------------
// Hauptberechnung
// --------------------------------------------------------------------------
echo "Berechne semantische Dichte...\n";
$jsonData = [];
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

    // --- Emotionen aus beiden Quellen sammeln ---
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

    // --- Emotionen nach FRZK-Dimension verteilen ---
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
                }
            }
        }
    }

    // --- Dimensionen berechnen ---
    $x = count($xSkalen) || count($x_emotions)
        ? (array_sum($xSkalen) + array_sum($x_emotions)) / max(1, count($xSkalen) + count($x_emotions))
        : 0;

    $y = count($ySkalen) || count($y_emotions)
        ? (array_sum($ySkalen) + array_sum($y_emotions)) / max(1, count($ySkalen) + count($y_emotions))
        : 0;

    $z = count($zSkalen) || count($z_emotions)
        ? (array_sum($zSkalen) + array_sum($z_emotions)) / max(1, count($zSkalen) + count($z_emotions))
        : 0;

    // --- Gesamtdichte h ---
    $allVals = array_merge($xSkalen, $ySkalen, $zSkalen, $x_emotions, $y_emotions, $z_emotions);
    $h = count($allVals) ? array_sum($allVals) / count($allVals) : 0;

    // --- dh/dt ---
    $dh_dt = isset($prevH[$tid]) ? $h - $prevH[$tid] : 0.0;
    $prevH[$tid] = $h;

    // --- Stabilität ---
    $values = [$x,$y,$z];
    $mean = array_sum($values)/3;
    $variance = array_sum(array_map(fn($v)=>pow($v-$mean,2),$values))/3;
    $stabilitaet = max(0, 1 - $variance);

    // --- Cluster ---
    if ($h < 1.5) $cluster = 1;
    elseif ($h < 2.2) $cluster = 2;
    else $cluster = 3;

    // --- Transition Marker ---
// --- Transition Marker (granular nach Kapitel 3 FRZK) ---
/*


🧭 Ergebnisbeispiele
Δh (dh_dt)    Stabilität    transitions_marker-Ausgabe
0.01    0.9    ⚖️ Homöostatisch (resilient)
0.10    0.7    🌱 Adaptiv
0.25    0.6    🔄 Koordinativ
0.45    0.4    🌊 Transformativ
0.65    0.2    ⚡ Perturbativ (instabil)
0.95    0.1    💥 Kollapsiv (instabil)
🧠 Theoretischer Bezug (Kapitel 3)

Diese Klassifikation bildet die funktionale Dynamik der Bedeutungs-Änderungsrate (dh/dt) ab und entspricht der dortigen
Differenzierung der Transitionsebenen:

Homöostatisch / Adaptiv = Binnenkohärenz-Erhalt

Koordinativ / Transformativ = Funktionswechsel, emergente Umstrukturierung

Perturbativ / Kollapsiv = Systembruch oder Neuanfang

Damit kannst du in der späteren frzk_transitions-Analyse z. B. auch Aggregationen nach Marker-Typ durchführen (etwa: Anteil transformativ vs. adaptiv).



*/
$absDh = abs($dh_dt);
$marker = "Stabil";

if ($absDh < 0.05) {
    $marker = "Homöostatisch"; // nahezu keine Änderung – Gleichgewicht
} elseif ($absDh < 0.15) {
    $marker = "Adaptiv"; // leichte Anpassung
} elseif ($absDh < 0.30) {
    $marker = "Koordinativ"; // mittlere dynamische Anpassung
} elseif ($absDh < 0.50) {
    $marker = "Transformativ"; // deutliche Neuorientierung
} elseif ($absDh < 0.80) {
    $marker = "Perturbativ"; // starker Sprung, Instabilität
} else {
    $marker = "Kollapsiv"; // vollständiger Bedeutungsumbruch
}

// Erweiterung durch Stabilitätsbewertung
if ($stabilitaet < 0.3 && $absDh > 0.3) {
    $marker .= " (instabil)";
} elseif ($stabilitaet > 0.8 && $absDh < 0.1) {
    $marker .= " (resilient)";
}

// Optional: symbolische Marker (z. B. für Visualisierung)
$markerSymbol = match (true) {
    str_contains($marker, "Homöostatisch") => "⚖️",
    str_contains($marker, "Adaptiv") => "🌱",
    str_contains($marker, "Koordinativ") => "🔄",
    str_contains($marker, "Transformativ") => "🌊",
    str_contains($marker, "Perturbativ") => "⚡",
    str_contains($marker, "Kollapsiv") => "💥",
    default => "•",
};
$marker = "{$markerSymbol} {$marker}";

    // --- Bemerkung + Emotionen ---
    $bem = sprintf("K:%.2f S:%.2f A:%.2f h:%.2f Δh:%.2f", $x, $y, $z, $h, $dh_dt);
    $emotionsString = implode(", ", $emotionenGesamt);

    // --- INSERT ---
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
        "h_bedeutung"   => $h,
        "dh_dt"         => $dh_dt,
        "cluster_id"    => $cluster,
        "stabilitaet_score" => $stabilitaet,
        "transitions_marker" => $marker,
        "bemerkung"     => $bem,
        "emotions"      => $emotionsString
    ];

    if ($counter % 100 === 0) {
        echo "→ $counter / $total Datensätze verarbeitet\n";
    }
}

// --------------------------------------------------------------------------
// JSON schreiben
// --------------------------------------------------------------------------
file_put_contents(__DIR__ . "/frzk_semantische_dichte.json",
    json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ FRZK-Semantische Dichte erfolgreich befüllt (inkl. Gruppen & Zeit) und JSON erzeugt.\n";

// --------------------------------------------------------------------------
// Nachgelagerte Befüllung der abhängigen FRZK-Komponenten
// --------------------------------------------------------------------------
//require_once("fill_frzk_interdependenz.php");
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_interdependenz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    zeitpunkt DATETIME NOT NULL,
    x_kognition DECIMAL(5,2) DEFAULT NULL,
    y_sozial DECIMAL(5,2) DEFAULT NULL,
    z_affektiv DECIMAL(5,2) DEFAULT NULL,
    h_bedeutung DECIMAL(5,2) DEFAULT NULL,
    korrelationsscore FLOAT DEFAULT NULL,
    kohärenz_index FLOAT DEFAULT NULL,
    varianz_xyz FLOAT DEFAULT NULL,
    bemerkung TEXT,
    INDEX(teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Daten holen ---
$stmt = $pdo->query("
    SELECT teilnehmer_id, zeitpunkt, x_kognition AS x, y_sozial AS y, z_affektiv AS z, h_bedeutung AS h
    FROM frzk_semantische_dichte
    ORDER BY teilnehmer_id, zeitpunkt ASC
");
$rows = $stmt->fetchAll();

// --- Prepared Insert ---
$insert = $pdo->prepare("
    INSERT INTO frzk_interdependenz
    (teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung,
     korrelationsscore, kohärenz_index, varianz_xyz, bemerkung)
    VALUES
    (:tid, :zeitpunkt, :x, :y, :z, :h, :corr, :koh, :var, :bem)
");

// --- Berechnungen ---
$count = 0;
foreach ($rows as $row) {
    $tid = (int)$row['teilnehmer_id'];
    $x = (float)$row['x'];
    $y = (float)$row['y'];
    $z = (float)$row['z'];
    $h = (float)$row['h'];

    // --- Korrelationsscore ---
    $corr = ($x*$y + $y*$z + $x*$z) / 3;

    // --- Kohärenzindex ---
    $koh = 1 - (abs($x-$y) + abs($y-$z) + abs($x-$z)) / 9;
    if ($koh < 0) $koh = 0;

    // --- Varianz xyz ---
    $values = [$x,$y,$z];
    $mean = array_sum($values)/3;
    $var = array_sum(array_map(fn($v)=>pow($v-$mean,2), $values))/3;

    // --- Bemerkung ---
    $bem = sprintf("x=%.2f y=%.2f z=%.2f h=%.2f | Corr=%.3f Koh=%.3f Var=%.3f",
                   $x,$y,$z,$h,$corr,$koh,$var);

    $insert->execute([
        ':tid' => $tid,
        ':zeitpunkt' => $row['zeitpunkt'],
        ':x' => $x,
        ':y' => $y,
        ':z' => $z,
        ':h' => $h,
        ':corr' => $corr,
        ':koh' => $koh,
        ':var' => $var,
        ':bem' => $bem
    ]);

    $count++;
}

echo "✅ $count Datensätze in frzk_interdependenz eingefügt (inkl. x,y,z,h & Kopplungswerte).\n";
//require_once("fill_frzk_loops.php");
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_loops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    start_zeit DATETIME NOT NULL,
    end_zeit DATETIME NOT NULL,
    schleifen_typ VARCHAR(50) DEFAULT NULL,
    dauer INT DEFAULT NULL,
    dh_dt_avg FLOAT DEFAULT NULL,
    verdichtungsgrad FLOAT DEFAULT NULL,
    stabilitaet_score FLOAT DEFAULT NULL,
    pausenmarker VARCHAR(50) DEFAULT NULL,
    bemerkung TEXT,
    INDEX(teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Daten holen (sortiert nach Teilnehmer und Zeit) ---
$stmt = $pdo->query("
    SELECT teilnehmer_id, zeitpunkt, h_bedeutung, dh_dt, stabilitaet_score
    FROM frzk_semantische_dichte
    ORDER BY teilnehmer_id, zeitpunkt ASC
");
$rows = $stmt->fetchAll();

// --- Verarbeitung ---
$insert = $pdo->prepare("
    INSERT INTO frzk_loops
    (teilnehmer_id, start_zeit, end_zeit, schleifen_typ, dauer,
     dh_dt_avg, verdichtungsgrad, stabilitaet_score, pausenmarker, bemerkung)
    VALUES
    (:tid, :start, :end, :typ, :dauer, :dhavg, :verd, :stab, :pause, :bem)
");

$count = 0;
$current_tid = null;
$current_series = [];

foreach ($rows as $r) {
    $tid = (int)$r['teilnehmer_id'];
    $h = (float)$r['h_bedeutung'];
    $dh = (float)($r['dh_dt'] ?? 0);
    $stab = (float)($r['stabilitaet_score'] ?? 0);
    $zeit = $r['zeitpunkt'];

    // --- Wechsel zu neuem Teilnehmer -> vorherige Serie auswerten ---
    if ($current_tid !== null && $tid !== $current_tid && count($current_series) > 1) {
        processLoop($current_tid, $current_series, $insert, $count);
        $current_series = [];
    }

    $current_tid = $tid;
    $current_series[] = [
        'zeit' => $zeit,
        'h' => $h,
        'dh' => $dh,
        'stab' => $stab
    ];
}

// --- letzte Serie ---
if ($current_tid !== null && count($current_series) > 1) {
    processLoop($current_tid, $current_series, $insert, $count);
}

echo "✅ $count Rückkopplungsschleifen in frzk_loops eingefügt.\n";


// ----------------------------------------------------------
// Hilfsfunktion zur Schleifenanalyse
// ----------------------------------------------------------
function processLoop($tid, $series, $insert, &$count) {
    // Zeitspanne
    $start = $series[0]['zeit'];
    $end = end($series)['zeit'];
    $dauer = (new DateTime($start))->diff(new DateTime($end))->s;

    // Durchschnittliche Änderungsrate
    $dh_avg = array_sum(array_column($series,'dh')) / count($series);

    // Varianz von h -> Verdichtungsgrad (Inverse der Varianz)
    $hs = array_column($series,'h');
    $mean_h = array_sum($hs)/count($hs);
    $var_h = array_sum(array_map(fn($v)=>pow($v-$mean_h,2),$hs))/count($hs);
    $verd = max(0, 1 - $var_h);

    // Mittelwert Stabilität
    $stab_avg = array_sum(array_column($series,'stab')) / max(1,count($series));

    // --- Klassifikation der Schleife ---
    if (abs($dh_avg) < 0.05 && $verd > 0.9) {
        $typ = "Homöostase";         // stabiler Rücklauf
    } elseif (abs($dh_avg) < 0.2) {
        $typ = "Integration";        // allmähliche Anpassung
    } elseif (abs($dh_avg) < 0.5) {
        $typ = "Reorganisation";     // moderate Neuordnung
    } else {
        $typ = "Perturbation";       // starker Bruch / Reset
    }

    // Pausenmarker – erkennt signifikante zeitliche Lücken
    $pause = (count($series) < 3) ? "Kurzsequenz"
            : (($verd < 0.5) ? "Unterbrechung" : "Fortlauf");

    // Textausgabe
    $bem = sprintf("Loop %s | Δh̄=%.3f Verd=%.3f Stab=%.3f Dauer=%ds",
                   $typ, $dh_avg, $verd, $stab_avg, $dauer);

    // DB-Insert
    $insert->execute([
        ':tid' => $tid,
        ':start' => $start,
        ':end' => $end,
        ':typ' => $typ,
        ':dauer' => $dauer,
        ':dhavg' => $dh_avg,
        ':verd' => $verd,
        ':stab' => $stab_avg,
        ':pause' => $pause,
        ':bem' => $bem
    ]);

    $count++;
}

//require_once("fill_frzk_operatoren.php");
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_operatoren (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    zeitpunkt DATETIME NOT NULL,
    x_kognition DECIMAL(5,2) DEFAULT NULL,
    y_sozial DECIMAL(5,2) DEFAULT NULL,
    z_affektiv DECIMAL(5,2) DEFAULT NULL,
    h_bedeutung DECIMAL(5,2) DEFAULT NULL,
    dh_dt FLOAT DEFAULT NULL,
    stabilitaet_score FLOAT DEFAULT NULL,
    operator_sigma FLOAT DEFAULT NULL,
    operator_meta FLOAT DEFAULT NULL,
    operator_resonanz FLOAT DEFAULT NULL,
    operator_emer FLOAT DEFAULT NULL,
    operator_level FLOAT DEFAULT NULL,
    dominanter_operator VARCHAR(20) DEFAULT NULL,
    bemerkung TEXT,
    INDEX(teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Emotionstypen-Mapping laden ---
$emotionsMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch()) {
    $emotionsMap[trim($row["emotion"])] = strtolower(trim($row["type_name"]));
}

// --- Hauptdaten aus frzk_semantische_dichte ---
$sql = "SELECT teilnehmer_id, zeitpunkt, x_kognition AS x, y_sozial AS y, 
               z_affektiv AS z, h_bedeutung AS h, dh_dt, stabilitaet_score, 
               emotions
        FROM frzk_semantische_dichte
        ORDER BY teilnehmer_id, zeitpunkt ASC";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
INSERT INTO frzk_operatoren
(teilnehmer_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung, dh_dt,
 stabilitaet_score, operator_sigma, operator_meta, operator_resonanz,
 operator_emer, operator_level, dominanter_operator, bemerkung)
VALUES
(:tid, :zeitpunkt, :x, :y, :z, :h, :dh, :stab,
 :sig, :meta, :res, :emer, :level, :dom, :bem)
");

$count = 0;

foreach ($rows as $r) {
    $tid = (int)$r['teilnehmer_id'];
    $x   = (float)$r['x'];
    $y   = (float)$r['y'];
    $z   = (float)$r['z'];
    $h   = (float)$r['h'];
    $dh  = (float)($r['dh_dt'] ?? 0);
    $stab= (float)($r['stabilitaet_score'] ?? 0);

    // --- Emotionen-Array (optional aus JSON oder CSV) ---
    $emoTypes = [];
    if (!empty($r['emotions'])) {
        $emos = array_map('trim', explode(',', $r['emotions']));
        foreach ($emos as $emo) {
            if (isset($emotionsMap[$emo])) {
                $emoTypes[] = $emotionsMap[$emo];
            }
        }
    }

    // --- σ (Semantisierung): kognitive Dominanz ---
    $sigma = min(1, $x / 3 + (in_array('kognitiv', $emoTypes) ? 0.3 : 0));

    // --- M (Meta-Reflexion): Wechsel zwischen positiven/negativen Emotionen ---
    $pos = count(array_filter($emoTypes, fn($t)=>$t === 'positiv'));
    $neg = count(array_filter($emoTypes, fn($t)=>$t === 'negativ'));
    $meta = ($pos && $neg) ? 0.7 : 0.3;
    $meta += max(0, (1 - $stab) / 2);

    // --- R (Resonanz): soziale Kohärenz ---
    $res = min(1, $y / 3 + ($stab > 0.7 ? 0.2 : 0));

    // --- E (Emergenz): hohe Dynamik / starke dh_dt-Schwankung ---
    $emer = min(1, abs($dh) + ($z > 1.5 ? 0.2 : 0));

    // --- Gesamtniveau ---
    $level = ($sigma + $meta + $res + $emer) / 4;

    // --- Dominanter Operator ---
    $ops = ['σ'=>$sigma, 'M'=>$meta, 'R'=>$res, 'E'=>$emer];
    arsort($ops);
    $dom = array_key_first($ops);

    // --- Bemerkung ---
    $bem = sprintf("σ=%.2f M=%.2f R=%.2f E=%.2f | Level=%.2f dh/dt=%.3f",
                   $sigma,$meta,$res,$emer,$level,$dh);

    // --- Insert ---
    $insert->execute([
        ':tid' => $tid,
        ':zeitpunkt' => $r['zeitpunkt'],
        ':x' => $x,
        ':y' => $y,
        ':z' => $z,
        ':h' => $h,
        ':dh' => $dh,
        ':stab' => $stab,
        ':sig' => $sigma,
        ':meta' => $meta,
        ':res' => $res,
        ':emer' => $emer,
        ':level' => $level,
        ':dom' => $dom,
        ':bem' => $bem
    ]);

    $count++;
}

echo "✅ $count Operator-Datensätze in frzk_operatoren eingefügt (σ,M,R,E erfolgreich berechnet).\n";

//require_once("fill_frzk_reflexion.php");
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_reflexion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    zeitpunkt DATETIME NOT NULL,
    reflexionsgrad FLOAT DEFAULT NULL,
    meta_kohärenz FLOAT DEFAULT NULL,
    selbstbezug_index FLOAT DEFAULT NULL,
    reflexions_marker VARCHAR(20) DEFAULT NULL,
    bemerkung TEXT DEFAULT NULL,
    INDEX (teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Alle semantischen Dichte-Daten laden ---
$stmt = $pdo->query("SELECT * FROM frzk_semantische_dichte ORDER BY teilnehmer_id, zeitpunkt");
$rows = $stmt->fetchAll();

// --- Schlüsselbegriffe für Selbstbezug erkennen ---
$selfWords = ["selbst", "ich", "motivation", "zweifel", "bewusst", "reflexion", "identität", "selbstvertrauen"];
$reflexiveTypes = ["reflexiv", "metakognitiv"];

// --- Gruppieren nach Teilnehmer ---
$grouped = [];
foreach ($rows as $r) {
    $tid = $r["teilnehmer_id"];
    $grouped[$tid][] = $r;
}

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
    INSERT INTO frzk_reflexion
    (teilnehmer_id, zeitpunkt, reflexionsgrad, meta_kohärenz, selbstbezug_index, reflexions_marker, bemerkung)
    VALUES (:tid, :zeit, :grad, :meta, :self, :marker, :bem)
");

// --- Berechnung pro Teilnehmer ---
foreach ($grouped as $tid => $data) {

    // 1️⃣ Meta-Kohärenz – Kohärenz der Kohärenz (1 - Varianz von dh_dt)
    $dhValues = array_column($data, "dh_dt");
    $meanDh = count($dhValues) ? array_sum($dhValues) / count($dhValues) : 0;
    $varDh = count($dhValues) > 1
        ? array_sum(array_map(fn($v) => pow($v - $meanDh, 2), $dhValues)) / count($dhValues)
        : 0;
    $meta = max(0, 1 - $varDh);

    // 2️⃣ Selbstbezug-Index (Anteil selbstreferenzieller Emotionen)
    $selfCount = 0;
    $emoCount = 0;
    foreach ($data as $d) {
        if (!empty($d["emotions"])) {
            $emos = array_map("trim", explode(",", strtolower($d["emotions"])));
            foreach ($emos as $e) {
                if ($e === "") continue;
                $emoCount++;
                foreach ($selfWords as $w) {
                    if (str_contains($e, $w)) {
                        $selfCount++;
                        break;
                    }
                }
            }
        }
    }
    $selfIndex = $emoCount > 0 ? min(1, $selfCount / $emoCount) : 0;

    // 3️⃣ Stabilität (Mittelwert aus stabilitaet_score)
    $stabValues = array_column($data, "stabilitaet_score");
    $stabilitaet = count($stabValues) ? array_sum($stabValues) / count($stabValues) : 0;

    // 4️⃣ Reflexionsgrad (gewichtetes Mittel)
    $grad = 0.6 * $stabilitaet + 0.4 * $selfIndex;

    // 5️⃣ Marker
    if ($grad < 0.33) $marker = "niedrig";
    elseif ($grad < 0.66) $marker = "mittel";
    else $marker = "hoch";

    // 6️⃣ Bemerkung
    $bem = sprintf(
        "Reflexionsgrad: %.2f | Meta-Kohärenz: %.2f | Selbstbezug: %.2f | Stabilität: %.2f",
        $grad, $meta, $selfIndex, $stabilitaet
    );

    // 7️⃣ Insert (aktueller Zeitwert)
    $insert->execute([
        ":tid"    => $tid,
        ":zeit"   => end($data)["zeitpunkt"],
        ":grad"   => $grad,
        ":meta"   => $meta,
        ":self"   => $selfIndex,
        ":marker" => $marker,
        ":bem"    => $bem
    ]);
}

echo "✅ Tabelle frzk_reflexion erfolgreich erstellt und befüllt (Variante A, ohne Foreign Key).\n";

//require_once("fill_frzk_transitions.php");
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    zeitpunkt DATETIME NOT NULL,
    von_cluster INT DEFAULT NULL,
    nach_cluster INT DEFAULT NULL,
    delta_h FLOAT DEFAULT NULL,
    delta_stabilitaet FLOAT DEFAULT NULL,
    transition_typ VARCHAR(50) DEFAULT NULL,
    transition_intensitaet FLOAT DEFAULT NULL,
    marker VARCHAR(10) DEFAULT NULL,
    bemerkung TEXT DEFAULT NULL,
    INDEX (teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Daten aus frzk_semantische_dichte holen ---
$stmt = $pdo->query("SELECT * FROM frzk_semantische_dichte ORDER BY teilnehmer_id, zeitpunkt");
$rows = $stmt->fetchAll();

// --- Gruppieren nach Teilnehmer ---
$grouped = [];
foreach ($rows as $r) {
    $tid = $r["teilnehmer_id"];
    $grouped[$tid][] = $r;
}

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
    INSERT INTO frzk_transitions
    (teilnehmer_id, zeitpunkt, von_cluster, nach_cluster, delta_h, delta_stabilitaet,
     transition_typ, transition_intensitaet, marker, bemerkung)
    VALUES (:tid, :zeit, :von, :nach, :dh, :ds, :typ, :inten, :mark, :bem)
");

// --- Berechnung ---
foreach ($grouped as $tid => $data) {
    if (count($data) < 2) continue; // keine Transition möglich

    for ($i = 1; $i < count($data); $i++) {
        $prev = $data[$i - 1];
        $curr = $data[$i];

        $deltaH = (float)$curr["h_bedeutung"] - (float)$prev["h_bedeutung"];
        $deltaStab = (float)$curr["stabilitaet_score"] - (float)$prev["stabilitaet_score"];

        $vonCluster = (int)$prev["cluster_id"];
        $nachCluster = (int)$curr["cluster_id"];

        // --- Intensität ---
        $intensitaet = min(1, (abs($deltaH) + abs($deltaStab)) / 2);

        // --- Typbestimmung ---
        if ($nachCluster !== $vonCluster && $deltaH > 0.5) {
            $typ = "Sprung";
            $mark = "🚀";
        } elseif ($deltaH > 0.4 && $deltaStab > 0) {
            $typ = "Stabilisierung";
            $mark = "🌀";
        } elseif ($deltaH < -0.4 && $deltaStab < 0) {
            $typ = "Destabilisierung";
            $mark = "⚡";
        } elseif (abs($deltaH) < 0.2 && abs($deltaStab) < 0.1) {
            $typ = "Neutral";
            $mark = "•";
        } else {
            $typ = "Rückkopplung";
            $mark = "🔄";
        }

        // --- Bemerkung ---
        $bem = sprintf(
            "Δh: %.3f | Δstab: %.3f | Cluster: %d→%d | Typ: %s | Intensität: %.2f",
            $deltaH, $deltaStab, $vonCluster, $nachCluster, $typ, $intensitaet
        );

        // --- Insert ---
        $insert->execute([
            ":tid"   => $tid,
            ":zeit"  => $curr["zeitpunkt"],
            ":von"   => $vonCluster,
            ":nach"  => $nachCluster,
            ":dh"    => $deltaH,
            ":ds"    => $deltaStab,
            ":typ"   => $typ,
            ":inten" => $intensitaet,
            ":mark"  => $mark,
            ":bem"   => $bem
        ]);
    }
}

echo "✅ Tabelle frzk_transitions erfolgreich erstellt und befüllt.\n";
$tables = [
  "frzk_semantische_dichte",
  "frzk_interdependenz",
  "frzk_operatoren",
  "frzk_reflexion",
  "frzk_transitions",
  "frzk_group_semantische_dichte",
  "frzk_group_reflexion",
  "frzk_group_transitions"
];

foreach ($tables as $t) {
    $stmt = $pdo->query("SELECT * FROM $t");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $file = __DIR__ . "/{$t}.json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✅ Exportiert: $t → $file (" . count($data) . " Einträge)\n";
}

?>
