<?php
// DB-Verbindung aufbauen (bitte Zugangsdaten anpassen)
$dsn = "mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Alle Students holen
$sqlStudents = "SELECT first_name, last_name, grade FROM students";
$stmt = $pdo->query($sqlStudents);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update vorbereiten
$sqlUpdate = "UPDATE std_teilnehmer 
              SET Klassenstufe = :grade 
              WHERE Vorname = :vorname 
              AND Nachname = :nachname";
$updateStmt = $pdo->prepare($sqlUpdate);

$countUpdated = 0;
foreach ($students as $student) {
    $ok = $updateStmt->execute([
        ':grade'   => $student['grade'],
        ':vorname' => $student['first_name'],
        ':nachname'=> $student['last_name']
    ]);
    if ($ok && $updateStmt->rowCount() > 0) {
        $countUpdated++;
    }
}

echo "Fertig. $countUpdated Datensätze aktualisiert.\n";
