<?php
// import_datenmaske.php
// -----------------------------------------------------------
// Erweiterung:
// Beim Einfügen in mtr_rueckkopplung_datenmaske wird automatisch
// die passende gruppe_id aus ue_gruppen bestimmt (Tag + Zeit).
// -----------------------------------------------------------

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$errors = [];
$skipped = [];
$inserted = 0;

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

            // Datum + Lehrkraft
            if (preg_match('/^(\d{2}\.\d{2}\.\d{2})\s+(.+)$/', $line, $m)) {
                if ($current['datum'] && $current['fach']) {
                    $entries[] = $current;
                    $current = ['fach'=>null,'datum'=>null,'lehrkraft'=>null,'thema'=>null,'bemerkungen'=>[]];
                }
                $datum = DateTime::createFromFormat('d.m.y', $m[1]);
                $current['datum'] = $datum ? $datum->format('Y-m-d H:i:s') : null;
                $current['lehrkraft'] = trim(preg_replace('/- geändert:.*/', '', $m[2]));
                continue;
            }

            // Fachkennung
            if (preg_match('/^(PHY|MAT|nM|BIO|CHE|ENG|DEU|INF)$/i', $line, $m)) {
                $current['fach'] = strtoupper($m[1]);
                continue;
            }

            // Thema
            if ($current['fach'] && !$current['thema']) {
                $current['thema'] = $line;
                continue;
            }

            // Bemerkung
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

        // --- Statements vorbereiten ---
        $sqlInsertMain = "INSERT IGNORE INTO mtr_rueckkopplung_datenmaske 
            (teilnehmer_id, fach, datum, gruppe_id, lehrkraft, thema, bemerkung)
            VALUES (:teilnehmer_id, :fach, :datum, :gruppe_id, :lehrkraft, :thema, :bemerkung)";
        $stmtMain = $pdo->prepare($sqlInsertMain);

        $sqlInsertValue = "INSERT IGNORE INTO mtr_rueckkopplung_datenmaske_values 
            (id_mtr_rueckkopplung_datenmaske, value)
            VALUES (:id_mtr_rueckkopplung_datenmaske, :value)";
        $stmtValue = $pdo->prepare($sqlInsertValue);

        // --- Gruppenzuordnung vorbereiten ---
        $stmtGroup = $pdo->prepare("
            SELECT id FROM ue_gruppen
            WHERE day_number = :day
              AND :time BETWEEN uhrzeit_start AND uhrzeit_ende
            LIMIT 1
        ");

        $count = 0;
        foreach ($entries as $e) {

            if (strtoupper($e['fach']) === 'ORG') {
                $skipped[] = "{$e['datum']} – {$e['fach']} (ORG übersprungen)";
                continue;
            }

            // Tag + Zeit aus Datum bestimmen
            $dt = new DateTime($e['datum']);
            $day = (int)$dt->format('N');  // Montag=1 … Sonntag=7
            $time = $dt->format('H:i:s');

            $stmtGroup->execute([':day' => $day, ':time' => $time]);
            $gruppe_id = $stmtGroup->fetchColumn();

            if (!$gruppe_id) {
                $gruppe_id = null; // Kein Treffer
            }

            $bemerkung = implode(' | ', array_map('trim', $e['bemerkungen']));

            $stmtMain->execute([
                ':teilnehmer_id' => $teilnehmer_id,
                ':fach' => $e['fach'],
                ':datum' => $e['datum'],
                ':gruppe_id' => $gruppe_id,
                ':lehrkraft' => $e['lehrkraft'],
                ':thema' => $e['thema'],
                ':bemerkung' => $bemerkung,
            ]);

            $idMain = $pdo->lastInsertId();

            foreach ($e['bemerkungen'] as $b) {
                $stmtValue->execute([
                    ':id_mtr_rueckkopplung_datenmaske' => $idMain,
                    ':value' => trim($b),
                ]);
            }

            $count++;
        }

        $pdo->exec("INSERT IGNORE INTO _mtr_datenmaske_values_wertung (value)
                    SELECT value FROM mtr_rueckkopplung_datenmaske_values");

        echo "✅ Import abgeschlossen: {$count} Datensätze (mit Gruppenzuordnung) eingetragen.<br>";

        if (!empty($skipped)) {
            echo "<p style='color:orange'>⚠️ Übersprungen (Fach ORG):<br>" . implode("<br>", $skipped) . "</p>";
        }
    }
}
$pdo -> exec("UPDATE mtr_rueckkopplung_datenmaske dm
JOIN ue_zuweisung_teilnehmer zut 
  ON dm.teilnehmer_id = zut.teilnehmer_id 
  AND WEEKDAY(dm.datum) = WEEKDAY(zut.datum)
JOIN ue_gruppen g 
  ON zut.gruppe_id = g.id
SET dm.gruppe_id = g.id;
");
/* diese abfrage listet die teilnehmer pro gruppe und tagnummer und andere werte,

SELECT distinct
    g.id AS gruppe_id,
    g.tag,
    g.day_number,
    g.uhrzeit_start,
    g.uhrzeit_ende,
    g.fach,
    CONCAT(t.Nachname, ', ', t.Vorname) AS teilnehmername,
    t.id AS teilnehmer_id
FROM ue_gruppen g
LEFT JOIN ue_zuweisung_teilnehmer zt 
       ON g.id = zt.gruppe_id
LEFT JOIN std_teilnehmer t 
       ON zt.teilnehmer_id = t.id
ORDER BY g.tag, g.uhrzeit_start, t.Nachname, t.Vorname;

damit müsste sich die gruppen_id bestimmen lassen.

über SELECT teilnehmer_id, weekday(datum) FROM `mtr_rueckkopplung_datenmaske`

lassen sich beide koppeln und damit die gruppe in mtr_rueckkopplung_datenmaske setzen
*/
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
$rows = $pdo->query("SELECT id, CONCAT(Vorname, '; ', Nachname, ', ', Vorname) AS tnName 
                     FROM std_teilnehmer 
                     ORDER BY Vorname, Nachname")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "<option value='{$r['id']}'>{$r['tnName']} (id: {$r['id']})</option>\n";
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
