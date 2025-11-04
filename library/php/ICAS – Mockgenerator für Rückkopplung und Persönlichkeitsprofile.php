<?php
/**
 * ICAS – Mockgenerator für Rückkopplung und Persönlichkeitsprofile
 * -----------------------------------------------------------------
 * Erstellt Mock-Daten in:
 *   - mtr_rueckkopplung_teilnehmer
 *   - mtr_persoenlichkeit
 *   - mtr_rueckkopplung_lehrkraft_tn
 * inklusive Übernahme des Feldes ue_zuweisung_teilnehmer_id.
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$pdo->query("truncate mtr_rueckkopplung_lehrkraft_tn")->execute();

$insert_person = 0;
$insert_lehrkraft = 0;
$errors = [];

// ======================================================
// 1. Teilnehmer laden
// ======================================================
$teilnehmer = $pdo->query("SELECT id FROM std_teilnehmer WHERE show_tn = 1")->fetchAll(PDO::FETCH_COLUMN);
if (!$teilnehmer) die("Keine Teilnehmer gefunden.\n");

// ======================================================
// 2. Emotionen laden
// ======================================================
$emotionen = ['freude', 'überraschung', 'interesse', 'angst', 'wut', 'trauer', 'gelassenheit', 'stolz', 'zuneigung', 'verwirrung'];

// ======================================================
// 3. Mock-Daten erzeugen
// ======================================================
foreach ($teilnehmer as $tid) {
    $anzahl_rueckmeldungen = rand(5, 10);
    for ($i = 0; $i < $anzahl_rueckmeldungen; $i++) {
        try {
            $datum = date('Y-m-d H:i:s', strtotime("2025-09-01 +" . rand(0, 60) . " days +" . rand(8, 17) . " hours +" . rand(0, 59) . " minutes"));
            $emotion = $emotionen[array_rand($emotionen)];

            // ==================================================
            // 3.1 Rückkopplung Teilnehmer einfügen
            // ==================================================
            $sql_tn = $pdo->prepare("
                INSERT INTO mtr_rueckkopplung_teilnehmer
                (teilnehmer_id, emotions, erfasst_am, ue_zuweisung_teilnehmer_id)
                VALUES (?, ?, ?, ?)
            ");
            $ue_zuweisung_teilnehmer_id = rand(1, 10); // zufällige Lehrerzuweisung
            $sql_tn->execute([$tid, $emotion, $datum, $ue_zuweisung_teilnehmer_id]);
            $rueck_id = $pdo->lastInsertId();

            // ==================================================
            // 3.2 Persönlichkeitsdaten mtr_persoenlichkeit
            // ==================================================
            $insert_p = $pdo->prepare("
                INSERT INTO mtr_persoenlichkeit
                (teilnehmer_id, datum, offenheit_erfahrungen, gewissenhaftigkeit, Extraversion, vertraeglichkeit,
                 zielorientierung, lernfaehigkeit, stressbewaeltigung, kreativitaet_innovation, problemloesefaehigkeit,
                 ko_kreationsfaehigkeit, resonanzfaehigkeit, bedeutungsbildung, metakognition, note)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $werte = [];
            for ($w = 0; $w < 13; $w++) $werte[] = round(mt_rand(15, 55) / 10, 1);
            $note = round(array_sum($werte) / count($werte), 1);

            $insert_p->execute(array_merge([$tid, date('Y-m-01', strtotime($datum))], $werte, [$note]));
            $insert_person++;

            // ==================================================
            // 3.3 Lehrkraft-Rückkopplung
            // ==================================================
            $insert_l = $pdo->prepare("
                INSERT INTO mtr_rueckkopplung_lehrkraft_tn
                (teilnehmer_id, erfasst_am, lehrkraft_id, kommentar, bewertung, ue_zuweisung_teilnehmer_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $kommentar = ucfirst($emotion) . " beobachtet – positive Entwicklung.";
            $bewertung = round(mt_rand(30, 50) / 10, 1);
            $lehrkraft_id = 1;

            $insert_l->execute([
                $tid,
                $datum,
                $lehrkraft_id,
                $kommentar,
                $bewertung,
                $ue_zuweisung_teilnehmer_id // direkte Übernahme aus Rückkopplung_teilnehmer
            ]);
            $insert_lehrkraft++;

        } catch (PDOException $e) {
            $errors[] = "Fehler bei Teilnehmer $tid ($datum): " . $e->getMessage();
        }
    }
}

// ======================================================
// 4. Abschlussbericht
// ======================================================
echo "✅ Mock-Generierung abgeschlossen.\n";
echo "Neue Datensaetze in mtr_persoenlichkeit: $insert_person\n";
echo "Neue Datensaetze in mtr_rueckkopplung_lehrkraft_tn: $insert_lehrkraft\n";

if ($errors) {
    echo "⚠️ Fehler:\n - " . implode("\n - ", $errors) . "\n";
} else {
    echo "Keine Fehler aufgetreten.\n";
}

?>
