<?php
// migrate_values_to_textitems.php
// Liest _mtr_datenmaske_values_wertung aus ICAS und überträgt offene Texte in mtr_textitems

$host = 'localhost';
$dbname = 'icas';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// 1. Neue Tabelle erstellen (falls nicht vorhanden)
$createTableSQL = "
CREATE TABLE IF NOT EXISTS mtr_textitems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    hub_id INT NULL,
    value_text TEXT NOT NULL,
    item_typ VARCHAR(50) DEFAULT NULL,
    wichtung FLOAT DEFAULT 1.0,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teilnehmer (teilnehmer_id),
    INDEX idx_hub (hub_id)
)";
$pdo->exec($createTableSQL);

// 2. Alle offenen Items aus _mtr_datenmaske_values_wertung abrufen
$sqlSelect = "SELECT id AS teilnehmer_id, value FROM _mtr_datenmaske_values_wertung WHERE value IS NOT NULL AND TRIM(value) <> ''";
$stmt = $pdo->query($sqlSelect);
$items = $stmt->fetchAll();

// 3. Daten in mtr_textitems einfügen
$insertSQL = "INSERT INTO mtr_textitems (teilnehmer_id, value_text, item_typ, wichtung) VALUES (:teilnehmer_id, :value_text, :item_typ, :wichtung)";
$insertStmt = $pdo->prepare($insertSQL);

foreach ($items as $row) {
    $insertStmt->execute([
        ':teilnehmer_id' => $row['teilnehmer_id'],
        ':value_text' => $row['value'],
        ':item_typ' => 'offen',   // Standardtyp, kann später angepasst werden
        ':wichtung' => 1.0
    ]);
}

echo "Migration abgeschlossen. " . count($items) . " Items übertragen.\n";
?>
