<?php
// 🔄 fill_frzk_transitions.php
// Erzeugt die Tabelle frzk_transitions und befüllt sie aus frzk_semantische_dichte

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabelle anlegen ---
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
?>
