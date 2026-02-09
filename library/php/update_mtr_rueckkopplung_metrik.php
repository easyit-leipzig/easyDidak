<?php
/**
 * ICAS – Didaktische Metrikberechnung für mtr_rueckkopplung_lehrkraft_datenmaske
 * ------------------------------------
 * Dieses Skript analysiert das Feld "bemerkung" in mtr_rueckkopplung_datenmaske
 * und leitet daraus fünf didaktische Dimensionen ab:
 *   - metr_kognition
 *   - metr_sozial
 *   - metr_affektiv
 *   - metr_metakog
 *   - metr_kohärenz
 *
 * Danach werden die Werte auch in mtr_rueckkopplung_lehrkraft_datenmaske gespiegelt (falls vorhanden).
 *
 * Aufruf: automatisch durch fill_mtr_rueckkopplung_lehrkraft_datenmaske_optimiert.php oder manuell im Browser.
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Optional: Nur für einen bestimmten Teilnehmer (wenn vom Import übergeben)
$filterTeilnehmer = isset($teilnehmer_id) ? intval($teilnehmer_id) : null;

// Selektiere alle Einträge, die noch keine Metrik haben
$sql = "SELECT * FROM mtr_rueckkopplung_datenmaske WHERE metr_kognition IS NULL";
if ($filterTeilnehmer) {
    $sql .= " AND teilnehmer_id = " . $filterTeilnehmer;
}
$stmt = $pdo->query($sql);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = count($records);

if ($count === 0) {
    echo "ℹ️ Keine neuen Datensätze zur Metrikberechnung gefunden.\n";
    return;
}

echo "🧮 Starte Metrik-Berechnung für $count Datensätze...\n\n";

// --- Hilfsfunktion: Textanalyse der Bemerkung ---
function analyseMetrik($text)
{
    $text = mb_strtolower($text, 'UTF-8');

    // Keyword-Gruppen (kannst du beliebig erweitern)
    $patterns = [
        'kognition' => ['verstehen', 'anwenden', 'lösen', 'analysieren', 'zusammenfassen', 'begründ', 'kennt', 'erkennt'],
        'sozial'    => ['kooperativ', 'hilfsbereit', 'kommuniziert', 'feedback', 'miteinander', 'team', 'respekt'],
        'affektiv'  => ['motiviert', 'interessiert', 'offen', 'selbstvertrauen', 'sicher', 'freude', 'engagiert'],
        'metakog'   => ['reflektiert', 'strategien', 'plant', 'kontrolliert', 'verbessert', 'bewusst', 'zielstrebig'],
        'kohärenz'  => ['zusammenhang', 'übertragen', 'verknüpf', 'transfer', 'integriert', 'nachvollziehbar']
    ];

    $scores = ['kognition'=>0,'sozial'=>0,'affektiv'=>0,'metakog'=>0,'kohärenz'=>0];

    foreach ($patterns as $key => $words) {
        foreach ($words as $w) {
            if (mb_strpos($text, $w) !== false) {
                $scores[$key]++;
            }
        }
    }

    // Normalisierung auf 0–1 (je mehr Keywords → desto höher)
    foreach ($scores as $k => &$v) {
        $v = min(1, $v / 5.0);
    }

    return $scores;
}

// --- Durch alle Datensätze iterieren ---
$update = $pdo->prepare("
    UPDATE mtr_rueckkopplung_datenmaske
    SET metr_kognition = :k, metr_sozial = :s, metr_affektiv = :a, metr_metakog = :m, metr_kohärenz = :c
    WHERE id = :id
");

$updateLehrkraft = $pdo->prepare("
    UPDATE mtr_rueckkopplung_lehrkraft_tn
    SET metr_kognition = :k, metr_sozial = :s, metr_affektiv = :a, metr_metakog = :m, metr_kohärenz = :c
    WHERE teilnehmer_id = :tid AND DATE(datum) = :datum
");

$processed = 0;

foreach ($records as $r) {
    $scores = analyseMetrik($r['bemerkung']);

    $update->execute([
        ':k' => $scores['kognition'],
        ':s' => $scores['sozial'],
        ':a' => $scores['affektiv'],
        ':m' => $scores['metakog'],
        ':c' => $scores['kohärenz'],
        ':id' => $r['id']
    ]);

    $updateLehrkraft->execute([
        ':k' => $scores['kognition'],
        ':s' => $scores['sozial'],
        ':a' => $scores['affektiv'],
        ':m' => $scores['metakog'],
        ':c' => $scores['kohärenz'],
        ':tid' => $r['teilnehmer_id'],
        ':datum' => $r['datum']
    ]);

    $processed++;
    echo "✅ ID {$r['id']} ({$r['datum']} – {$r['fach']}) → [Kog={$scores['kognition']}, Soz={$scores['sozial']}, Aff={$scores['affektiv']}, Meta={$scores['metakog']}, Koh={$scores['kohärenz']}]\n";
}

echo "\n🎯 Fertig: $processed Datensätze aktualisiert.\n";
?>
