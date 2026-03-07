<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$r = $pdo -> query("select id, erfasst_am from mtr_rueckkopplung_teilnehmer") -> fetchAll(PDO::FETCH_ASSOC);
$l = count( $r );
$i = 0;
while( $i < $l ) {
    $dt = new \DateTime($r[$i]["erfasst_am"]);
    $dt_time = $dt -> format("H:i:s");
    $dt_time = new \DateTime($dt_time);
    $dt_day = $dt -> format("Y-m-d");
    $dv = new \DateTime("17:09:00");
    $sql="";
    $tmp = $dt_time->format("H:i:s");
    if( $dt_time < $dv ) {
        if( $tmp != "15:35:00" ) {
            $sql = "update mtr_rueckkopplung_teilnehmer set erfasst_am='" . $dt_day . " 15:35:00' where id=" . $r[$i]["id"];   
        }
    } else {
        if( $tmp != "17:10:00" ) {
            $sql = "update mtr_rueckkopplung_teilnehmer set erfasst_am='" . $dt_day . " 17:10:00' where id=" . $r[$i]["id"];   
        }
        
    }
    if( $sql != "" ) {
        $pdo -> exec($sql);        
    }
    $i += 1;
} 

?>
