<?php
/**
 * FRZK – Einzel-Schülerprofil (advanced)
 * Features:
 *  - Zeitfenster
 *  - Profil-Wahrscheinlichkeiten
 *  - FRZK-konform (keine Stabilität)
 */

header('Content-Type: application/json; charset=utf-8');

// --------------------
// Parameter
// --------------------
$schueler_id = (int)($_GET['schueler_id'] ?? 0);
$von = $_GET['von'] ?? null;
$bis = $_GET['bis'] ?? null;

if ($schueler_id <= 0) {
    echo json_encode(["error" => "schueler_id fehlt"]);
    exit;
}

// --------------------
// DB
// --------------------
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// --------------------
// Schüler
// --------------------
$schueler = $pdo->prepare("
    SELECT id, vorname, nachname
    FROM schueler
    WHERE id = ?
");
$schueler->execute([$schueler_id]);
$s = $schueler->fetch(PDO::FETCH_ASSOC);
if (!$s) {
    echo json_encode(["error" => "Schüler nicht gefunden"]);
    exit;
}

// --------------------
// Zeitdaten (FRZK)
// --------------------
$sqlZeit = "
    SELECT zeitpunkt, x_wert, y_wert, z_wert
    FROM frzk_semantische_dichte
    WHERE schueler_id = ?
";
$params = [$schueler_id];

if ($von && $bis) {
    $sqlZeit .= " AND zeitpunkt BETWEEN ? AND ?";
    $params[] = $von;
    $params[] = $bis;
}

$zeitStmt = $pdo->prepare($sqlZeit);
$zeitStmt->execute($params);
$zeitdaten = $zeitStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$zeitdaten) {
    echo json_encode(["error" => "Keine Zeitdaten"]);
    exit;
}

// --------------------
// Aggregation (Momentanprofil)
// --------------------
$x = array_sum(array_column($zeitdaten,'x_wert')) / count($zeitdaten);
$y = array_sum(array_column($zeitdaten,'y_wert')) / count($zeitdaten);
$z = array_sum(array_column($zeitdaten,'z_wert')) / count($zeitdaten);

// --------------------
// Profile laden
// --------------------
$profile = $pdo->query("
    SELECT *
    FROM frzk_schuelerprofil_definition
    WHERE aktiv = 1
")->fetchAll(PDO::FETCH_ASSOC);

// --------------------
// Distanz & Wahrscheinlichkeit
// --------------------
$profileScores = [];
$total = 0.0;

foreach ($profile as $p) {
    $cx = ($p['x_min'] + $p['x_max']) / 2;
    $cy = ($p['y_min'] + $p['y_max']) / 2;
    $cz = ($p['z_min'] + $p['z_max']) / 2;

    $dist = sqrt(
        ($x - $cx)**2 +
        ($y - $cy)**2 +
        ($z - $cz)**2
    );

    $score = 1 / (1 + $dist);
    $profileScores[] = [
        "profil_id" => $p['id'],
        "code" => $p['profil_code'],
        "titel" => $p['titel'],
        "distanz" => round($dist,4),
        "score" => $score
    ];
    $total += $score;
}

// Normierung
foreach ($profileScores as &$ps) {
    $ps['wahrscheinlichkeit'] = round($ps['score'] / $total, 3);
}
unset($ps);

// Sortieren
usort($profileScores, fn($a,$b) =>
    $b['wahrscheinlichkeit'] <=> $a['wahrscheinlichkeit']
);

// --------------------
// JSON
// --------------------
echo json_encode([
    "schueler" => [
        "id" => $s['id'],
        "name" => $s['vorname']." ".$s['nachname']
    ],
    "zeitfenster" => [
        "von" => $von,
        "bis" => $bis,
        "anzahl_punkte" => count($zeitdaten)
    ],
    "frzk_position" => [
        "x" => round($x,3),
        "y" => round($y,3),
        "z" => round($z,3)
    ],
    "profil_wahrscheinlichkeiten" => $profileScores,
    "zeitreihe" => $zeitdaten
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
