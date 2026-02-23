<?php
header('Content-Type: text/plain; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "select id, teilnehmer_id, datum from mtr_rueckkopplung_datenmaske where thema is not null and gruppe_id = 0";
$r_vals = $pdo -> query( $sql ) -> fetchAll( PDO::FETCH_ASSOC );
$l = count( $r_vals );
$i = 0;
while( $i < $l ) {
    $dt = new DateTime( $r_vals[$i]["datum"] );
    $date1 = $dt->format('w');
    $sql = "select id from ue_gruppen where day_number = '" . $date1 + 1 . "'";
    $r_ue = $pdo -> query( $sql ) -> fetchAll( PDO::FETCH_ASSOC );
    if( count( $r_ue ) > 0 ) {
        $pdo -> exec( "update mtr_rueckkopplung_datenmaske set gruppe_id=" . $r_ue[0]["id"] . " where id = " . $r_vals[$i]["id"] );   
    }
    $i += 1;
} 
?>
