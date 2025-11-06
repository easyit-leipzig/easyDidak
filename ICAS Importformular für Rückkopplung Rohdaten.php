<?php
// -----------------------------------------------------------
// ICAS Importformular – Rückkopplung Rohdaten
// -----------------------------------------------------------
// Überspringt Datensätze, wenn fach = "ORG"
// Prüft Dubletten (gleiche teilnehmer_id + datum)
// -----------------------------------------------------------

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

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $current = [
            'fach' => null,
            'datum' => null,
            'lehrkraft' => null,
            'thema' => null,
            'bemerkung' => ''
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Datum + Lehrkraft
            if (preg_match('/^(\d{2}\.\d{2}\.\d{2})\s+(.+)$/u', $line, $m)) {
                // Wenn ein vorheriger Datensatz abgeschlossen ist → speichern
                if ($current['datum'] && $current['fach'] && $current['thema']) {
                    processRecord($pdo, $teilnehmer_id, $current, $inserted, $errors, $skipped);
                    $current['bemerkung'] = '';
                }
                $current['datum'] = DateTime::createFromFormat('d.m.y', $m[1])->format('Y-m-d');
                $current['lehrkraft'] = trim($m[2]);
                continue;
            }

            // Fach
            if (preg_match('/^(PHY|MAT|ENG|BIO|CHE|ORG)$/', $line)) {
                $current['fach'] = $line;
                continue;
            }

            // Thema
            if (preg_match('/^(Prüfungsvorbereitung|Weitere Themen|Lineare Funktion|Quadratische|Kreis|Trigonometrie|weitere Themen|Thema|Schwerpunkte)/ui', $line)) {
                $current['thema'] = $line;
                continue;
            }

            // Bemerkung
            $current['bemerkung'] .= ($current['bemerkung'] ? ' ' : '') . $line;
        }

        // Letzten Datensatz prüfen
        if ($current['datum'] && $current['fach'] && $current['thema']) {
            processRecord($pdo, $teilnehmer_id, $current, $inserted, $errors, $skipped);
        }
// --- 1️⃣ Alle Datensätze aus der Datenmaske laden ---
$sql = "SELECT id, teilnehmer_id, datum, fach, lehrkraft, thema, bemerkung,
               metr_kognition, metr_sozial, metr_affektiv, metr_metakog, metr_kohärenz
        FROM mtr_rueckkopplung_datenmaske";
$masken = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- 2️⃣ Vorbereitung des Insert-Statements ---
$insert = $pdo->prepare("
    INSERT INTO mtr_rueckkopplung_lehrkraft_tn
    (ue_zuweisung_teilnehmer_id, teilnehmer_id, fach, datum, lehrkraft,
     thema, rueckmeldung, metr_kognition, metr_sozial, metr_affektiv, metr_metakog, metr_kohärenz)
    VALUES
    (:ue_id, :tn_id, :fach, :datum, :lehrkraft,
     :thema, :rueck, :k, :s, :a, :m, :koh)
");
// === Keyword-Gruppen (vereinfachtes semantisches Mapping) ===
$keywords = [
    'kognition' => [
        'beherrscht','anwenden','verstehen','lösen','sicher','kennt','abrufen','Wissen','Methoden','Begriff'
    ],
    'sozial' => [
        'hilft','arbeitet mit','Team','Feedback','erklärt','kommuniziert','Kooperation','Zusammenarbeit'
    ],
    'affektiv' => [
        'motiviert','interessiert','engagiert','Freude','positiv','ermutigt','Einstellung','Zielstrebigkeit'
    ],
    'metakog' => [
        'reflektiert','plant','setzt Prioritäten','Feedback umsetzen','selbstständig','organisiert','kontrolliert','Strategie'
    ]
];

// === Alle Datensätze laden ===
$stmt = $pdo->query("SELECT id, bemerkung FROM mtr_rueckkopplung_datenmaske");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $text = mb_strtolower($row['bemerkung']);
    $scores = ['kognition'=>0,'sozial'=>0,'affektiv'=>0,'metakog'=>0];
    $total = 0;

    foreach ($keywords as $cat => $list) {
        $count = 0;
        foreach ($list as $word) {
            $count += substr_count($text, mb_strtolower($word));
        }
        $scores[$cat] = min($count / 3.0, 1.0); // normalisiert (max 1.0)
        $total += $scores[$cat];
    }

    // Kohärenz als Varianz-Stabilität (1 - Varianz)
    $avg = $total / 4;
    $variance = 0;
    foreach ($scores as $v) $variance += pow($v - $avg, 2);
    $variance = $variance / 4;
    $koh = max(0, 1 - $variance * 4);

    // Update-Statement
    $update = $pdo->prepare("
        UPDATE mtr_rueckkopplung_datenmaske
        SET metr_kognition = :k,
            metr_sozial = :s,
            metr_affektiv = :a,
            metr_metakog = :m,
            metr_kohärenz = :ko
        WHERE id = :id
    ");

    $update->execute([
        ':k'  => round($scores['kognition'],2),
        ':s'  => round($scores['sozial'],2),
        ':a'  => round($scores['affektiv'],2),
        ':m'  => round($scores['metakog'],2),
        ':ko' => round($koh,2),
        ':id' => $row['id']
    ]);
}

echo "✅ Metriken erfolgreich aktualisiert.\n";

// --- 3️⃣ Durch alle Datensätze iterieren ---
$inserted = 0;
foreach ($masken as $m) {

    // 🧩 passenden Eintrag in mtr_rueckkopplung_teilnehmer suchen (Datum ±1 Tag)
$query = $pdo->prepare("
    SELECT ue_zuweisung_teilnehmer_id
    FROM mtr_rueckkopplung_teilnehmer
    WHERE teilnehmer_id = :tid
      AND ABS(DATEDIFF(DATE(erfasst_am), :datum)) <= 1
    ORDER BY ABS(DATEDIFF(DATE(erfasst_am), :datum)) ASC
    LIMIT 1
");
    $query->execute([':tid' => $m['teilnehmer_id'], ':datum' => $m['datum']]);
    $res = $query->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        echo "⚠️ Kein passender Eintrag in mtr_rueckkopplung_teilnehmer für ID {$m['id']} ({$m['datum']}) gefunden.\n";
        continue;
    }

    $ue_id = $res['ue_zuweisung_teilnehmer_id'];

    // --- 4️⃣ Datensatz in mtr_rueckkopplung_lehrkraft_tn einfügen ---
    $insert->execute([
        ':ue_id'   => $ue_id,
        ':tn_id'   => $m['teilnehmer_id'],
        ':fach'    => $m['fach'],
        ':datum'   => $m['datum'],
        ':lehrkraft' => $m['lehrkraft'],
        ':thema'   => $m['thema'],
        ':rueck'   => $m['bemerkung'],
        ':k'       => $m['metr_kognition'],
        ':s'       => $m['metr_sozial'],
        ':a'       => $m['metr_affektiv'],
        ':m'       => $m['metr_metakog'],
        ':koh'     => $m['metr_kohärenz']
    ]);

    $inserted++;
}

echo "✅ Synchronisation abgeschlossen. $inserted Datensätze eingefügt.\n";

        // Ergebnisübersicht
        echo "<div style='margin-top:20px;padding:15px;background:#eef;border-radius:10px'>";
        echo "<h3>📊 Import-Ergebnis</h3>";
        echo "<p style='color:green'>✅ Erfolgreich eingefügt: <b>$inserted</b> Datensätze.</p>";

        if (!empty($skipped)) {
            echo "<p style='color:orange'>⚠️ Übersprungen (Fach ORG):<br>" . implode("<br>", $skipped) . "</p>";
        }
        if (!empty($errors)) {
            echo "<p style='color:red'>❌ Dubletten (bereits vorhanden):<br>" . implode("<br>", $errors) . "</p>";
        }

        echo "</div>";
    }
}

