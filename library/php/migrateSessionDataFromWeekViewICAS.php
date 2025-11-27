<?php
// ==========================
// DB-Verbindung
// ==========================
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_POST["rawdata"])) {
    exit("Keine Daten erhalten.");
}

$input = $_POST["rawdata"];

// ==========================
// Hilfsfunktionen
// ==========================

function insertRoom($pdo, $room) {
    var_dump($room);
    $stmt = $pdo->prepare("INSERT IGNORE INTO rooms (name) VALUES (?)");
    $stmt->execute([$room]);

    return $pdo->lastInsertId() ?: $pdo->query("SELECT room_id FROM rooms WHERE name='$room'")->fetchColumn();
}

function insertSubject($pdo, $subject) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO subjects (code) VALUES (?)");
    $stmt->execute([$subject]);

    return $pdo->lastInsertId() ?: $pdo->query("SELECT subject_id FROM subjects WHERE code='$subject'")->fetchColumn();
}

function insertStudent($pdo, $first, $last, $grade, $schoolform, $valid_until) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO students (first_name, last_name, grade, school_form, valid_until)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$first, $last, $grade, $schoolform, $valid_until]);

    // Falls existiert:
    if ($stmt->rowCount() == 0) {
        return $pdo->query("
            SELECT student_id FROM students
            WHERE first_name='$first' AND last_name='$last'
        ")->fetchColumn();
    }

    return $pdo->lastInsertId();
}

function insertSession($pdo, $date, $start, $end, $subject_id, $room_id) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO sessions (session_date, start_time, end_time, subject_id, room_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$date, $start, $end, $subject_id, $room_id]);

    // existierende Session holen
    if ($stmt->rowCount() == 0) {
        $stmt2 = $pdo->prepare("
            SELECT session_id FROM sessions
            WHERE session_date=? AND start_time=? AND room_id=? AND subject_id=?
        ");
        $stmt2->execute([$date, $start, $room_id, $subject_id]);
        return $stmt2->fetchColumn();
    }

    return $pdo->lastInsertId();
}

function addStudentToSession($pdo, $sid, $studid) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO session_students (session_id, student_id) VALUES (?, ?)");
    $stmt->execute([$sid, $studid]);
}

// ==========================
// Rohtext in Zeilen zerlegen
// ==========================
$lines = explode("\n", $input);

// Parser Zustand
$currentDate = null;
$currentStart = null;
$currentEnd   = null;
$currentRoom  = null;
$currentSubject = null;
$currentSessionId = null;

$dateRegex = "/^(Mo\.|Di\.|Mi\.|Do\.|Fr\.|Sa\.|So\.) (\d{2})\.(\d{2})\.(\d{2})$/";
$sessionRegex = "/^(\d{2}:\d{2}) - (\d{2}:\d{2}) (MAT|PHY) Rm\. (\d+)/";
$studentRegex = "/^([A-Za-zÄÖÜäöüß ,.-]+)\s+(\d{1,2})\s+(GYM|RS|GR)\s+(MAT|PHY)$/";
$validUntilRegex = "/bis (\d{2})\.(\d{2})\.(\d{2})/";

// ==========================
// Parsing
// ==========================

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === "") continue;

    // ------------------------------------------
    // Datum erkannt
    // ------------------------------------------
    if (preg_match($dateRegex, $line, $m)) {
        $currentDate = "20{$m[4]}-{$m[3]}-{$m[2]}";
        continue;
    }

    // ------------------------------------------
    // Session erkannt
    // ------------------------------------------
    if (preg_match($sessionRegex, $line, $m)) {
        $currentStart = $m[1] . ":00";
        $currentEnd   = $m[2] . ":00";
        $currentSubject = $m[3];
        $currentRoom  = "Rm. " . $m[4];

        $roomId = insertRoom($pdo, $currentRoom);
        $subjectId = insertSubject($pdo, $currentSubject);

        $currentSessionId = insertSession(
            $pdo,
            $currentDate,
            $currentStart,
            $currentEnd,
            $subjectId,
            $roomId
        );
        continue;
    }

    // ------------------------------------------
    // Schülerzeile inkl. Ablaufdatum
    // ------------------------------------------
    if (preg_match($studentRegex, $line, $m)) {

        // Vorname + Nachname trennen
        $fullname = trim($m[1]);
        $nameParts = explode(",", $fullname);
        $last = trim($nameParts[0]);
        $first = trim($nameParts[1] ?? "");

        // Klasse / Schulform
        $grade = $m[2];
        $schoolform = $m[3];

        // "bis …" suchen
        $valid = null;
        if (preg_match($validUntilRegex, $line, $v)) {
            $valid = "20{$v[3]}-{$v[2]}-{$v[1]}";
        }

        // Schüler in DB einfügen
        $studId = insertStudent($pdo, $first, $last, $grade, $schoolform, $valid);

        // Session ↔ Schüler verknüpfen
        addStudentToSession($pdo, $currentSessionId, $studId);

        continue;
    }
}

echo "<h2>Import abgeschlossen ✔</h2>";
echo "<p>Alle Daten wurden duplikatsfrei übernommen.</p>";
