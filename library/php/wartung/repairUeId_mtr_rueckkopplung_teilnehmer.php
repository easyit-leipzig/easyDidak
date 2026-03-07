<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$r = $pdo ->query("select id, erfasst_am from mtr_rueckkopplung_teilnehmer") -> fetchAll(PDO::FETCH_ASSOC);
$l = count( $r );
$i = 0;
while( $i < $l ) {
    
    $dt = new \DateTime( $r[$i]["erfasst_am"] );
    $dt_time = $dt->format("H:i:s");
    $dt_date = $dt->format("Y-m-d");
    $vt = new \DateTime("17:10:00");
    if( $dt_time < $vt and ($dt_time!="15:35:00" or $dt_time!="17:10:00") ) {
        $write_date = $dt_date . " " . "15:35:00";
    $pdo -> exec("update mtr_rueckkopplung_teilnehmer set erfasst_am='" . $write_date . "' where id =" . $r[$i]["id"]);
    } else {
        $write_date = $dt_date . " " . "17:10:00";
    $pdo -> exec("update mtr_rueckkopplung_teilnehmer set erfasst_am='" . $write_date . "' where id =" . $r[$i]["id"]);
        
    }
    $i += 1;                                               
} 
?>
