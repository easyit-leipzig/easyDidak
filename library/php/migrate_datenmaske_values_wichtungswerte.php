<?php
$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');

function normalize($value, $min = 1, $max = 6) {
    return max(0.0, min(1.0, 1 - (($value - $min) / ($max - $min))));
}

// Hilfsfunktion: Ähnlichkeitsmaß (Levenshtein + Anteil)
function similarity($a, $b) {
    $a = mb_strtolower(trim($a));
    $b = mb_strtolower(trim($b));
    $maxLen = max(strlen($a), strlen($b));
    if ($maxLen == 0) return 0;
    $lev = levenshtein($a, $b);
    return 1 - ($lev / $maxLen);
}

// Emotionen abrufen
$emotions = $pdo->query("SELECT emotion, map_field, type_name, valenz, aktivierung FROM _mtr_emotionen")
                ->fetchAll(PDO::FETCH_ASSOC);

// Werte aus der Datenmaske laden
$values = $pdo->query("SELECT DISTINCT value FROM mtr_rueckkopplung_datenmaske_values WHERE value <> ''")
              ->fetchAll(PDO::FETCH_COLUMN);

foreach ($values as $text) {
    // Tokenisierung: Wörter extrahieren, normalisieren
    $tokens = preg_split('/[^a-zA-ZäöüÄÖÜß]+/u', mb_strtolower($text));
    $tokens = array_filter($tokens, fn($t) => strlen($t) > 2);

    $valenz_sum = 0;
    $aktiv_sum = 0;
    $hits = 0;

    foreach ($emotions as $emo) {
        $map = mb_strtolower($emo['map_field']);
        $emoWord = mb_strtolower($emo['emotion']);
        foreach ($tokens as $tok) {
            // 3-stufiges Matching
            if ($tok === $map || $tok === $emoWord) {
                $weight = 1.0;
            } elseif (str_contains($tok, $map) || str_contains($map, $tok)) {
                $weight = 0.6;
            } elseif (similarity($tok, $map) >= 0.6 || similarity($tok, $emoWord) >= 0.6) {
                $weight = 0.4;
            } else continue;

            // Kumulative Gewichtung
            $valenz_sum += $emo['valenz'] * $weight;
            $aktiv_sum += $emo['aktivierung'] * $weight;
            $hits += $weight;
        }
    }

    $valenz_avg = $hits ? $valenz_sum / $hits : 0;
    $aktivierung_avg = $hits ? $aktiv_sum / $hits : 0;

    // Simulation für andere Felder
    $wichtungsfaktor = round((abs($valenz_avg) + $aktivierung_avg) / 2, 2); // note = emotionale Relevanz
    $wichtung = rand(1, 10);

    $emotional = normalize(rand(1,6));
    $affektiv = normalize(rand(1,6));
    $kognitiv = normalize(rand(1,6));
    $sozial = normalize(rand(1,6));
    $leistung = normalize(rand(1,6));

    // Existenz prüfen
    $check = $pdo->prepare("SELECT id FROM _mtr_datenmaske_values_wertung WHERE value = ?");
    $check->execute([$text]);
    $id = $check->fetchColumn();

    if (!$id) {
        $insert = $pdo->prepare("
            INSERT INTO _mtr_datenmaske_values_wertung
            (value, note, wichtung, emotional, affektiv, kognitiv, sozial, leistung, valenz_avg, aktivierung_avg, last_update)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insert->execute([
            $text,
            $wichtungsfaktor,
            $wichtung,
            $emotional,
            $affektiv,
            $kognitiv,
            $sozial,
            $leistung,
            $valenz_avg,
            $aktivierung_avg
        ]);
    } else {
        $update = $pdo->prepare("
            UPDATE _mtr_datenmaske_values_wertung
            SET note=?, wichtung=?, emotional=?, affektiv=?, kognitiv=?, sozial=?, leistung=?,
                valenz_avg=?, aktivierung_avg=?, last_update=NOW()
            WHERE id=?
        ");
        $update->execute([
            $wichtungsfaktor,
            $wichtung,
            $emotional,
            $affektiv,
            $kognitiv,
            $sozial,
            $leistung,
            $valenz_avg,
            $aktivierung_avg,
            $id
        ]);
    }
}

echo "✅ Deep-Granulare Integration abgeschlossen – Valenz/Aktivierung jetzt mehrstufig berechnet.\n";
?>
