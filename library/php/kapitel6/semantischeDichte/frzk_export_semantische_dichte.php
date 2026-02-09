<?php
// frzk_export_semantische_dichte.php
// Einheitlicher Export für FRZK-Semantische-Dichte-Visualisierung

header('Content-Type: application/json');

$host = "localhost";
$db   = "icas";
$user = "root";
$pass = "";

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$output = [
    "lehrkraft" => [],
    "schueler"  => [],
    "hubs"      => []
];

/* -----------------------------
 * Lehrkraftsicht
 * ----------------------------- */
$sql = "
SELECT
    teilnehmer_id,
    kognitiv,
    sozial,
    affektiv,
    emotional
FROM mtr_rueckkopplung_lehrkraft_datenmaske
";
foreach ($pdo->query($sql) as $row) {
    $output["lehrkraft"][] = [
        "id" => (int)$row["teilnehmer_id"],
        "position" => [
            "kognitiv" => (float)$row["kognitiv"],
            "sozial"   => (float)$row["sozial"],
            "affektiv" => (float)$row["affektiv"]
        ],
        "emotional" => (float)$row["emotional"]
    ];
}

/* -----------------------------
 * Schülersicht
 * ----------------------------- */

$sql = "
SELECT
    teilnehmer_id,
    x_kognition,
    y_sozial,
    z_affektiv
FROM frzk_semantische_dichte
";
foreach ($pdo->query($sql) as $row) {
    $output["schueler"][] = [
        "id" => (int)$row["teilnehmer_id"],
        "position" => [
            "kognition" => (float)$row["x_kognition"],
            "sozial"   => (float)$row["y_sozial"],
            "affektiv" => (float)$row["z_affektiv"]
        ]
    ];
}

/* -----------------------------
 * Hubs
 * ----------------------------- */
$sql = "
SELECT
    id,
    name,
    typ,
    gewicht_kognitiv,
    gewicht_sozial,
    gewicht_affektiv
FROM frzk_hubs
";
foreach ($pdo->query($sql) as $row) {
    $output["hubs"][] = [
        "id"   => (int)$row["id"],
        "name" => $row["name"],
        "typ"  => $row["typ"],
        "weight" => [
            "kognitiv" => (float)$row["gewicht_kognitiv"],
            "sozial"   => (float)$row["gewicht_sozial"],
            "affektiv" => (float)$row["gewicht_affektiv"]
        ]
    ];
}
file_put_contents("frzk_export_semantische_dichte.json", json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) );
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("frzk_export_semantische_dichte.json", json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) );
