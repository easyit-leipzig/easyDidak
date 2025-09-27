<?php
/**
 * generate_feedback_from_personality.php
 *
 * Liest die letzte Persönlichkeitsmessung für einen Teilnehmer aus `mtr_persoenlichkeit`
 * und erzeugt daraus heuristisch einen Datensatz in `mtr_rueckkopplung_teilnehmer`.
 *
 * Usage: php generate_feedback_from_personality.php <teilnehmer_id> <ue_zuweisung_teilnehmer_id> <gruppe_id>
 *
 * Hinweis: Die DB-Struktur stammt aus deiner icas.sql (mtr_persoenlichkeit, mtr_rueckkopplung_teilnehmer).
 * Siehe: icas.sql. :contentReference[oaicite:1]{index=1}
 */

if ($argc < 4) {
    echo "Usage: php {$argv[0]} <teilnehmer_id> <ue_zuweisung_teilnehmer_id> <gruppe_id>\n";
    exit(1);
}

$teilnehmer_id = intval($argv[1]);
$ue_zuweisung_teilnehmer_id = intval($argv[2]);
$gruppe_id = intval($argv[3]);

// ----- DB connection (anpassen) -----
$host = '127.0.0.1';
$db   = 'icas';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 1) Persönlichkeitsprofil laden (letzter Datensatz nach datum)
$sql = "
    SELECT *
    FROM mtr_persoenlichkeit
    WHERE teilnehmer_id = :tid
    ORDER BY datum DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $teilnehmer_id]);
$profile = $stmt->fetch();

if (!$profile) {
    echo "Keine Persönlichkeitsdaten für teilnehmer_id={$teilnehmer_id} gefunden.\\n";
    exit(1);
}

// --- Kurze Validierung / Defaults falls Felder fehlen ---
$fields = [
    'offenheit_erfahrungen','gewissenhaftigkeit','Extraversion','vertraeglichkeit',
    'zielorientierung','lernfaehigkeit','anpassungsfaehigkeit','soziale_interaktion',
    'metakognition','stressbewaeltigung','bedeutungsbildung','belastbarkeit',
    'problemlösefähigkeit','kreativität_innovation','ko-kreationsfähigkeit',
    'resonanzfähigkeit','handlungsdichte','performanz_effizienz','basiswissen','note'
];

foreach ($fields as $f) {
    if (!isset($profile[$f])) {
        // setze auf mittleren Defaultwert 3.0
        $profile[$f] = 3.0;
    } else {
        // sicherstellen: float
        $profile[$f] = (float) $profile[$f];
    }
}

/*
 * 2) Heuristische Mapping-Regeln
 *
 * Ziel: Werte 0..5 (in mtr_persoenlichkeit) in 1..4 (int) für die Rückkopplungsfelder mtr_rueckkopplung_teilnehmer mappen.
 *
 * Vorgehen (vereinfachend):
 *  - wir normalisieren 0..5 auf 1..4 mit Schwellen:
 *      score <= 1.5 -> 1
 *      1.5 < score <= 2.5 -> 2
 *      2.5 < score <= 3.5 -> 3
 *      score > 3.5 -> 4
 *
 *  - spezifische Felder werden kombiniert / gewichtet:
 *    * 'mitarbeit'   <- mittleres Niveau aus Extraversion, gewissenhaftigkeit, handlungsdichte
 *    * 'konzentration' <- lernfaehigkeit, gewissenhaftigkeit, belastbarkeit
 *    * 'selbststaendigkeit' <- zielorientierung + metakognition
 *    * 'fleiss' <- gewissenhaftigkeit + performanz_effizienz
 *    * 'lernfortschritt' <- basiswissen + problemlösefähigkeit + kreativität_innovation (als Proxy)
 *    * 'beherrscht_thema' <- basiswissen + performanz_effizienz
 *    * 'transferdenken' <- problemlösefähigkeit + kreativität_innovation
 *    * 'vorbereitet' <- gewissenhaftigkeit + vorbereitung proxy (hier: zielorientierung)
 *    * didaktische Felder (themenauswahl, materialien, methodenvielfalt, individualisierung, aufforderung)
 *       werden heuristisch aus soziale_interaktion / resonanzfähigkeit / anpassungsfaehigkeit gemappt
 *
 *  - 'emotions': Erzeuge eine kurze Emotions-Liste (Namen), z.B. 'Neugier,Freude' basierend auf Offenheit/Kreativität/Resonanz.
 *
 * Hinweise: Die Regeln sind bewusst einfach und erklärbar; du kannst sie beliebig anpassen.
 */

