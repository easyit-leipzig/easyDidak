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
    $tmp ="";
    $words = [
        "absprachen einhaltend",
        "beteiligt sich",
        "gute mitarbeit",
        "fleissig",
        "bemueht",
        "arbeitet selbststaendig",
        "konzentriert",
        "vorbereitet",
        "lernfortschritt erzielt",
        "beherrscht thema",
        "faehig zu transferdenken",
        "stoerend",
        "blockierend",
        "resignierend",
        "benoetigt aufforderung",
        "basiswissen vorhanden"         
    ];
    $rep = [
        "abspracheneinhaltend",
        "beteiligtsich",
        "gutemitarbeit",
        "fleissig",
        "bemueht",
        "arbeitetselbststaendig",
        "konzentriert",
        "vorbereitet",
        "lernfortschritterzielt",
        "beherrschtthema",
        "faehigzutransferdenken",
        "stoerend",
        "blockierend",
        "resignierend",
        "benoetigtaufforderung",
        "basiswissenvorhanden"         
    ];            
    $l = count( $words );
    $i = 0;
    $switcher = false;
    $pos = 0;
    while( $i < $l ) {
        if (str_contains($text, $words[$i])) {
            if( !$switcher ) {
                $pos = strpos($text, $words[$i]); 
                $switcher = true;   
            }
            if( $words[$i]== "benoetigt aufforderung") {
                $z=1;
            }
            $tmp .= $rep[$i]  . " ";
        }        
        $i += 1;
    }
    // 3. Alles entfernen, was nicht a-z oder Leerzeichen ist
    if( $tmp != "" ) {
        $text = $tmp;
    }
    $text = preg_replace('/[^a-z\s,;()]/', '', $text);

    // 4. Tokenisieren
    //$tokens = preg_split('/\s+/', $text); 
    preg_match_all('/\w+|[^\w\s]/u', $text, $matches);
    $tokens = $matches[0];   

    // 5. Leere entfernen
    return array_filter($tokens);
}

?>
