<?php
/**
 * FRZK – Kapitel-6 JSON-Export
 * Quelle: MySQL / MariaDB (icas.sql + FRZK-Tabellen)
 * Ziel: JSON für automatische Text- & Visualisierungsgenerierung
 */

header('Content-Type: application/json; charset=utf-8');

// --------------------
// DB-Konfiguration
// --------------------
$dsn = "mysql:host=localhost;dbname=icas;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// --------------------
// Profile laden
// --------------------
$profilesStmt = $pdo->query("
    SELECT *
    FROM _frzk_orverlust_schuelerprofil_definition
    WHERE aktiv = 1
    ORDER BY id
");

$profiles = [];

while ($p = $profilesStmt->fetch(PDO::FETCH_ASSOC)) {

    $profil_id = (int)$p['id'];

    // --------------------
    // Kapiteltext-Bausteine
    // --------------------
    $textStmt = $pdo->prepare("
        SELECT kontext, text
        FROM _frzk_orverlust_kapiteltext_bausteine
        WHERE profil_id = ?
        ORDER BY FIELD(
            kontext,
            'profil_definition',
            'interpretation',
            'didaktik',
            'fehler',
            'intervention'
        )
    ");
    $textStmt->execute([$profil_id]);

    $kapiteltext = [];
    while ($t = $textStmt->fetch(PDO::FETCH_ASSOC)) {
        $kapiteltext[$t['kontext']][] = $t['text'];
    }

    // --------------------
    // Interventionspfad
    // --------------------
    $pfadStmt = $pdo->prepare("
        SELECT schritt_nr, phase, beschreibung
        FROM _frzk_orverlust_profil_interventionspfad
        WHERE profil_id = ?
        ORDER BY schritt_nr
    ");
    $pfadStmt->execute([$profil_id]);

    $interventionen = [];
    while ($i = $pfadStmt->fetch(PDO::FETCH_ASSOC)) {
        $interventionen[] = [
            "schritt" => (int)$i['schritt_nr'],
            "phase"   => $i['phase'],
            "text"    => $i['beschreibung']
        ];
    }

    // --------------------
    // Profilobjekt
    // --------------------
    $profiles[] = [
        "profil_id" => $profil_id,
        "code"      => $p['profil_code'],
        "titel"     => $p['titel'],
        "typ"       => $p['epistemischer_typ'],
        "raum" => [
            "x" => [$p['x_min'], $p['x_max']],
            "y" => [$p['y_min'], $p['y_max']],
            "z" => [$p['z_min'], $p['z_max']]
        ],
        "kapiteltext" => $kapiteltext,
        "interventionspfad" => $interventionen
    ];
}

// --------------------
// JSON ausgeben
// --------------------
echo json_encode(
    [
        "meta" => [
            "quelle" => "FRZK",
            "kapitel" => "6",
            "erzeugt_am" => date("c")
        ],
        "profile" => $profiles
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
file_put_contents("Orientierungsverlüst_create_Schuelerprofil_Text.json", json_encode(
    [
        "meta" => [
            "quelle" => "FRZK",
            "kapitel" => "6",
            "erzeugt_am" => date("c")
        ],
        "profile" => $profiles
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
);
