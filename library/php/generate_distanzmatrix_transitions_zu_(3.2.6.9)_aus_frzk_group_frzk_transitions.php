<?php
// =====================================================
// generate_distanzmatrix_transitions.php
// Erzeugt Distanzmatrix M(U)=D_ij aus dynamischen FRZK-Verläufen
// Tabelle: frzk_group_frzk_transitions
// =====================================================

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    ""
);

// 1. Transition-Daten abrufen
$sql = "SELECT id,
               avg_dh_dt,
               std_dh_dt,
               kohärenz_index
        FROM frzk_group_frzk_transitions
        ORDER BY id ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Vektoren erzeugen
$U = [];
foreach ($rows as $r) {
    $U[$r['id']] = [
        (float)$r['avg_dh_dt'],
        (float)$r['std_dh_dt'],
        (float)$r['kohärenz_index']
    ];
}

// 3. Distanzmetrik
function dist($a, $b) {
    $sum = 0;
    for ($k = 0; $k < count($a); $k++) {
        $sum += pow($a[$k] - $b[$k], 2);
    }
    return sqrt($sum);
}

// 4. Distanzmatrix berechnen
$keys = array_keys($U);
$D = [];

foreach ($keys as $i) {
    foreach ($keys as $j) {
        $D[$i][$j] = dist($U[$i], $U[$j]);
    }
}

// 5. JSON speichern
file_put_contents("distanzmatrix_transitions.json", json_encode($D, JSON_PRETTY_PRINT));

// 6. HTML-Ausgabe
echo "<h2>Distanzmatrix – Transition-Dynamiken</h2>";
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
