<?php
function frzk_map_funktionsklasse($lemma, $pdo) {

    $sql = "
        SELECT m.funktionsklasse_id, m.wortart
        FROM frzk_lexem_mapping m
        WHERE m.lexem = :lemma
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lemma' => $lemma
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result !== false) {
        return (int)$result['funktionsklasse_id'];
    }

    return null; // unbekanntes Lexem
}
function frzk_lemmatize($token) {

    // einfache Verbnormalisierung
    $verb_endungen = ['te','ten','test','tet','t','en'];

    foreach ($verb_endungen as $endung) {
        if (mb_substr($token, -mb_strlen($endung)) === $endung) {
            return mb_substr($token, 0, -mb_strlen($endung)) . 'en';
        }
    }

    return $token;
}
function frzk_classify_pos($lemma) {

    $intensitaet = ['sehr','stark','deutlich','kaum','teilweise','besonders'];
    $negationen = ['nicht','kein','keine','nie'];

    if (in_array($lemma, $intensitaet)) return 'adverb';
    if (in_array($lemma, $negationen)) return 'negation';

    if (mb_substr($lemma, -2) === 'en') return 'verb';

    if (mb_substr($lemma, -2) === 'ig' || mb_substr($lemma, -3) === 'end')
        return 'adjektiv';

    return 'nomen';
}
function frzk_tokenize($text) {

    // 1. Lowercase
    $text = mb_strtolower($text, 'UTF-8');

    // 2. Umlaute normalisieren (kanonische ASCII-Form)
    $umlaute = [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ];

    $text = strtr($text, $umlaute);

    // 3. Alles entfernen, was nicht a-z oder Leerzeichen ist
    $text = preg_replace('/[^a-z\s]/', '', $text);

    // 4. Tokenisieren
    $tokens = preg_split('/\s+/', $text);

    // 5. Leere entfernen
    return array_filter($tokens);
}

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("truncate frzk_lexem");
$pdo->exec("truncate frzk_pattern");
$pdo->exec("truncate frzk_pattern_lexem");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
$stmt = $pdo->query("SELECT DISTINCT value_nlp FROM _mtr_datenmaske_values_wertung 
                        WHERE value_nlp IS NOT NULL and length(value_nlp)>50");
//$stmt = $pdo->query("SELECT value as value_nlp FROM mtr_rueckkopplung_datenmaske_values 
//                        WHERE length(value)>50");
                        

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $text = trim($row['value_nlp']);
    if ($text === '') continue;

    // 1. Tokenisierung
    $tokens = frzk_tokenize($text);

    foreach ($tokens as $token) {

        // 2. Lemmatisierung
        $lemma = frzk_lemmatize($token);

        if (!$lemma) continue;

        // 3. Wortart bestimmen
        $wortart = frzk_classify_pos($lemma);

        // 4. Funktionsklasse bestimmen
        $funktionsklasse = frzk_map_funktionsklasse($lemma, $wortart,$pdo);

        // 5. Insert nur wenn noch nicht vorhanden
        $insert = $pdo->prepare("
            INSERT IGNORE INTO frzk_lexem (lexem, wortart, funktionsklasse_id)
            VALUES (?, ?, ?)
        ");

        $insert->execute([$lemma, $wortart, $funktionsklasse]);
        // Pattern speichern
$insertPattern = $pdo->prepare("
    INSERT IGNORE INTO frzk_pattern (pattern) VALUES (?)
");
$insertPattern->execute([$text]);

$patternId = $pdo->lastInsertId();
if (!$patternId) {
    $patternId = $pdo->query("
        SELECT id FROM frzk_pattern 
        WHERE pattern=".$pdo->quote($text)
    )->fetchColumn();
}
        $lexemId = $pdo->query("
    SELECT id FROM frzk_lexem 
    WHERE lexem=".$pdo->quote($lemma)
)->fetchColumn();

$insertLink = $pdo->prepare("
    INSERT IGNORE INTO frzk_pattern_lexem 
    (pattern_id, lexem_id)
    VALUES (?,?)
");

$insertLink->execute([$patternId,$lexemId]);

    }
}

echo "Extraktion abgeschlossen.\n";
