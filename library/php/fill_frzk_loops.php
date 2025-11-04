<?php
// fill_frzk_loops.php
// Erzeugt Rückkopplungsschleifen (Autopoiesis / Stabilisierung) aus frzk_semantische_dichte.
// FRZK-Bezug: Kap. 5.3 "Autopoietische Schleifen", Kap. 6.2 "Kohärenzdynamik".

header('Content-Type: text/plain; charset=utf-8');

// --- Datenbankverbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabelle anlegen falls nötig ---
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
?>
