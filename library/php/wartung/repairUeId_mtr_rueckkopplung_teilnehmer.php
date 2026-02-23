<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$r = $pdo ->query("select * from mtr_rueckkopplung_teilnehmer where ue_id =0 and ue_zuweisung_teilnehmer_id=0") -> fetchAll(PDO::FETCH_ASSOC);
$l = count( $r );
$i = 0;
while( $i < $l ) {
    
    $dt = new \DateTime( $r[$i]["erfasst_am"] );
    $dt_time = $dt->format("H:i:s");
    $dt_date = $dt->format("Y-m-d");
    $vt = new \DateTime("17:10:00");
    if( $dt_time < $vt ) {
        $write_date = $dt_date . " " . "15:35:00";
    } else {
        $write_date = $dt_date . " " . "17:10:00";
        
    }
    $pdo -> exec("update mtr_rueckkopplung_teilnehmer set erfasst_am='" . $write_date . "' where id =" . $r[$i]["id"]);
    $i += 1;                                               
} 
?>
