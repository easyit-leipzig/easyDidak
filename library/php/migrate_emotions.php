<?php
    $bew = $db_pdo -> query( "SELECT * FROM mtr_rueckkopplung_teilnehmer" )->fetchAll();
    $l = count( $bew );
    $i = 0;
    while( $i < $l ) {
        $datum = substr( $bew[$i]["erfasste_am"],0 , 10);
        $ues =  $db_pdo -> query( "SELECT * FROM ue_unterrichtseinheit where datum ='$datum'" )->fetchAll();
        $zuwThema = $db_pdo -> query( "SELECT * FROM ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id =" . $ues[0]["id"] )->fetchAll();
        $m = count( $zuwThema );
        $j = 0;
        while( $j < $m ) {
            $zuwTN = $db_pdo -> query( "SELECT * FROM ue_zuweisung_teilnehmer where ue_unterrichtseinheit_zw_thema_id  =" . $zuwThema[$j]["id"] )->fetchAll(); 
            $j += 1;
        }
         
        $i += 1;
    }
?>
