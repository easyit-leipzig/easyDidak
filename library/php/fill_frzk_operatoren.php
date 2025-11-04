<?php
// fill_frzk_operatoren.php
// Berechnet σ-, M-, R-, E-Operatoren nach FRZK-Kriterien
// aus frzk_semantische_dichte + _mtr_emotionen.
// FRZK-Bezug: Kap. 4.2 „Operatorenlogik“, Kap. 5.4 „Operatorenräume“.

header('Content-Type: text/plain; charset=utf-8');

// --- Datenbankverbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabelle anlegen falls nötig ---
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
?>
