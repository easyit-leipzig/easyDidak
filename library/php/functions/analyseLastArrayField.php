<?php
function checkLastItem( $item ) {
$words=[
"abspracheneinhaltend",
"beteiligtsich",
"gutemitarbeit",
"fleissig",
"bemueht",
"arbeitetselbststaendig",
"konzentriert",
"vorbereitet",
"beherrschtthema",
"faehigzutransferdenken",
"lernfortschritterzielt",
"basiswissenvorhanden",
"stoerend ",
"blockierend ",
"resignierend",
"desinteressiert",
"gleichgueltig",
"benoetigtaufforderung"
];
    $tmp = end( $item );
    $resultArray = [];
    //if( count( $tmp == 1 ) ) {
        foreach ($words as $element) {
            if (str_contains($tmp, $element)) {
                $resultArray[] = ",";
                $resultArray[] = $element;
            }
        }

    //}
    if( count( $resultArray )> 2 ) {
        array_pop( $item );
        $item = array_merge( $item, $resultArray ); 
    }
    return $item;    
}
?>
