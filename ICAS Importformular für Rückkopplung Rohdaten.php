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

        // ===== 1) PARSEN UND INSERT IN DATENMASKE =====
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

            // Datum + Lehrkraft z.B. "03.11.25 Thiele, Dipl.-Ing. Olaf"
            if (preg_match('/^(\d{2}\.\d{2}\.\d{2})\s+(.+)$/u', $line, $m)) {
                // vorhandenen Block speichern
                if ($current['datum'] && $current['fach'] && $current['thema']) {
                    processRecord($pdo, $teilnehmer_id, $current, $inserted, $errors, $skipped);
                    $current['bemerkung'] = '';
                }
                $dt = DateTime::createFromFormat('d.m.y', $m[1]);
                if ($dt === false) {
                    // fehlerhaftes Datum — skip
                    $current['datum'] = null;
                } else {
                    $current['datum'] = $dt->format('Y-m-d');
                }
                $current['lehrkraft'] = trim($m[2]);
                continue;
            }

            // Fach (erweitere Liste falls nötig)
            if (preg_match('/^(PHY|MAT|ENG|BIO|CHE|ORG)$/i', $line, $m2)) {
                $current['fach'] = strtoupper($m2[1]);
                continue;
            }

            // Thema-Marker (vereinfachte Erkennung)
            if (preg_match('/^(Prüfungsvorbereitung|Weitere Themen|Lineare Funktion|Quadratische|Kreis|Trigonometrie|weitere Themen|Thema|Schwerpunkte)/ui', $line)) {
                $current['thema'] = $line;
                continue;
            }

            // alles andere = bemerkung (anhängen)
            $current['bemerkung'] .= ($current['bemerkung'] ? ' ' : '') . $line;
        }

        // letzten Block speichern
        if ($current['datum'] && $current['fach'] && $current['thema']) {
            processRecord($pdo, $teilnehmer_id, $current, $inserted, $errors, $skipped);
        }

        // ===== 2) METRIKEN BERECHNEN für eingefügte (oder alle ohne Metrik) =====
        $metricsProcessed = calculateAndStoreMetrics($pdo, $teilnehmer_id);

        // ===== 3) SYNCHRONISATION nach mtr_rueckkopplung_lehrkraft_tn =====
        list($synced, $syncUpdated, $syncInserted) = syncToLehrkraftTable($pdo, $teilnehmer_id);

        // ===== 4) ERGEBNISAUSGABE =====
        echo "<div style='margin-top:20px;padding:15px;background:#eef;border-radius:10px'>";
        echo "<h3>📊 Import-Ergebnis</h3>";
        echo "<p style='color:green'>✅ Eingefügt in Datenmaske: <b>$inserted</b> Datensätze.</p>";
        if (!empty($skipped)) {
            echo "<p style='color:orange'>⚠️ Übersprungen (Fach = ORG):<br>" . implode("<br>", $skipped) . "</p>";
        }
        if (!empty($errors)) {
            echo "<p style='color:red'>❌ Dubletten (bereits vorhanden in Datenmaske):<br>" . implode("<br>", $errors) . "</p>";
        }
        echo "<hr>";
        echo "<p>🧮 Metrik-Berechnung: <b>$metricsProcessed</b> Datensätze verarbeitet.</p>";
        echo "<p>🔁 Synchronisation nach <code>mtr_rueckkopplung_lehrkraft_tn</code>: <b>$synced</b> versucht, davon <b>$syncUpdated</b> aktualisiert, <b>$syncInserted</b> neu angelegt.</p>";
        echo "</div>";
    }
}

// -------------------- Funktionen --------------------

/**
 * processRecord
 * - überspringt fach = ORG
 * - prüft Dublette (teilnehmer_id + datum) in mtr_rueckkopplung_datenmaske
 * - fügt ein, wenn nicht vorhanden
 */
