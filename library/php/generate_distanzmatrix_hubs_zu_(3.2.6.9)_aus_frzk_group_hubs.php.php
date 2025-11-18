<?php
// =====================================================
// generate_distanzmatrix_hubs.php
// Erzeugt Distanzmatrix M(U)=D_ij aus Hub-Daten
// Tabelle: frzk_group_hubs
// =====================================================

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    ""
);

// 1. Hubs abrufen
$sql = "SELECT id,
               avg_x,
               avg_y,
               avg_z,
               avg_h,
               var_h,
               z_h
        FROM frzk_group_hubs
        ORDER BY id ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Vektorraum U aufbauen
$U = [];
foreach ($rows as $r) {
    $U[$r['id']] = [
        (float)$r['avg_x'],
        (float)$r['avg_y'],
        (float)$r['avg_z'],
        (float)$r['avg_h'],
        (float)$r['var_h'],
        (float)$r['z_h']
    ];
}

// 3. Metrik d(u_i, u_j)
function dist($a, $b) {
    $sum = 0;
    for ($k = 0; $k < count($a); $k++) {
        $sum += pow($a[$k] - $b[$k], 2);
    }
    return sqrt($sum);
}

// 4. Distanzmatrix erstellen
$keys = array_keys($U);
$D = [];

foreach ($keys as $i) {
    foreach ($keys as $j) {
        $D[$i][$j] = dist($U[$i], $U[$j]);
    }
}

// 5. JSON speichern
file_put_contents("distanzmatrix_hubs.json", json_encode($D, JSON_PRETTY_PRINT));

// 6. HTML-Ausgabe
echo "<h2>Distanzmatrix – Hubs</h2>";
echo "<table border='1' cellpadding='3' cellspacing='0'>";

echo "<tr><th></th>";
foreach ($keys as $j) echo "<th>$j</th>";
echo "</tr>";

foreach ($keys as $i) {
    echo "<tr><th>$i</th>";
    foreach ($keys as $j) {
        printf("<td>%.3f</td>", $D[$i][$j]);
    }
    echo "</tr>";
}

echo "</table>";
?>
