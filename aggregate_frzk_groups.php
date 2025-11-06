<?php
/**
 * aggregate_frzk_groups_v3.php
 *
 * Aggregiert FRZK-Tabellen gruppenweise — automatisch spaltenabhängig.
 */

header('Content-Type: text/plain; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Alle Basis-Tabellen
$baseTables = ["frzk_semantische_dichte", "frzk_transitions", "frzk_hubs"];

// Hilfsfunktion: prüft, ob Spalte existiert
function columnExists(PDO $pdo, string $table, string $col): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :c");
    $stmt->execute([":c" => $col]);
    return (bool) $stmt->fetch();
}

foreach ($baseTables as $base) {
    echo "Aggregiere $base ...\n";
    $groupTable = "frzk_group_" . $base;

    // Existenz prüfen
    $stmt = $pdo->query("SHOW TABLES LIKE '$base'");
    if (!$stmt->fetch()) {
        echo "⚠️ Tabelle $base existiert nicht – überspringe.\n";
        continue;
    }

    // Spalten prüfen
    $cols = [];
    foreach (["x_kognition","y_sozial","z_affektiv","h_bedeutung","dh_dt","stabilitaet_score","cluster_id"] as $c) {
        if (columnExists($pdo, $base, $c)) $cols[] = $c;
    }

    if (empty($cols)) {
        echo "⚠️ Keine passenden Spalten gefunden – überspringe $base.\n";
        continue;
    }

    // Gruppentabelle löschen & neu anlegen
    $pdo->exec("DROP TABLE IF EXISTS `$groupTable`");
    $pdo->exec("
        CREATE TABLE `$groupTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gruppe_id INT NOT NULL,
            anzahl_teilnehmer INT NOT NULL,
            avg_h FLOAT NULL,
            var_h FLOAT NULL,
            stabilitaet_avg FLOAT NULL,
            dh_dt_avg FLOAT NULL,
            dh_dt_std FLOAT NULL,
            cluster_dominant INT NULL,
            kohärenz_score FLOAT NULL,
            transitions_marker VARCHAR(50) NULL,
            bemerkung TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Dynamisches SQL basierend auf existierenden Spalten
    $selects = ["r.gruppe_id", "COUNT(DISTINCT s.teilnehmer_id) AS n"];
    if (in_array("x_kognition", $cols)) $selects[] = "AVG(s.x_kognition) AS avg_x";
    if (in_array("y_sozial", $cols))   $selects[] = "AVG(s.y_sozial) AS avg_y";
    if (in_array("z_affektiv", $cols)) $selects[] = "AVG(s.z_affektiv) AS avg_z";
    if (in_array("h_bedeutung", $cols)) {
        $selects[] = "AVG(s.h_bedeutung) AS avg_h";
        $selects[] = "VAR_SAMP(s.h_bedeutung) AS var_h";
    }
    if (in_array("stabilitaet_score", $cols)) $selects[] = "AVG(s.stabilitaet_score) AS stabilitaet_avg";
    if (in_array("dh_dt", $cols)) {
        $selects[] = "AVG(s.dh_dt) AS dh_dt_avg";
        $selects[] = "STD(s.dh_dt) AS dh_dt_std";
    }

    $sql = "
        SELECT " . implode(", ", $selects) . "
        FROM $base AS s
        JOIN mtr_rueckkopplung_teilnehmer AS r ON r.teilnehmer_id = s.teilnehmer_id
        GROUP BY r.gruppe_id
    ";
    $stmt = $pdo->query($sql);
    $groups = $stmt->fetchAll();

    // Clusterdominanz, falls cluster_id existiert
    $clusterDom = [];
    if (in_array("cluster_id", $cols)) {
        $clusterStmt = $pdo->prepare("
            SELECT r.gruppe_id, s.cluster_id, COUNT(*) AS c
            FROM $base AS s
            JOIN mtr_rueckkopplung_teilnehmer AS r ON r.teilnehmer_id = s.teilnehmer_id
            GROUP BY r.gruppe_id, s.cluster_id
            ORDER BY r.gruppe_id, c DESC
        ");
        $clusterStmt->execute();
        foreach ($clusterStmt as $row) {
            if (!isset($clusterDom[$row['gruppe_id']])) {
                $clusterDom[$row['gruppe_id']] = $row['cluster_id'];
            }
        }
    }

    $insert = $pdo->prepare("
        INSERT INTO `$groupTable`
        (gruppe_id, anzahl_teilnehmer, avg_h, var_h, stabilitaet_avg, dh_dt_avg, dh_dt_std, cluster_dominant, kohärenz_score, transitions_marker, bemerkung)
        VALUES
        (:gid, :n, :avgh, :varh, :stab, :dhavg, :dhstd, :cluster, :koh, :marker, :bem)
    ");

    foreach ($groups as $g) {
        $gid = $g["gruppe_id"];
        $cluster = $clusterDom[$gid] ?? null;

        // Kohärenzberechnung (nur wenn relevant)
        $koh = isset($g["stabilitaet_avg"], $g["var_h"])
            ? max(0, round($g["stabilitaet_avg"] - $g["var_h"], 3))
            : null;

        if ($koh > 0.8) $marker = "hoch kohärent";
        elseif ($koh > 0.5) $marker = "mittel";
        else $marker = "niedrig";

        $bem = sprintf(
            "Gruppe %d: Øh=%.2f, σh=%.2f, Δh̄=%.2f, Kohärenz=%.2f",
            $gid,
            $g["avg_h"] ?? 0,
            sqrt($g["var_h"] ?? 0),
            $g["dh_dt_avg"] ?? 0,
            $koh ?? 0
        );

        $insert->execute([
            ":gid" => $gid,
            ":n" => $g["n"],
            ":avgh" => $g["avg_h"] ?? null,
            ":varh" => $g["var_h"] ?? null,
            ":stab" => $g["stabilitaet_avg"] ?? null,
            ":dhavg" => $g["dh_dt_avg"] ?? null,
            ":dhstd" => $g["dh_dt_std"] ?? null,
            ":cluster" => $cluster,
            ":koh" => $koh,
            ":marker" => $marker,
            ":bem" => $bem
        ]);
    }

    echo "✅ Aggregation für $base abgeschlossen → $groupTable\n\n";
}

echo "🎯 Alle Gruppentabellen erfolgreich generiert.\n";
?>
