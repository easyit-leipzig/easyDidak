<?php
  function tokenize($text) {

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
    $text = preg_replace('/[^a-z\s,.;]/', '', $text);

    // 4. Tokenisieren
    //$tokens = preg_split('/\s+/', $text); 
    preg_match_all('/\w+|[^\w\s]/u', $text, $matches);
    $tokens = $matches[0];   

    // 5. Leere entfernen
    return array_filter($tokens);
}

?>
