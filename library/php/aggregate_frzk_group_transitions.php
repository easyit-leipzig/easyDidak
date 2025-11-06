<?php
// aggregate_frzk_group_transitions_autofix.php
// Aggregiert Transitions- und Dynamikdaten gruppenweise, robust gegen fehlende dh_dt-Felder

header('Content-Type: text/plain; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Sicherstellen, dass gruppe_id in frzk_semantische_dichte existiert ---
$cols = $pdo->query("SHOW COLUMNS FROM frzk_semantische_dichte")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('gruppe_id', $cols)) {
    echo "⚙️  Spalte gruppe_id fehlt – wird ergänzt und befüllt...\n";
    $pdo->exec("ALTER TABLE frzk_semantische_dichte ADD COLUMN gruppe_id INT NULL AFTER teilnehmer_id");
    $pdo->exec("
        UPDATE frzk_semantische_dichte fsd
        JOIN mtr_rueckkopplung_teilnehmer mrt
          ON fsd.teilnehmer_id = mrt.teilnehmer_id
        SET fsd.gruppe_id = mrt.gruppe_id
    ");
}

// --- Neue Aggregationstabelle ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_frzk_transitions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gruppe_id INT NOT NULL,
  anzahl_punkte INT DEFAULT 0,
  avg_dh_dt FLOAT DEFAULT NULL,
  std_dh_dt FLOAT DEFAULT NULL,
  kohärenz_index FLOAT DEFAULT NULL,
  transitions_marker VARCHAR(50) DEFAULT NULL,
  bemerkung TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("TRUNCATE frzk_group_frzk_transitions");

// --- Daten aus frzk_semantische_dichte holen (statt frzk_transitions) ---
$sql = "
SELECT 
    gruppe_id,
    dh_dt,
    transitions_marker
FROM frzk_semantische_dichte
WHERE gruppe_id IS NOT NULL
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) exit("❌ Keine dynamischen Werte (dh_dt) in frzk_semantische_dichte gefunden.\n");

// --- Gruppieren ---
$groups = [];
foreach ($rows as $r) {
    $gid = (int)$r['gruppe_id'];
    $groups[$gid][] = $r;
}

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
INSERT INTO frzk_group_frzk_transitions
(gruppe_id, anzahl_punkte, avg_dh_dt, std_dh_dt, kohärenz_index, transitions_marker, bemerkung)
VALUES (:gid, :anz, :avg, :std, :coh, :marker, :bem)
");

foreach ($groups as $gid => $entries) {
    $dh = array_column($entries, 'dh_dt');
    $avg = count($dh) ? array_sum($dh)/count($dh) : 0;
    $std = count($dh) > 1 ? sqrt(array_sum(array_map(fn($x)=>pow($x-$avg,2),$dh))/count($dh)) : 0;

    // Dominanter Marker
    $markerCounts = [];
    foreach ($entries as $e) {
        $m = $e['transitions_marker'] ?: 'unbekannt';
        $markerCounts[$m] = ($markerCounts[$m] ?? 0) + 1;
    }
    arsort($markerCounts);
    $dominant = array_key_first($markerCounts);

    // Kohärenzindex
    $coh = ($std > 0) ? max(0, 1 - $std / (abs($avg) + 0.001)) : 1.0;

    // Bemerkung
    if ($avg > 0.3) {
        $bem = "Positive Übergänge, steigende Kohärenz.";
    } elseif ($avg < -0.3) {
        $bem = "Abnehmende Kohärenz, Desynchronisation.";
    } elseif ($dominant === 'Stabil') {
        $bem = "Stabile Gruppe mit hoher Kohärenz.";
    } else {
        $bem = "Wechselhafte Dynamik, moderate Kohärenz.";
    }

    $insert->execute([
        ":gid" => $gid,
        ":anz" => count($entries),
        ":avg" => $avg,
        ":std" => $std,
        ":coh" => $coh,
        ":marker" => $dominant,
        ":bem" => $bem
    ]);

    printf("Gruppe %2d | n=%2d | ⌀Δh=%.3f | σ=%.3f | Marker=%-10s | Kohärenz=%.2f → %s\n",
        $gid, count($entries), $avg, $std, $dominant, $coh, $bem);
}

echo "\n✅ Aggregation abgeschlossen: Tabelle frzk_group_frzk_transitions befüllt.\n";
?>
