<?php
$words=[
"abspracheneinhaltend",
"beteiligtsich",
"gutemitarbeit",
"fleißig",
"bemüht",
"arbeitetselbstständig",
"konzentriert",
"vorbereitet",
"beherrschtthema",
"fähigzutransferdenken",
"lernfortschritterzielt",
"basiswissenvorhanden",
"störend ",
"blockierend ",
"resignierend",
"desinteressiert",
"gleichgültig",
"benötigtaufforderung"
];
function checkLastItem( $item ) {
    $tmp = end( $item );
    $resultArray = [];
    if( count( $tmp == 1 ) ) {
        foreach ($words as $element) {
            if (str_contains($item, $element)) {
                $resultArray[] = ",";
                $resultArray[] = $element;
            }
        }

    }
    if( count( $resultArray )> 2 ) {
        array_pop( $item );
        $item = array_merge( $item, $resultArray ); 
    }
    return $item;    
}
?>
