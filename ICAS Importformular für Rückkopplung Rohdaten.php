<?php
// import_datenmaske.php
// -----------------------------------------------------------
// Komplettes Import-Skript:
// - Rohtext parsen -> mtr_rueckkopplung_datenmaske einfügen
// - Dublettenprüfung (teilnehmer_id + datum) / skip fach = ORG
// - Metrikberechnung für neue Datensätze
// - Synchronisation -> mtr_rueckkopplung_lehrkraft_tn
//   (wenn passender ue_zuweisung_teilnehmer_id existiert -> UPDATE,
//    sonst INSERT)
// - Saubere Ergebnisausgabe
// -----------------------------------------------------------

ini_set('display_errors', 1);
error_reporting(E_ALL);

// === DB-Verbindung ===
$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ergebnis-Container
$errors = [];   // Dubletten
$skipped = [];  // skipped (fach = ORG)
$inserted = 0;
$metricsProcessed = 0;
$synced = 0;
$syncUpdated = 0;
$syncInserted = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $teilnehmer_id = intval($_POST['teilnehmer_id']);
    $raw = trim($_POST['rawdata']);

    if (!$teilnehmer_id || empty($raw)) {
        echo "<p style='color:red'>❌ Bitte Teilnehmer-ID und Rohdaten eingeben!</p>";
    } else {

    $lines = preg_split('/\r\n|\r|\n/', trim($raw));

    $entries = [];
    $current = [
        'fach' => null,
        'datum' => null,
        'lehrkraft' => null,
        'thema' => null,
        'bemerkungen' => []
    ];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_contains($line, '1 –') || str_contains($line, 'Schwerpunkte')) continue;

        // Datum + Lehrkraft erkennen
        if (preg_match('/^(\d{2}\.\d{2}\.\d{2})\s+(.+)$/', $line, $m)) {
            if ($current['datum'] && $current['fach']) {
                $entries[] = $current;
                $current = ['fach'=>null,'datum'=>null,'lehrkraft'=>null,'thema'=>null,'bemerkungen'=>[]];
            }
            $datum = DateTime::createFromFormat('d.m.y', $m[1]);
            $current['datum'] = $datum ? $datum->format('Y-m-d') : null;
            $current['lehrkraft'] = trim(preg_replace('/- geändert:.*/', '', $m[2]));
            continue;
        }

        // Fachkennung
        if (preg_match('/^(PHY|MAT|nM|BIO|CHE|ENG|DEU|INF)$/i', $line, $m)) {
            $current['fach'] = strtoupper($m[1]);
            continue;
        }

        // Thema (erste Zeile nach Fach)
        if ($current['fach'] && !$current['thema']) {
            $current['thema'] = $line;
            continue;
        }

        // Bemerkungen sammeln
        if ($current['fach']) {
            if (!preg_match('/^\d{2}\.\d{2}\.\d{2}/', $line) && !preg_match('/^(PHY|MAT|nM|BIO|CHE|ENG|DEU|INF)$/i', $line)) {
                $current['bemerkungen'][] = $line;
            }
        }
    }

    // letzten Datensatz übernehmen
    if ($current['datum'] && $current['fach']) {
        $entries[] = $current;
    }

    // --- SQL vorbereiten ---
    $sqlInsertMain = "INSERT ignore INTO mtr_rueckkopplung_datenmaske 
        (teilnehmer_id, fach, datum, lehrkraft, thema, bemerkung)
        VALUES (:teilnehmer_id, :fach, :datum, :lehrkraft, :thema, :bemerkung)";
    $stmtMain = $pdo->prepare($sqlInsertMain);

    $sqlInsertValue = "INSERT ignore INTO mtr_rueckkopplung_datenmaske_values 
        (id_mtr_rueckkopplung_datenmaske, value)
        VALUES (:id_mtr_rueckkopplung_datenmaske, :value)";
    $stmtValue = $pdo->prepare($sqlInsertValue);

    // --- Datensätze einfügen ---
    $count = 0;
    foreach ($entries as $e) {
        $bemerkung = implode(' | ', array_map('trim', $e['bemerkungen']));
/*
Absprachen einhaltendbeteiligt sich / gute Mitarbeitfleißig / bemühtarbeitet selbstständigkonzentriertvorbereitetLernfortschritt erzieltbeherrscht Themafähig zu Transferdenken
*/
        $stmtMain->execute([
            ':teilnehmer_id' => $teilnehmer_id,
            ':fach' => $e['fach'],
            ':datum' => $e['datum'],
            ':lehrkraft' => $e['lehrkraft'],
            ':thema' => $e['thema'],
            ':bemerkung' => $bemerkung,
        ]);
        
        // 1️⃣ Hauptdatensatz einfügen

        // letzte eingefügte ID ermitteln
        $idMain = $pdo->lastInsertId();

        // 2️⃣ Einzelbemerkungen einfügen
        foreach ($e['bemerkungen'] as $b) {
            $stmtValue->execute([
                ':id_mtr_rueckkopplung_datenmaske' => $idMain,
                ':value' => trim($b),
            ]);
        }

        $count++;
    }
    $pdo -> exec("insert ignore into _mtr_datenmaske_values_wertung (value) select value from mtr_rueckkopplung_datenmaske_values");
    echo "✅ Import abgeschlossen: {$count} Hauptdatensätze + Teilbemerkungen eingetragen.\n";
    }
}

// -------------------- HTML-Form --------------------
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>ICAS Datenimport – Rückkopplung Datenmaske</title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f8f8f8; }
form { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; max-width: 900px; }
textarea { width: 100%; height: 400px; font-family: monospace; }
label { display: block; margin-top: 10px; font-weight: bold; }
select, input[type="number"] { padding: 5px; width: 260px; }
button { margin-top: 15px; padding: 10px 20px; background: #2c6df5; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #1e4fd5; }
</style>
</head>
<body>

<h2>ICAS – Rückkopplung Rohdaten importieren</h2>

<form method="post">
    <label for="teilnehmer_id">Teilnehmer:</label>
    <select name="teilnehmer_id" id="teilnehmer_id" required>
        <option value="">-- auswählen --</option>
<?php
// Teilnehmerliste ziehen
$rows = $pdo->query("SELECT id, CONCAT(Nachname, ', ', Vorname) as tnName FROM std_teilnehmer ORDER BY Nachname, Vorname")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $sel = (isset($teilnehmer_id) && $teilnehmer_id == $r['id']) ? ' selected' : '';
    echo "<option value=\"" . htmlspecialchars($r['id']) . "\"$sel>" . htmlspecialchars($r['tnName']) . " (id:" . htmlspecialchars($r['id']) . ")</option>\n";
}
?>
    </select>

    <label for="rawdata">Rohdaten (aus Datenmaske einfügen):</label>
    <textarea name="rawdata" id="rawdata" placeholder="Hier den Text aus der Datenmaske einfügen..."></textarea>

    <button type="submit">Import starten</button>
</form>
<?php
$rows = $pdo->query("SELECT min(datum) as minDate, max(datum) as maxDate, concat(Nachname, ', ', Vorname) as tnName FROM `mtr_rueckkopplung_datenmaske`left join std_teilnehmer on teilnehmer_id=std_teilnehmer.id group by std_teilnehmer.id order by Nachname")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "<div>" . htmlspecialchars($r['minDate']) . "\t" . htmlspecialchars($r['maxDate']) . "\t" . htmlspecialchars($r['tnName']);
    echo "</div>";
}
    
?>
</body>
</html>