// -----------------------------------------------------------
// Hilfsfunktion: Eintrag verarbeiten
// -----------------------------------------------------------
function processRecord($pdo, $tid, $rec, &$inserted, &$errors, &$skipped)
{
    // ❌ Datensätze mit Fach = ORG überspringen
    if (strtoupper(trim($rec['fach'])) === 'ORG') {
        $skipped[] = "{$rec['datum']} – {$rec['fach']} ({$rec['lehrkraft']})";
        return;
    }

    // Dublettenprüfung
    $check = $pdo->prepare("
        SELECT id FROM mtr_rueckkopplung_datenmaske
        WHERE teilnehmer_id = :tid AND datum = :datum
    ");
    $check->execute([':tid' => $tid, ':datum' => $rec['datum']]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $errors[] = "{$rec['datum']} – {$rec['fach']} (bereits vorhanden)";
        return;
    }

    // ✅ Insert
    $stmt = $pdo->prepare("
        INSERT INTO mtr_rueckkopplung_datenmaske
        (teilnehmer_id, fach, datum, lehrkraft, thema, bemerkung)
        VALUES (:tid, :fach, :datum, :lehrkraft, :thema, :bem)
    ");
    $stmt->execute([
        ':tid' => $tid,
        ':fach' => $rec['fach'],
        ':datum' => $rec['datum'],
        ':lehrkraft' => $rec['lehrkraft'],
        ':thema' => $rec['thema'],
        ':bem' => trim($rec['bemerkung'])
    ]);
    $inserted++;
}
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
input[type="number"] { padding: 5px; width: 200px; }
button { margin-top: 15px; padding: 10px 20px; background: #2c6df5; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #1e4fd5; }
</style>
</head>
<body>

<h2>ICAS – Rückkopplung Rohdaten importieren</h2>

<form method="post">
    <label for="teilnehmer_id">Teilnehmer-ID:</label>
    <!--
    <input type="number" name="teilnehmer_id" id="teilnehmer_id" required placeholder="z. B. 20">
    -->
    <select name="teilnehmer_id" id="teilnehmer_id" required>
<?php
    $r = $pdo->query( "select id, concat( Nachname, ', ', Vorname) as tnName from std_teilnehmer order by Nachname")->fetchAll();
    var_dump($r);
    $l = count ($r);
        $i = 0;
        while( $i < $l ) {
            echo "<option value=" . $r[$i]["id"] . ">" .  $r[$i]["tnName"] . "</option>";
            $i += 1;
        }
    
?>
</select>
    <label for="rawdata">Rohdaten (aus Datenmaske einfügen):</label>
    <textarea name="rawdata" id="rawdata" placeholder="Hier den Text aus der Datenmaske einfügen..."></textarea>

    <button type="submit">Import starten</button>
</form>

</body>
</html>
