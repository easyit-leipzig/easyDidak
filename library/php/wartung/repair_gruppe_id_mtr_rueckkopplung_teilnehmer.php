<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$r = $pdo -> query("select id, gruppe_id, erfasst_am from mtr_rueckkopplung_teilnehmer 
    order by id")-> fetchAll(PDO::FETCH_ASSOC);  
$l = count( $r );
$i = 0;
while( $i < $l ) {
    $dt = new \DateTime($r[$i]["erfasst_am"]);
    $wt = $dt->format("w");
    $start_zeit = $dt->format("H:i:s");
    $wt_gr = $wt + 1;
    // hole gruppe_id
    $r_gr = $pdo -> query("select id from ue_gruppen where day_number=$wt_gr and uhrzeit_start='" . $start_zeit . "'") -> fetchAll(PDO::FETCH_ASSOC);    
    if( count($r_gr)==1) $pdo -> exec("update mtr_rueckkopplung_teilnehmer set gruppe_id=" . $r_gr[0]["id"] . " where id=" . $r[$i]["id"]);
    $i += 1;
} 



?>