// helper: map 0..5 -> 1..4
function map_score_to_1_4(float $s): int {
    if ($s <= 1.5) return 1;
    if ($s <= 2.5) return 2;
    if ($s <= 3.5) return 3;
    return 4;
}

function weighted_average(array $pairs) {
    // $pairs = [ [value, weight], ... ]
    $num = 0.0; $den = 0.0;
    foreach ($pairs as $p) {
        $v = (float)$p[0]; $w = (float)$p[1];
        $num += $v * $w;
        $den += $w;
    }
    return $den == 0 ? 3.0 : $num / $den;
}

// compute fields
$mitarbeit_score = weighted_average([
    [$profile['Extraversion'], 0.4],
    [$profile['gewissenhaftigkeit'], 0.4],
    [$profile['handlungsdichte'], 0.2]
]);

$konzentration_score = weighted_average([
    [$profile['lernfaehigkeit'], 0.45],
    [$profile['gewissenhaftigkeit'], 0.35],
    [$profile['belastbarkeit'], 0.2]
]);

$selbststaendigkeit_score = weighted_average([
    [$profile['zielorientierung'], 0.6],
    [$profile['metakognition'], 0.4]
]);

$fleiss_score = weighted_average([
    [$profile['gewissenhaftigkeit'], 0.7],
    [$profile['performanz_effizienz'], 0.3]
]);

$lernfortschritt_score = weighted_average([
    [$profile['basiswissen'], 0.4],
    [ $profile['problemlösefähigkeit'] ?? $profile['problemlösefähigkeit'] /* fallback handled earlier */ , 0.35],
    [$profile['kreativität_innovation'], 0.25]
]);

$beherrscht_thema_score = weighted_average([
    [$profile['basiswissen'], 0.6],
    [$profile['performanz_effizienz'], 0.4]
]);

$transferdenken_score = weighted_average([
    [$profile['problemlösefähigkeit'], 0.6],
    [$profile['kreativität_innovation'], 0.4]
]);

$vorbereitet_score = weighted_average([
    [$profile['gewissenhaftigkeit'], 0.6],
    [$profile['zielorientierung'], 0.4]
]);

// didaktische mapping (1..4)
$themenauswahl_score = weighted_average([
    [$profile['resonanzfähigkeit'], 0.4],
    [$profile['anpassungsfaehigkeit'], 0.6]
]);

$materialien_score = weighted_average([
    [$profile['resonanzfähigkeit'], 0.3],
    [$profile['kreativität_innovation'], 0.7]
]);

$methodenvielfalt_score = weighted_average([
    [$profile['offenheit_erfahrungen'], 0.6],
    [$profile['ko-kreationsfähigkeit'] ?? ($profile['ko-kreationsfähigkeit'] ?? 3.0), 0.4]
]);

$individualisierung_score = weighted_average([
    [$profile['soziale_interaktion'], 0.5],
    [$profile['metakognition'], 0.5]
]);

$aufforderung_score = weighted_average([
    [$profile['resonanzfähigkeit'], 0.5],
    [$profile['Extraversion'], 0.5]
]);

// zielgruppen: einfache mapping 1..4 based on soziale_interaction (higher -> able to address more target groups)
$zielgruppen_score = map_score_to_1_4($profile['soziale_interaktion']);

// map to ints 1..4
$to_int = function($s) {
    return map_score_to_1_4((float)$s);
};

