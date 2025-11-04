<?php
// 📊 analyze_frzk_transitions.php
// Erstellt eine Transitionsmatrix + Statistik aus frzk_transitions
// Läuft standalone (Variante ohne Foreign Keys)

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Daten abrufen ---
$stmt = $pdo->query("SELECT * FROM frzk_transitions ORDER BY teilnehmer_id, zeitpunkt");
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "⚠️  Keine Transitionsdaten gefunden.\n";
    exit;
}

// --- Initialisierung ---
$matrix = [];
$typeStats = [];
$intensitySum = [];
$intensityCount = [];

// --- Datenverarbeitung ---
foreach ($rows as $r) {
    $von = (int)$r["von_cluster"];
    $nach = (int)$r["nach_cluster"];
    $typ = $r["transition_typ"];
    $inten = (float)$r["transition_intensitaet"];

    // Matrixzählung
    if (!isset($matrix[$von])) $matrix[$von] = [];
    if (!isset($matrix[$von][$nach])) $matrix[$von][$nach] = 0;
    $matrix[$von][$nach]++;

    // Intensität sammeln
    if (!isset($intensitySum[$von][$nach])) $intensitySum[$von][$nach] = 0;
    if (!isset($intensityCount[$von][$nach])) $intensityCount[$von][$nach] = 0;
    $intensitySum[$von][$nach] += $inten;
    $intensityCount[$von][$nach]++;

    // Typ-Statistik
    if (!isset($typeStats[$typ])) $typeStats[$typ] = 0;
    $typeStats[$typ]++;
}

// --- Durchschnittsintensität berechnen ---
$intensityAvg = [];
foreach ($intensitySum as $von => $targets) {
    foreach ($targets as $nach => $sum) {
        $intensityAvg[$von][$nach] = $intensityCount[$von][$nach] > 0
            ? round($sum / $intensityCount[$von][$nach], 3)
            : 0;
    }
}

// --- Ausgabe ---
echo "=========================================\n";
echo "🧩 FRZK-Transitionsanalyse\n";
echo "=========================================\n\n";

echo "🔹 Transitionsmatrix (Häufigkeit von Cluster i → j):\n";
foreach ($matrix as $von => $targets) {
    foreach ($targets as $nach => $count) {
        $avg = $intensityAvg[$von][$nach] ?? 0;
        printf("  Cluster %d → %d: %d Übergänge (Ø Intensität %.2f)\n", $von, $nach, $count, $avg);
    }
}
echo "\n";

echo "🔹 Transitionstypen-Verteilung:\n";
foreach ($typeStats as $typ => $anz) {
    printf("  %-15s : %d\n", $typ, $anz);
}
echo "\n";

// --- JSON exportieren ---
$export = [
    "timestamp" => date("c"),
    "matrix" => $matrix,
    "intensity_average" => $intensityAvg,
    "type_distribution" => $typeStats
];
file_put_contents(__DIR__ . "/frzk_transitions_analysis.json", json_encode($export, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo "✅ Analyse abgeschlossen. Ergebnisse gespeichert in: frzk_transitions_analysis.json\n";
?>
