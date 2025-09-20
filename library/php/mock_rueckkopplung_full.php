<?php
// ================================================
// Mocking-Skript: Vollständige Befüllung
// mtr_rueckkopplung_lehrkraft_tn
// ================================================

$dsn = "mysql:host=localhost;dbname=icas;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
}

// ------------------------------------------------
// 1. Basisdaten holen
// ------------------------------------------------
$query = "SELECT id AS unterrichtseinheit_id, teilnehmer_id, datum, lernfortschritt, transferdenken, reflexionshinweis
          FROM mtr_leistung
          ORDER BY teilnehmer_id, datum";

$stmt = $pdo->query($query);

// ------------------------------------------------
// 2. Noten & Felder generieren
// ------------------------------------------------
$lehrkraftId = 1; // Dummy-Lehrkraft
$version = 1;

foreach ($stmt as $row) {
    $tid   = $row['teilnehmer_id'];
    $ueid  = $row['unterrichtseinheit_id'];
    $datum = $row['datum'];
    $lf    = (float)$row['lernfortschritt'];
    $tf    = (float)$row['transferdenken'];
    $rf    = (float)$row['reflexionshinweis'];

    // Gesamtscore berechnen (gewichtetes Mittel)
    $score = 0.6 * $lf + 0.3 * $tf + 0.1 * $rf;

    // Note 1–6
    $note = 6 - 5 * $score;
    $note = round(max(1, min(6, $note)), 1);

    // Punkte 0–100
    $punkte = round($score * 100);

    // Kategorie wählen
    if ($lf > $tf && $lf > $rf) {
        $kategorie = "fachlich";
    } elseif ($tf > $lf && $tf > $rf) {
        $kategorie = "methodisch";
    } else {
        $kategorie = "sozial";
    }

    // Feedbacktext generieren
    $feedback = "Gesamtnote: $note. 
        Fachliche Leistung: $lf, Transfer: $tf, Reflexion: $rf. 
        Schwerpunkt: $kategorie.";

    // ------------------------------------------------
    // 3. In Tabelle einfügen
    // ------------------------------------------------
    $insert = $pdo->prepare("
        INSERT INTO mtr_rueckkopplung_lehrkraft_tn
        (teilnehmer_id, unterrichtseinheit_id, datum, note, feedback_text, feedback_kategorie, bewertung_punkte, lehrkraft_id, version, created_at, updated_at)
        VALUES (:tid, :ueid, :datum, :note, :fb, :kat, :punkte, :lkid, :version, NOW(), NOW())
    ");

    $insert->execute([
        ':tid'     => $tid,
        ':ueid'    => $ueid,
        ':datum'   => $datum,
        ':note'    => $note,
        ':fb'      => $feedback,
        ':kat'     => $kategorie,
        ':punkte'  => $punkte,
        ':lkid'    => $lehrkraftId,
        ':version' => $version
    ]);
}

echo "mtr_rueckkopplung_lehrkraft_tn erfolgreich komplett befüllt!\n";
?>