<?php
/**
 * Extrahiert alle Bewertungsschluessel aus mtr_rueckkopplung_datenmaske.bemerkung
 * und fügt sie (ohne Duplikate) in _mtr_datenmaske_schluessel ein.
 * 
 * @author Olaf Thiele
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$dsn  = 'mysql:host=localhost;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Verbindung hergestellt\n";

    // --------------------------------------------------
    // 1️⃣ Ziel-Tabelle erzeugen (falls nicht vorhanden)
    // --------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `_mtr_datenmaske_schluessel` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `schluessel` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `schluessel` (`schluessel`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "✅ Tabelle '_mtr_datenmaske_schluessel' bereit\n";

    // --------------------------------------------------
    // 2️⃣ Alle 'bemerkung'-Felder auslesen
    // --------------------------------------------------
    $sql = "SELECT bemerkung FROM mtr_rueckkopplung_datenmaske
            WHERE bemerkung IS NOT NULL AND bemerkung != ''";
    $stmt = $pdo->query($sql);

    $alleSchluessel = [];

    while ($row = $stmt->fetch()) {
        $text = trim($row['bemerkung']);
        if ($text === '') continue;

        // 🔹 mögliche Trennzeichen: Komma, Semikolon, Slash, Zeilenumbruch
        $teile = preg_split('/[,;\/\r\n]+/', $text);

        foreach ($teile as $teil) {
            $schl = trim($teil);
            if ($schl !== '') {
                $alleSchluessel[$schl] = true;  // als assoziatives Array für Eindeutigkeit
            }
        }
    }

    $anzahl = count($alleSchluessel);
    echo "📦 Gefundene eindeutige Schlüssel: $anzahl\n";

    if ($anzahl === 0) {
        echo "⚠️ Keine Schlüssel gefunden – Abbruch.\n";
        exit;
    }

    // --------------------------------------------------
    // 3️⃣ Schlüssel einfügen mit ON DUPLICATE KEY UPDATE
    // --------------------------------------------------
    $pdo->beginTransaction();
    $insert = $pdo->prepare("
        INSERT INTO _mtr_datenmaske_schluessel (schluessel)
        VALUES (:schluessel)
        ON DUPLICATE KEY UPDATE schluessel = VALUES(schluessel)
    ");

    $count = 0;
    foreach (array_keys($alleSchluessel) as $s) {
        $insert->execute([':schluessel' => $s]);
        $count++;
    }

    $pdo->commit();

    echo "✅ Erfolgreich $count Schlüssel eingetragen / aktualisiert.\n";
    echo "💾 Vorgang abgeschlossen.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("❌ Datenbankfehler: " . $e->getMessage() . "\n");
}
