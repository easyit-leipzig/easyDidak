<?php
/**
 * frzk_transitions_to_json.php
 *
 * Exportiert individuelle FRZK-Transitionen als JSON
 * zur Weiterverarbeitung in Python (Orientierungsverlust, Stabilität, Dynamik)
 */

header('Content-Type: application/json; charset=utf-8');

// --------------------------------------------------
// DB-KONFIGURATION
// --------------------------------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'icas';
$DB_USER = 'root';
$DB_PASS = '';

// --------------------------------------------------
// DB-VERBINDUNG
// --------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// SQL-ABFRAGE
// --------------------------------------------------
$sql = "
SELECT
    teilnehmer_id,

    zeitpunkt_von,
    zeitpunkt_nach,

    cluster_von,
    cluster_nach,

    x_von, y_von, z_von,
    x_nach, y_nach, z_nach,

    h_von,
    h_nach,

    delta,
    transition_typ
FROM frzk_transitions
ORDER BY teilnehmer_id, zeitpunkt_von
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// --------------------------------------------------
// STRUKTURIERUNG: TEILNEHMER → TRANSITIONEN
// --------------------------------------------------
$result = [
    'meta' => [
        'exported_at' => date('c'),
        'source'      => 'frzk_transitions',
        'description' => 'Individuelle FRZK-Zustandsübergänge zur Analyse von Orientierungsverlust'
    ],
    'teilnehmer' => []
];

foreach ($rows as $row) {
    $tid = $row['teilnehmer_id'];

    if (!isset($result['teilnehmer'][$tid])) {
        $result['teilnehmer'][$tid] = [
            'teilnehmer_id' => $tid,
            'transitions'   => []
        ];
    }

    $result['teilnehmer'][$tid]['transitions'][] = [
        'zeitpunkt_von'  => $row['zeitpunkt_von'],
        'zeitpunkt_nach' => $row['zeitpunkt_nach'],

        'cluster' => [
            'von'  => $row['cluster_von'],
            'nach' => $row['cluster_nach']
        ],

        'position_von' => [
            'x' => (float)$row['x_von'],
            'y' => (float)$row['y_von'],
            'z' => (float)$row['z_von'],
            'h' => isset($row['h_von']) ? (float)$row['h_von'] : null
        ],

        'position_nach' => [
            'x' => (float)$row['x_nach'],
            'y' => (float)$row['y_nach'],
            'z' => (float)$row['z_nach'],
            'h' => isset($row['h_nach']) ? (float)$row['h_nach'] : null
        ],

        'delta'          => (float)$row['delta'],
        'transition_typ'=> $row['transition_typ']
    ];
}

// Re-Index für sauberes JSON (keine PHP-Keys)
$result['teilnehmer'] = array_values($result['teilnehmer']);

// --------------------------------------------------
// JSON-AUSGABE
// --------------------------------------------------
echo json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
