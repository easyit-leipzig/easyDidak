<?php
// =====================================================
// generate_distanzmatrix_profiles.php
// Erzeugt M(U)=D_ij als Distanzmatrix aus Lernprofilen
// Tabelle: mtr_rueckkopplung_datenmaske
// =====================================================

// DB-Verbindung
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    ""
);

// 1. Daten abrufen
$sql = "SELECT id,
               metr_kognition,
               metr_sozial,
               metr_affektiv,
               metr_metakog,
               metr_kohärenz
        FROM mtr_rueckkopplung_lehrkraft_tn
        ORDER BY id ASC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Vektoren U erzeugen
$U = [];
foreach ($rows as $r) {
    $U[$r['id']] = [
        (float)$r['metr_kognition'],
        (float)$r['metr_sozial'],
        (float)$r['metr_affektiv'],
        (float)$r['metr_metakog'],
        (float)$r['metr_kohärenz']
    ];
}

// 3. Distanzfunktion d(u_i, u_j)
function dist($a, $b) {
    $sum = 0.0;
    for ($k = 0; $k < count($a); $k++) {
        $sum += pow($a[$k] - $b[$k], 2);
    }
    return sqrt($sum);
}

// 4. Distanzmatrix D_ij erzeugen
$D = [];
$keys = array_keys($U);

foreach ($keys as $i) {
    foreach ($keys as $j) {
        $D[$i][$j] = dist($U[$i], $U[$j]);
    }
}

// 5. Ausgabe als JSON speichern (optional)
file_put_contents("distanzmatrix_profiles.json", json_encode($D, JSON_PRETTY_PRINT));

// 6. HTML-Ausgabe (falls direkt im Browser)
echo "<h2>Distanzmatrix M(U)=D<sub>ij</sub> aus Lernprofilen</h2>";
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