$insert_row = [
    'ue_zuweisung_teilnehmer_id' => $ue_zuweisung_teilnehmer_id,
    'teilnehmer_id' => $teilnehmer_id,
    'gruppe_id' => $gruppe_id,
    'einrichtung_id' => 1,
    // measured fields (int1..4)
    'mitarbeit' => $to_int($mitarbeit_score),
    'absprachen' => $to_int(weighted_average([ [$profile['vertraeglichkeit'], 0.6], [$profile['soziale_interaktion'], 0.4] ])),
    'selbststaendigkeit' => $to_int($selbststaendigkeit_score),
    'konzentration' => $to_int($konzentration_score),
    'fleiss' => $to_int($fleiss_score),
    'lernfortschritt' => $to_int($lernfortschritt_score),
    'beherrscht_thema' => $to_int($beherrscht_thema_score),
    'transferdenken' => $to_int($transferdenken_score),
    'basiswissen' => $to_int($profile['basiswissen']),
    'vorbereitet' => $to_int($vorbereitet_score),
    'themenauswahl' => $to_int($themenauswahl_score),
    'materialien' => $to_int($materialien_score),
    'methodenvielfalt' => $to_int($methodenvielfalt_score),
    'individualisierung' => $to_int($individualisierung_score),
    'aufforderung' => $to_int($aufforderung_score),
    'zielgruppen' => $to_int($zielgruppen_score),
];

// 3) Emotions-String heuristisch erzeugen (Namen, Komma-getrennt)
// Regeln (Beispiele):
// - Offenheit + Kreativität >= 3.5 -> Neugier, Motivation
// - Hohe Belastbarkeit & Gewissenhaftigkeit -> Zufriedenheit / Stolz (bei Erfolg, hier heuristisch Zufriedenheit)
// - Niedrige Stressbewaeltigung & niedrige Belastbarkeit -> Frustration / Ueberforderung
// - Hohe soziale_interaktion & resonanzfähigkeit -> Zugehoerigkeit, Vertrauen

$emotions = [];

if ($profile['offenheit_erfahrungen'] >= 3.5 && $profile['kreativität_innovation'] >= 3.0) {
    $emotions[] = 'Neugier';
    $emotions[] = 'Motivation';
}

if ($profile['gewissenhaftigkeit'] >= 3.5 && $profile['performanz_effizienz'] >= 3.0) {
    $emotions[] = 'Zufriedenheit';
    if ($profile['basiswissen'] >= 3.5) $emotions[] = 'Stolz';
}

if ($profile['stressbewaeltigung'] <= 2.5 || $profile['belastbarkeit'] <= 2.5) {
    $emotions[] = 'Frustration';
    if ($profile['stressbewaeltigung'] <= 1.5) $emotions[] = 'Ueberforderung';
}

if ($profile['soziale_interaktion'] >= 3.5 && $profile['resonanzfähigkeit'] >= 3.0) {
    $emotions[] = 'Zugehoerigkeit';
    $emotions[] = 'Vertrauen';
}

if ($profile['metakognition'] >= 3.5) {
    $emotions[] = 'Nachdenklichkeit';
}

// remove duplicates, limit to 8
$emotions = array_values(array_unique($emotions));
if (count($emotions) == 0) {
    // Default fallback emotion: Interesse oder Unsicherheit abhängig von offenheit
    $emotions[] = ($profile['offenheit_erfahrungen'] >= 3.0) ? 'Interesse' : 'Unsicherheit';
}
$emotions_str = implode(',', $emotions);

// Bemerkungen (kurz)
$bemerkungen = "Auto-generiert aus mtr_persoenlichkeit id={$profile['id']}; mapping heuristisch.";

