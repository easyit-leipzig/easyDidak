<?php
// fill_frzk_interdependenz.php
// Berechnet funktionale Interdependenz (x,y,z,h) aus frzk_semantische_dichte
// und füllt die Tabelle frzk_interdependenz vollständig.
// FRZK-Bezug: Kap. 5.2.2 „Funktionale Kopplung“, Kap. 6.1 „Kohärenzfelder“.

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabelle prüfen / anlegen ---
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
?>
