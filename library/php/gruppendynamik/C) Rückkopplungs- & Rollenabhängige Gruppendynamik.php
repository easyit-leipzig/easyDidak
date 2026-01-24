<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");

// Ziel-Tabelle
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_regulation (
  gruppe_id INT PRIMARY KEY,
  mean_abs_dh FLOAT,
  var_dh FLOAT,
  loop_density FLOAT,
  stabilitaet_mean FLOAT,
  regulation_score FLOAT,
  regulation_typ VARCHAR(40),
  bemerkung TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("TRUNCATE frzk_group_regulation");

// ---- Daten laden
$dh = $pdo->query("
SELECT gruppe_id, ABS(dh_dt) AS dh
FROM frzk_group_semantische_dichte
WHERE dh_dt IS NOT NULL
")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);

$loops = $pdo->query("
SELECT gruppe_id, COUNT(*) AS c
FROM frzk_group_loops
GROUP BY gruppe_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$stab = $pdo->query("
SELECT gruppe_id, AVG(stabilitaet_score) AS s
FROM frzk_group_operatoren
GROUP BY gruppe_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$ins = $pdo->prepare("
INSERT INTO frzk_group_regulation
(gruppe_id,mean_abs_dh,var_dh,loop_density,
 stabilitaet_mean,regulation_score,regulation_typ,bemerkung)
VALUES (?,?,?,?,?,?,?,?)
");

foreach ($dh as $gid=>$vals) {
    $n = count($vals);
    if (!$n) continue;

    $mean = array_sum($vals)/$n;
    $var  = array_sum(array_map(fn($v)=>($v-$mean)**2,$vals))/$n;
    $loopD = ($loops[$gid] ?? 0)/max(1,$n);
    $stabM = $stab[$gid] ?? 0;

    // 🔑 Regulation-Score
    $R = min(1,
        0.4*$mean +
        0.4*$var +
        0.2*(1-$loopD)
    );

    if ($R < 0.3)      $typ="Selbstregulierend";
    elseif ($R < 0.6)  $typ="Strukturell geführt";
    else               $typ="Regulationsabhängig";

    $bem = sprintf(
      "⟨|Δh|⟩=%.3f Var=%.3f Loops=%.2f Stabilität=%.2f",
      $mean,$var,$loopD,$stabM
    );

    $ins->execute([$gid,$mean,$var,$loopD,$stabM,$R,$typ,$bem]);
}

echo "✅ C) frzk_group_regulation erstellt\n";                                                                
$sql = "select gruppe_id, mean_abs_dh, var_dh, loop_density from frzk_group_regulation where gruppe_id < 10";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
file_put_contents("C) Rückkopplungs- & Rollenabhängige Gruppendynamik.json",json_encode($data, JSON_PRETTY_PRINT));  
  
?>
