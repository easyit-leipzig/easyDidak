<?php
// Aggregiert semantische Dichte pro Gruppe + Unterrichtseinheit

header('Content-Type: text/plain; charset=utf-8');
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "Leere alle frzk_gruppen-Tabellen...\n";
$pdo->exec("TRUNCATE TABLE frzk_gruppen_semantische_dichte;
TRUNCATE TABLE frzk_gruppen_hubs;
TRUNCATE TABLE frzk_gruppen_interdependenz;
TRUNCATE TABLE frzk_gruppen_loops;
TRUNCATE TABLE frzk_gruppen_operatoren;
TRUNCATE TABLE frzk_gruppen_reflexion;
TRUNCATE TABLE frzk_gruppen_transitions;");

/**/
// fill_frzk_gruppen_semantische_dichte.php
// Aggregiert semantische Dichte pro Gruppe + Unterrichtseinheit (über mtr_rueckkopplung_teilnehmer)
echo "Leere frzk_gruppen_semantische_dichte...\n";
$pdo->exec("TRUNCATE TABLE frzk_gruppen_semantische_dichte");

// ----------------------------------------------------------
// Unterrichtseinheiten und Gruppen ermitteln
// ----------------------------------------------------------
$sql = "
SELECT 
  uzt.ue_unterrichtseinheit_zw_thema_id AS unterrichtseinheit_id,
  mrt.gruppe_id
FROM mtr_rueckkopplung_teilnehmer mrt
JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
GROUP BY uzt.ue_unterrichtseinheit_zw_thema_id, mrt.gruppe_id
";
$stmt = $pdo->query($sql);
$combos = $stmt->fetchAll();

$insert = $pdo->prepare("
INSERT INTO frzk_gruppen_semantische_dichte
(unterrichtseinheit_id, gruppe_id, zeitpunkt, 
 x_kognition, y_sozial, z_affektiv, h_bedeutung, dh_dt, 
 cluster_id, stabilitaet_score, transitions_marker, emotions)
VALUES
(:ueid, :gid, :zeit, :x, :y, :z, :h, :dh, :cluster, :stab, :marker, :emo)
");

// ----------------------------------------------------------
// Aggregation pro Gruppe + UE
// ----------------------------------------------------------
foreach ($combos as $c) {
    $ueid = $c['unterrichtseinheit_id'];
    $gid  = $c['gruppe_id'];

    // 1️⃣ Mittelwerte der Dimensionen
    $avgStmt = $pdo->prepare("
        SELECT 
          AVG(x_kognition) AS x,
          AVG(y_sozial) AS y,
          AVG(z_affektiv) AS z,
          AVG(h_bedeutung) AS h,
          AVG(dh_dt) AS dh,
          AVG(stabilitaet_score) AS stab,
          MAX(zeitpunkt) AS zeit
        FROM frzk_semantische_dichte
        WHERE gruppe_id = :gid
    ");
    $avgStmt->execute([":gid"=>$gid]);
    $avg = $avgStmt->fetch();

    if (!$avg) continue;

    // 2️⃣ Dominanter Marker
    $markerStmt = $pdo->prepare("
        SELECT transitions_marker, COUNT(*) AS cnt
        FROM frzk_semantische_dichte
        WHERE gruppe_id = :gid
        GROUP BY transitions_marker
        ORDER BY cnt DESC
        LIMIT 1
    ");
    $markerStmt->execute([":gid"=>$gid]);
    $markerRow = $markerStmt->fetch();
    $marker = $markerRow ? $markerRow["transitions_marker"] : "Stabil";

    // 3️⃣ Relevante Emotionen (Top 3)
    $emoStmt = $pdo->prepare("
        SELECT emotions FROM frzk_semantische_dichte WHERE gruppe_id = :gid
    ");
    $emoStmt->execute([":gid"=>$gid]);
    $emoCounts = [];

    while ($row = $emoStmt->fetch()) {
        $list = array_map('trim', explode(',', $row['emotions']));
        foreach ($list as $emo) {
            if ($emo !== '') $emoCounts[$emo] = ($emoCounts[$emo] ?? 0) + 1;
        }
    }

    arsort($emoCounts);
    $topEmos = array_slice(array_keys($emoCounts), 0, 5); // Top 5 Emotionen
    $markerEmos = implode(", ", $topEmos);

    // 4️⃣ Cluster & Insert
    $cluster = ($avg["h"] < 1.5 ? 1 : ($avg["h"] < 2.2 ? 2 : 3));

    $insert->execute([
        ":ueid" => $ueid,
        ":gid"  => $gid,
        ":zeit" => $avg["zeit"],
        ":x"    => $avg["x"],
        ":y"    => $avg["y"],
        ":z"    => $avg["z"],
        ":h"    => $avg["h"],
        ":dh"   => $avg["dh"],
        ":cluster" => $cluster,
        ":stab" => $avg["stab"],
        ":marker" => $marker,
        ":emo"  => $markerEmos
    ]);

    echo "→ Gruppe $gid (UE $ueid): Marker=$marker, Emotionen=$markerEmos\n";
}

echo "✅ FRZK-Gruppen-Semantische Dichte mit dominanten Markern und Markeremotionen aufgebaut.\n";
/**/
echo "Starte Aggregation FRZK-Gruppen-Interdependenz...\n";

// Unterrichtseinheiten / Gruppen-Kombinationen bestimmen
$sql = "
SELECT 
  uzt.ue_unterrichtseinheit_zw_thema_id AS unterrichtseinheit_id,
  mrt.gruppe_id
FROM mtr_rueckkopplung_teilnehmer mrt
JOIN ue_zuweisung_teilnehmer uzt 
  ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
GROUP BY uzt.ue_unterrichtseinheit_zw_thema_id, mrt.gruppe_id
";
$stmt = $pdo->query($sql);
$combos = $stmt->fetchAll();

// INSERT vorbereiten
$insert = $pdo->prepare("
INSERT INTO frzk_gruppen_interdependenz
(unterrichtseinheit_id, gruppe_id, zeitpunkt, relation_typ, staerke, kohärenz)
VALUES (:ueid, :gid, :zeit, :typ, :staerke, :koh)
");

foreach ($combos as $c) {
    $ueid = $c['unterrichtseinheit_id'];
    $gid  = $c['gruppe_id'];

    // Teilnehmer dieser Gruppe holen
    $teilnehmerStmt = $pdo->prepare("
        SELECT teilnehmer_id
        FROM mtr_rueckkopplung_teilnehmer
        WHERE gruppe_id = :gid
    ");
    $teilnehmerStmt->execute([":gid" => $gid]);
    $tids = $teilnehmerStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$tids) {
        echo "⚠ Keine Teilnehmer für Gruppe {$gid} / UE {$ueid}.\n";
        continue;
    }

    $idList = implode(",", array_map("intval", $tids));

    // --- Aggregation über diese Teilnehmer ---
    // Wir definieren Relationstypen heuristisch aus den Dimensionen:
    // kognitiv, sozial, affektiv, insgesamt
    $relationen = [
        "Kognitiv" => "AVG(x_kognition)",
        "Sozial"   => "AVG(y_sozial)",
        "Affektiv" => "AVG(z_affektiv)",
        "Gesamt"   => "AVG(h_bedeutung)"
    ];

    foreach ($relationen as $typ => $expr) {
        $sqlAgg = "
            SELECT 
              $expr AS staerke,
              AVG(kohärenz_index) AS koh,
              MAX(zeitpunkt) AS zeit
            FROM frzk_interdependenz
            WHERE teilnehmer_id IN ($idList)
        ";
        $stmtAgg = $pdo->query($sqlAgg);
        $row = $stmtAgg->fetch();

        if ($row && $row['zeit']) {
            $insert->execute([
                ":ueid"     => $ueid,
                ":gid"      => $gid,
                ":zeit"     => $row["zeit"],
                ":typ"      => $typ,
                ":staerke"  => $row["staerke"],
                ":koh"      => $row["koh"]
            ]);
            echo "✔ Gruppe {$gid} / UE {$ueid} – {$typ} aggregiert.\n";
        }
    }
}

echo "✅ Aggregation FRZK-Gruppen-Interdependenz abgeschlossen.\n";
/*
fill_frzk_gruppen_loops.php
<?php
header('Content-Type:text/plain;charset=utf-8');
$pdo=new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec("TRUNCATE TABLE frzk_gruppen_loops");
*/
// fill_frzk_gruppen_loops.php
// Aggregiert die Teilnehmer-Loops (frzk_loops) zu Gruppen-Loops (frzk_gruppen_loops)


// Gruppentabelle leeren
echo "Starte Aggregation FRZK-Gruppen-Loops...\n";

// Unterrichtseinheit/Gruppen bestimmen
$sql = "
SELECT 
  uzt.ue_unterrichtseinheit_zw_thema_id AS unterrichtseinheit_id,
  mrt.gruppe_id
FROM mtr_rueckkopplung_teilnehmer mrt
JOIN ue_zuweisung_teilnehmer uzt 
  ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
GROUP BY uzt.ue_unterrichtseinheit_zw_thema_id, mrt.gruppe_id
";
$stmt = $pdo->query($sql);
$combos = $stmt->fetchAll();

// INSERT vorbereiten
$insert = $pdo->prepare("
INSERT INTO frzk_gruppen_loops
(unterrichtseinheit_id, gruppe_id, anzahl_teilnehmer, avg_loops, var_loops, loop_count, created_at)
VALUES (:ueid, :gid, :anz, :avg, :var, :count, NOW())
");

foreach ($combos as $c) {
    $ueid = $c['unterrichtseinheit_id'];
    $gid  = $c['gruppe_id'];

    // Teilnehmerliste der Gruppe bestimmen
    $tidStmt = $pdo->prepare("
        SELECT teilnehmer_id 
        FROM mtr_rueckkopplung_teilnehmer 
        WHERE gruppe_id = :gid
    ");
    $tidStmt->execute([":gid" => $gid]);
    $tids = $tidStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$tids) {
        echo "⚠ Keine Teilnehmer für Gruppe {$gid} / UE {$ueid}.\n";
        continue;
    }

    $anzTeilnehmer = count($tids);
    $idList = implode(",", array_map("intval", $tids));

    // Aggregation aus frzk_loops
    $sqlAgg = "
        SELECT 
            COUNT(*) AS loop_count,
            AVG(dauer) AS avg_loops,
            VAR_POP(dauer) AS var_loops
        FROM frzk_loops
        WHERE teilnehmer_id IN ($idList)
    ";

    $stmtAgg = $pdo->query($sqlAgg);
    $row = $stmtAgg->fetch();

    $loopCount = (int)($row["loop_count"] ?? 0);
    $avgLoops  = (float)($row["avg_loops"] ?? 0);
    $varLoops  = (float)($row["var_loops"] ?? 0);

    // Einfügen in Gruppentabelle
    $insert->execute([
        ":ueid" => $ueid,
        ":gid"  => $gid,
        ":anz"  => $anzTeilnehmer,
        ":avg"  => $avgLoops,
        ":var"  => $varLoops,
        ":count"=> $loopCount
    ]);

    echo "✔ Gruppe {$gid} / UE {$ueid} → {$loopCount} Loops, Ø{$avgLoops}, Var{$varLoops}\n";
}

echo "✅ Aggregation FRZK-Gruppen-Loops abgeschlossen.\n";
/*
// fill_frzk_gruppen_hubs.php
// Aggregiert Hubs (frzk_hubs) auf Gruppenebene pro Unterrichtseinheit

header('Content-Type:text/plain; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Zieltabelle leeren
$pdo->exec("TRUNCATE TABLE frzk_gruppen_hubs");
*/
echo "Starte Aggregation FRZK-Gruppen-Loops (korrigiert)...\n";

// 1) Bestimme alle Kombinationen (echte unterrichtseinheit_id aus uet.ue_unterrichtseinheit_id)
$sqlCombos = "
SELECT DISTINCT 
  uet.ue_unterrichtseinheit_id AS unterrichtseinheit_id,
  mrt.gruppe_id
FROM mtr_rueckkopplung_teilnehmer mrt
JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
JOIN ue_unterrichtseinheit_zw_thema uet ON uzt.ue_unterrichtseinheit_zw_thema_id = uet.id
";
$combos = $pdo->query($sqlCombos)->fetchAll();

$insert = $pdo->prepare("
INSERT INTO frzk_gruppen_loops
(unterrichtseinheit_id, gruppe_id, anzahl_teilnehmer, avg_loops, var_loops, loop_count, created_at)
VALUES (:ueid, :gid, :anz, :avg, :var, :count, NOW())
");

foreach ($combos as $c) {
    $ueid = (int)$c['unterrichtseinheit_id'];
    $gid  = (int)$c['gruppe_id'];

    // 2) Teilnehmer eindeutig ermitteln - DISTINCT, gefiltert nach UE und Gruppe
    $tstmt = $pdo->prepare("
        SELECT DISTINCT uzt.teilnehmer_id
        FROM mtr_rueckkopplung_teilnehmer mrt
        JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
        JOIN ue_unterrichtseinheit_zw_thema uet ON uzt.ue_unterrichtseinheit_zw_thema_id = uet.id
        WHERE uet.ue_unterrichtseinheit_id = :ue
          AND mrt.gruppe_id = :gid
    ");
    $tstmt->execute([':ue' => $ueid, ':gid' => $gid]);
    $tids = $tstmt->fetchAll(PDO::FETCH_COLUMN);

    $anzTeilnehmer = count($tids);
    if ($anzTeilnehmer === 0) {
        echo "→ UE {$ueid} / Gruppe {$gid}: keine eindeutigen Teilnehmer gefunden, übersprungen.\n";
        continue;
    }

    // 3) Aggregiere Loops nur für diese eindeutigen Teilnehmer
    $idList = implode(',', array_map('intval', $tids)); // safe because intval

    $sqlAgg = "
        SELECT 
            COUNT(*) AS loop_count,
            AVG(dauer) AS avg_loops,
            VAR_POP(dauer) AS var_loops
        FROM frzk_loops
        WHERE teilnehmer_id IN ($idList)
    ";
    $agg = $pdo->query($sqlAgg)->fetch(PDO::FETCH_ASSOC);

    $loopCount = (int)($agg['loop_count'] ?? 0);
    $avgLoops  = isset($agg['avg_loops']) ? (float)$agg['avg_loops'] : null;
    $varLoops  = isset($agg['var_loops']) ? (float)$agg['var_loops'] : null;

    // 4) Insert / Upsert
    $insert->execute([
        ':ueid' => $ueid,
        ':gid'  => $gid,
        ':anz'  => $anzTeilnehmer,
        ':avg'  => $avgLoops,
        ':var'  => $varLoops,
        ':count'=> $loopCount
    ]);

    echo "→ UE {$ueid} / Gruppe {$gid}: Teilnehmer={$anzTeilnehmer}, Loops={$loopCount}, avg=" . ($avgLoops===null?"NULL":round($avgLoops,2)) . "\n";
}

echo "✅ Fertig: FRZK-Gruppen-Loops (korrigiert)\n";

// Optional: JSON-Export
file_put_contents(__DIR__."/frzk_gruppen_hubs.json",
  json_encode($pdo->query("SELECT * FROM frzk_gruppen_hubs")->fetchAll(), 
  JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

?>
