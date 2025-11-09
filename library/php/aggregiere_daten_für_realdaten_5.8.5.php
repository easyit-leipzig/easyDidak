<?php
// config
$host = 'localhost';        // Datenbankhost
$db   = 'icas';  // Datenbankname
$user = 'root';        // DB-Benutzer
$pass = '';    // DB-Passwort
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Optionen für PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// SQL-Abfrage
$sql = "SELECT gruppe_id, mitarbeit, absprachen, selbststaendigkeit, konzentration, fleiss, lernfortschritt, beherrscht_thema, transferdenken, basiswissen, vorbereitet, themenauswahl, materialien, methodenvielfalt, individualisierung, aufforderung
        FROM mtr_rueckkopplung_teilnehmer
        WHERE gruppe_id > 0";

$stmt = $pdo->query($sql);

$outputFile = "data.txt";

// Schreibe die Daten in die Datei
$file = fopen($outputFile, "w");
if (!$file) {
    die("Konnte Datei nicht öffnen: $outputFile");
}

while ($row = $stmt->fetch()) {
    // Alle Werte als eine Zeile flach in die Datei schreiben
    $line = "[" . implode(",", $row) . "],"; // Tab-getrennt
    fwrite($file, $line . "\n");
}

fclose($file);

echo "Fertig! Daten wurden in '$outputFile' geschrieben.\n";
?>