// 4) Insert in mtr_rueckkopplung_teilnehmer
$insert_sql = "
    INSERT INTO mtr_rueckkopplung_teilnehmer
    (
        ue_zuweisung_teilnehmer_id, teilnehmer_id, gruppe_id, einrichtung_id,
        mitarbeit, absprachen, selbststaendigkeit, konzentration, fleiss,
        lernfortschritt, beherrscht_thema, transferdenken, basiswissen, vorbereitet,
        themenauswahl, materialien, methodenvielfalt, individualisierung, aufforderung,
        zielgruppen, emotions, bemerkungen
    ) VALUES (
        :ue_zt, :tid, :gid, :einr,
        :mitarbeit, :absprachen, :selbststaendigkeit, :konzentration, :fleiss,
        :lernfortschritt, :beherrscht_thema, :transferdenken, :basiswissen, :vorbereitet,
        :themenauswahl, :materialien, :methodenvielfalt, :individualisierung, :aufforderung,
        :zielgruppen, :emotions, :bemerkungen
    )
";
$stmt = $pdo->prepare($insert_sql);
$params = [
    ':ue_zt' => $insert_row['ue_zuweisung_teilnehmer_id'],
    ':tid' => $insert_row['teilnehmer_id'],
    ':gid' => $insert_row['gruppe_id'],
    ':einr' => $insert_row['einrichtung_id'],

    ':mitarbeit' => $insert_row['mitarbeit'],
    ':absprachen' => $insert_row['absprachen'],
    ':selbststaendigkeit' => $insert_row['selbststaendigkeit'],
    ':konzentration' => $insert_row['konzentration'],
    ':fleiss' => $insert_row['fleiss'],

    ':lernfortschritt' => $insert_row['lernfortschritt'],
    ':beherrscht_thema' => $insert_row['beherrscht_thema'],
    ':transferdenken' => $insert_row['transferdenken'],
    ':basiswissen' => $insert_row['basiswissen'],
    ':vorbereitet' => $insert_row['vorbereitet'],

    ':themenauswahl' => $insert_row['themenauswahl'],
    ':materialien' => $insert_row['materialien'],
    ':methodenvielfalt' => $insert_row['methodenvielfalt'],
    ':individualisierung' => $insert_row['individualisierung'],
    ':aufforderung' => $insert_row['aufforderung'],

    ':zielgruppen' => $insert_row['zielgruppen'],
    ':emotions' => $emotions_str,
    ':bemerkungen' => $bemerkungen
];

try {
    $stmt->execute($params);
    $newId = $pdo->lastInsertId();
    echo "Erfolgreich eingefügt: mtr_rueckkopplung_teilnehmer.id = {$newId}\\n";
    echo "Emotions: {$emotions_str}\\n";
} catch (PDOException $e) {
    echo "Insert fehlgeschlagen: \" . $e->getMessage() . \"\\n";
    exit(1);
}

/*
Kurz-Checkliste / Hinweise zur Anpassung

Passe die DB-Zugangsdaten ($host, $user, $pass) an.

Das Skript nimmt den zuletzt vorhandenen mtr_persoenlichkeit-Datensatz des Teilnehmers. Wenn du eine bestimmte id verwenden willst, passe die SELECT-Query an.

Die Mapping-Regeln sind bewusst transparent und leicht veränderbar; du kannst Gewichte, Schwellen oder resultierende Skalen ändern.

Emotions-Mapping erzeugt derzeit einen kommagetrennten String mit Emotionsnamen (z. B. Neugier,Motivation). Du kannst stattdessen auch IDs aus _mtr_emotionen verwenden oder die mtr_emotions-Tabelle befüllen — die DB-Strukturen dafür sind in icas.sql.

Die verwendeten Tabellen/Spalten entsprechen der icas.sql-Struktur.

Wenn du willst:

passe ich die Gewichtungen an (z. B. stärkere Gewichtung für metakognition bei selbststaendigkeit),

erweitere ich das Skript so, dass es gleichzeitig auch einen Eintrag in mtr_emotions anlegt (voller Emotions-Record mit einzelnen Boolean-Feldern), oder

erzeuge ich ein Web-Endpoint (POST) statt CLI-Skript.
*/