function processRecord(PDO $pdo, int $tid, array $rec, int &$inserted, array &$errors, array &$skipped)
{
    // fach prüfen
    if (!isset($rec['fach']) || strtoupper(trim($rec['fach'])) === 'ORG') {
        $skipped[] = "{$rec['datum']} – {$rec['fach']} ({$rec['lehrkraft']})";
        return;
    }

    // dublettenprüfung
    $check = $pdo->prepare("
        SELECT id FROM mtr_rueckkopplung_datenmaske
        WHERE teilnehmer_id = :tid AND datum = :datum
        LIMIT 1
    ");
    $check->execute([':tid' => $tid, ':datum' => $rec['datum']]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $errors[] = "{$rec['datum']} – {$rec['fach']} (bereits vorhanden)";
        return;
    }

    // insert
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

/**
 * calculateAndStoreMetrics
 * - berechnet Metriken (einfaches keyword-mapping) für alle mtr_rueckkopplung_datenmaske
 *   mit metr_kognition IS NULL (oder eingeschränkt auf teilnehmer)
 * - aktualisiert die Datenmaske-Einträge
 * - gibt Anzahl verarbeiteter Datensätze zurück
 */
function calculateAndStoreMetrics(PDO $pdo, ?int $filterTeilnehmer = null): int
{
    // keyword sets
    $patterns = [
        'kognition' => ['verstehen','anwenden','lösen','analysieren','zusammenfassen','begründ','kennt','erkennt','sicher','anwenden'],
        'sozial'    => ['kooperativ','hilfsbereit','kommuniziert','feedback','miteinander','team','respekt','erklärt','hilft','zusammenarbeit'],
        'affektiv'  => ['motiviert','interessiert','offen','selbstvertrauen','sicher','freude','engagiert','einstellung'],
        'metakog'   => ['reflektiert','strategien','plant','kontrolliert','verbessert','bewusst','zielstrebig','priorit'],
        'kohärenz'  => ['zusammenhang','übertragen','verknüpf','transfer','integriert','nachvollziehbar','kohärenz']
    ];

    $sql = "SELECT * FROM mtr_rueckkopplung_datenmaske WHERE metr_kognition IS NULL";
    if ($filterTeilnehmer) $sql .= " AND teilnehmer_id = " . intval($filterTeilnehmer);
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) return 0;

    $update = $pdo->prepare("
        UPDATE mtr_rueckkopplung_datenmaske
        SET metr_kognition = :k, metr_sozial = :s, metr_affektiv = :a, metr_metakog = :m, metr_kohärenz = :c
        WHERE id = :id
    ");

    $processed = 0;
    foreach ($rows as $r) {
        $text = mb_strtolower($r['bemerkung'] ?? '', 'UTF-8');
        $scores = ['kognition'=>0,'sozial'=>0,'affektiv'=>0,'metakog'=>0,'kohärenz'=>0];

        foreach ($patterns as $key => $words) {
            foreach ($words as $w) {
                if ($w === '') continue;
                if (mb_strpos($text, $w) !== false) $scores[$key] += 1;
            }
        }
        // normalisieren (skala 0..1), divisor anpassbar
        foreach ($scores as $k => &$v) $v = min(1, $v / 5.0);

        // Kohärenz alternativ als "Varianz-Stabilität" möglich — für jetzt Keyword-basierte Kohärenz
        // Schreibe zurück
        $update->execute([
            ':k' => round($scores['kognition'], 2),
            ':s' => round($scores['sozial'], 2),
            ':a' => round($scores['affektiv'], 2),
            ':m' => round($scores['metakog'], 2),
            ':c' => round($scores['kohärenz'], 2),
            ':id' => $r['id']
        ]);
        $processed++;
    }

    return $processed;
}

/**
 * syncToLehrkraftTable
 * - liest (option: nur für teilnehmer) alle relevanten Zeilen aus mtr_rueckkopplung_datenmaske
 * - findet passenden ue_zuweisung_teilnehmer_id aus mtr_rueckkopplung_teilnehmer
 *   (match teilnehmer_id + DATE(erfasst_am) ~ datum ±1 Tag)
 * - wenn bereits Eintrag in mtr_rueckkopplung_lehrkraft_tn für die ue_zuweisung_teilnehmer_id existiert -> UPDATE
 *   andernfalls -> INSERT
 * - gibt array: [anzahlVersucht, updatedCount, insertedCount]
 */
function syncToLehrkraftTable(PDO $pdo, ?int $filterTeilnehmer = null): array
{
    // select relevant mask entries
    $sql = "SELECT id, teilnehmer_id, datum, fach, lehrkraft, thema, bemerkung, metr_kognition, metr_sozial, metr_affektiv, metr_metakog, metr_kohärenz
            FROM mtr_rueckkopplung_datenmaske";
    if ($filterTeilnehmer) $sql .= " WHERE teilnehmer_id = " . intval($filterTeilnehmer);
    $masken = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $attempts = 0; $updated = 0; $inserted = 0;

    // prepared statements
    $findUe = $pdo->prepare("
        SELECT ue_zuweisung_teilnehmer_id
        FROM mtr_rueckkopplung_teilnehmer
        WHERE teilnehmer_id = :tid
          AND ABS(DATEDIFF(DATE(erfasst_am), :datum)) <= 1
        ORDER BY ABS(DATEDIFF(DATE(erfasst_am), :datum)) ASC
        LIMIT 1
    ");

    $findLehr = $pdo->prepare("
        SELECT id FROM mtr_rueckkopplung_lehrkraft_tn
        WHERE ue_zuweisung_teilnehmer_id = :ueid
        LIMIT 1
    ");

    $insertLehr = $pdo->prepare("
        INSERT INTO mtr_rueckkopplung_lehrkraft_tn
        (ue_zuweisung_teilnehmer_id, teilnehmer_id, fach, datum, lehrkraft, thema, rueckmeldung,
         metr_kognition, metr_sozial, metr_affektiv, metr_metakog, metr_kohärenz, erfasst_am)
        VALUES
        (:ueid, :tid, :fach, :datum, :lehrkraft, :thema, :rueck,
         :k, :s, :a, :m, :c, NOW())
    ");

    $updateLehr = $pdo->prepare("
        UPDATE mtr_rueckkopplung_lehrkraft_tn
        SET fach = :fach, datum = :datum, lehrkraft = :lehrkraft, thema = :thema, rueckmeldung = :rueck,
            metr_kognition = :k, metr_sozial = :s, metr_affektiv = :a, metr_metakog = :m, metr_kohärenz = :c,
            erfasst_am = NOW()
        WHERE ue_zuweisung_teilnehmer_id = :ueid
    ");

    foreach ($masken as $m) {
        $attempts++;

        // 1) finde ue_zuweisung_teilnehmer_id
        $findUe->execute([':tid' => $m['teilnehmer_id'], ':datum' => $m['datum']]);
        $ue = $findUe->fetchColumn();

        if (!$ue) {
            // kein zuordnungseintrag gefunden -> skip (oder loggen)
            continue;
        }

        // 2) existiert bereits Eintrag in lehrkraft_tn?
        $findLehr->execute([':ueid' => $ue]);
        $existsLehrId = $findLehr->fetchColumn();

        if ($existsLehrId) {
            // update
            $updateLehr->execute([
                ':fach' => $m['fach'],
                ':datum' => $m['datum'],
                ':lehrkraft' => $m['lehrkraft'],
                ':thema' => $m['thema'],
                ':rueck' => $m['bemerkung'],
                ':k' => $m['metr_kognition'],
                ':s' => $m['metr_sozial'],
                ':a' => $m['metr_affektiv'],
                ':m' => $m['metr_metakog'],
                ':c' => $m['metr_kohärenz'],
                ':ueid' => $ue
            ]);
            $updated++;
        } else {
            // insert
            $insertLehr->execute([
                ':ueid' => $ue,
                ':tid' => $m['teilnehmer_id'],
                ':fach' => $m['fach'],
                ':datum' => $m['datum'],
                ':lehrkraft' => $m['lehrkraft'],
                ':thema' => $m['thema'],
                ':rueck' => $m['bemerkung'],
                ':k' => $m['metr_kognition'],
                ':s' => $m['metr_sozial'],
                ':a' => $m['metr_affektiv'],
                ':m' => $m['metr_metakog'],
                ':c' => $m['metr_kohärenz']
            ]);
            $inserted++;
        }
    }

    return [$attempts, $updated, $inserted];
}
function clamp($v) {
    return max(1.0, min(6.0, round((float)$v, 2)));
}

// Aggregation: durchschnittliche metriken pro Teilnehmer/Monat + avg_note falls vorhanden
$sql = "
SELECT 
    d.teilnehmer_id,
    DATE_FORMAT(d.datum, '%Y-%m-01') AS monatsdatum,
    COUNT(*) AS n,
    AVG(d.metr_kognition)   AS avg_kog,
    AVG(d.metr_sozial)      AS avg_soz,
    AVG(d.metr_affektiv)    AS avg_aff,
    AVG(d.metr_metakog)     AS avg_meta,
    AVG(d.metr_kohärenz)    AS avg_koh,
    AVG(p.note)             AS avg_note
FROM mtr_rueckkopplung_datenmaske d
LEFT JOIN mtr_persoenlichkeit p 
       ON p.teilnehmer_id = d.teilnehmer_id
      AND DATE_FORMAT(p.datum, '%Y-%m') = DATE_FORMAT(d.datum, '%Y-%m')
GROUP BY d.teilnehmer_id, monatsdatum
";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Gefundene Teilnehmer-Monats-Gruppen: " . count($data) . "\n\n";

$selExists = $pdo->prepare("SELECT id FROM mtr_persoenlichkeit WHERE teilnehmer_id = :tid AND datum = :datum");
$insert = $pdo->prepare("
INSERT INTO mtr_persoenlichkeit (
    teilnehmer_id, datum,
    offenheit_erfahrungen, gewissenhaftigkeit, Extraversion, vertraeglichkeit,
    zielorientierung, lernfaehigkeit, anpassungsfaehigkeit, soziale_interaktion,
    metakognition, stressbewaeltigung, bedeutungsbildung, belastbarkeit,
    problemloesefaehigkeit, kreativitaet_innovation, ko_kreationsfaehigkeit,
    resonanzfaehigkeit, handlungsdichte, performanz_effizienz, basiswissen, note
) VALUES (
    :tid, :datum,
    :off, :gew, :ext, :ver,
    :ziel, :lern, :anp, :sozial,
    :meta, :stress, :bedeu, :bel,
    :prob, :kreat, :ko, :reso,
    :hand, :perf, :basis, :note
)
");
$update = $pdo->prepare("
UPDATE mtr_persoenlichkeit SET
    offenheit_erfahrungen = :off,
    gewissenhaftigkeit = :gew,
    Extraversion = :ext,
    vertraeglichkeit = :ver,
    zielorientierung = :ziel,
    lernfaehigkeit = :lern,
    anpassungsfaehigkeit = :anp,
    soziale_interaktion = :sozial,
    metakognition = :meta,
    stressbewaeltigung = :stress,
    bedeutungsbildung = :bedeu,
    belastbarkeit = :bel,
    problemloesefaehigkeit = :prob,
    kreativitaet_innovation = :kreat,
    ko_kreationsfaehigkeit = :ko,
    resonanzfaehigkeit = :reso,
    handlungsdichte = :hand,
    performanz_effizienz = :perf,
    basiswissen = :basis,
    note = :note
WHERE teilnehmer_id = :tid AND datum = :datum
");

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($data as $r) {
    $tid = (int)$r['teilnehmer_id'];
    $datum = $r['monatsdatum'];
    $n = (int)$r['n'];

    // Wenn keine tatsächlichen Rückmeldungen, skip (sollte nicht passieren)
    if ($n === 0) { $skipped++; continue; }

    // Rohwerte (können NULL sein)
    $raw = [
        'avg_kog' => isset($r['avg_kog']) ? (float)$r['avg_kog'] : null,
        'avg_soz' => isset($r['avg_soz']) ? (float)$r['avg_soz'] : null,
        'avg_aff' => isset($r['avg_aff']) ? (float)$r['avg_aff'] : null,
        'avg_meta'=> isset($r['avg_meta'])? (float)$r['avg_meta'] : null,
        'avg_koh' => isset($r['avg_koh']) ? (float)$r['avg_koh'] : null
    ];

    // Prüfe ob ALLE Metriken NULL -> skip (vermeidet 1er-Fälle)
    $nonNull = array_filter($raw, function($v){ return $v !== null; });
    if (count($nonNull) === 0) {
        $skipped++;
        continue;
    }

    // Fallback: fehlende Werte durch Mittel der vorhandenen Metriken
    $meanAvail = array_sum($nonNull) / count($nonNull);
    foreach ($raw as $k => $v) {
        if ($v === null) $raw[$k] = $meanAvail; // sinnvoller Fallback
    }

    // Note: falls avg_note NULL -> set default 4.0 (mittlere Note)
    $note = isset($r['avg_note']) && $r['avg_note'] !== null ? (float)$r['avg_note'] : 4.0;
    // clamp note to [1..6]
    $note = max(1.0, min(6.0, $note));

    // Faktor (du kannst die Formel anpassen). Hier: bessere Note -> leicht höhere Skala.
    $faktor = 1 + (4.0 - $note) * 0.05; // bei note=4 -> faktor=1

    // Mapping: metr [0..1] -> scale [1..6] using 1 + metr*5, then apply faktor, then clamp
    $scale = function($metr) use ($faktor) {
        // ensure metr in [0..1]
        $m = max(0.0, min(1.0, (float)$metr));
        $val = 1.0 + $m * 5.0;        // now in [1..6]
        $val *= $faktor;             // apply weighting
        return clamp($val);
    };

    // Now compute personality fields using mapped values (examples)
    $off   = $scale($raw['avg_kog']);                              // Offenheit ~ Kognition
    $gew   = $scale($raw['avg_meta']);                             // Gewissenhaftigkeit ~ Metakog
    $ext   = $scale($raw['avg_aff']);                              // Extraversion ~ Affektiv
    $ver   = $scale($raw['avg_soz']);                              // Verträglichkeit ~ Sozial
    $ziel  = $scale($raw['avg_meta']);                             // Zielorientierung ~ Metakog
    $lern  = clamp((($scale($raw['avg_kog']) + $scale($raw['avg_meta']))/2)); // Lernfähigkeit
    $anp   = $scale($raw['avg_koh']);                              // Anpassungsfähigkeit ~ Kohärenz
    $sozial= $scale($raw['avg_soz']);
    $meta  = $scale($raw['avg_meta']);
    // stressbewaeltigung: interpretative mapping (high affektiv -> lower stress control)
    $stressRaw = $raw['avg_aff']; // 0..1
    $stressVal = 1.0 + (1.0 - $stressRaw) * 5.0; // if aff high (1) => 1.0; if aff low (0) => 6.0
    $stress = clamp($stressVal * $faktor);

    $bedeu = $scale($raw['avg_koh']);
    $bel   = clamp((($scale($raw['avg_aff']) + $scale($raw['avg_meta']))/2));
    $prob  = $scale($raw['avg_koh']);
    $kreat = $scale($raw['avg_koh']);
    $ko    = $scale($raw['avg_soz']);
    $reso  = $scale($raw['avg_aff']);
    $hand  = clamp((($scale($raw['avg_meta']) + $scale($raw['avg_koh']))/2));
    $perf  = clamp((($scale($raw['avg_kog']) + $scale($raw['avg_koh']))/2));
    $basis = clamp(1.0 + $raw['avg_kog'] * 5.0); // direct mapping without faktor for basiswissen

    // Prepare params
    $params = [
        ':off'=>$off,':gew'=>$gew,':ext'=>$ext,':ver'=>$ver,
        ':ziel'=>$ziel,':lern'=>$lern,':anp'=>$anp,':sozial'=>$sozial,
        ':meta'=>$meta,':stress'=>$stress,':bedeu'=>$bedeu,':bel'=>$bel,
        ':prob'=>$prob,':kreat'=>$kreat,':ko'=>$ko,':reso'=>$reso,
        ':hand'=>$hand,':perf'=>$perf,':basis'=>$basis,':note'=>$note,
        ':tid'=>$tid,':datum'=>$datum
    ];

    // Existenz prüfen
    $selExists->execute([':tid'=>$tid, ':datum'=>$datum]);
    $exists = $selExists->fetchColumn();

    if ($exists) {
        $update->execute($params);
        $updated++;
    } else {
        $insert->execute($params);
        $inserted++;
    }

    // Debug kurz ausgeben (entfernbar)
    echo sprintf("tid=%d month=%s n=%d avg_kog=%s avg_soz=%s avg_aff=%s avg_meta=%s avg_koh=%s avg_note=%s -> off=%.2f\n",
        $tid, $datum, $n,
        var_export($r['avg_kog'], true), var_export($r['avg_soz'], true),
        var_export($r['avg_aff'], true), var_export($r['avg_meta'], true),
        var_export($r['avg_koh'], true), var_export($r['avg_note'], true),
        $off
    );
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

</body>
</html>
