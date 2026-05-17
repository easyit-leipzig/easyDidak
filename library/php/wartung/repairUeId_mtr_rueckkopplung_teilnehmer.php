<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$r = $pdo ->query("select * from mtr_rueckkopplung_teilnehmer where ue_id=0") -> fetchAll(PDO::FETCH_ASSOC);
$l = count( $r );
$i = 0;
while( $i < $l ) {
    $d = new \DateTime($r[$i]["erfasst_am"]);
    $day = $d->format("Y-m-d");
    $time = $d->format("H:i:s");
    $ue_id = $r[$i]["ue_id"];
    $ues = $pdo->query("select id from ue_unterrichtseinheit where datum='$day' and zeit='$time' and gruppe_id=" . $r[$i]["gruppe_id"])-> fetchAll(PDO::FETCH_ASSOC);
    if( count($ues) == 0 ) {
        $pdo -> exec("INSERT INTO `ue_unterrichtseinheit` (`gruppe_id`, `einrichtung_id`, `datum`, `zeit`, `dauer`, `beschreibung`) 
                        VALUES (" . $r[$i]["gruppe_id"] . ", '1', '" . $day . "', '" . $time. "', '90', 'Gruppenveranstaltung')");
        $newUeId = $pdo->lastInsertId();
        $pdo->exec("update mtr_rueckkopplung_teilnehmer set ue_id=$newUeId where id=" . $r[$i]["id"]);
    } else {
        $newUeId = $ues[0]["id"];
        $pdo->exec("update mtr_rueckkopplung_teilnehmer set ue_id=$newUeId where id=" . $r[$i]["id"]);
    }
    /*
    $ueTh = $pdo->query("SELECT * FROM `ue_unterrichtseinheit_zw_thema` where ue_unterrichtseinheit_id=$newUeId")->fetchAll(PDO::FETCH_ASSOC);
    if( count( $ueTh ) ==0 ) {
        $pdo->exec("INSERT INTO `ue_unterrichtseinheit_zw_thema` (`ue_unterrichtseinheit_id`, `datum`, `lehrkraft_id`, `schulform_id`, `fach_id`, 
                    `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `bemerkungen`, `dauer`, `teilnehmer_id`, `beschreibung`) 
                    VALUES ($newUeId,'" . $day . " " . $time . "' , '1', '', '1', '', '', '', '', '', '', " . $r[$i]["teilnehmer_id"] . ", 'Gruppe " . $r[$i]["gruppe_id"] . "')");
        $newThId=$pdo->lastInsertId();
    } else {
        $newThId = $ueTh[0]["id"];
        
    }
    $pdo->exec("UPDATE ue_unterrichtseinheit_zw_thema
                    SET teilnehmer_id = CASE
                        WHEN teilnehmer_id IS NULL OR teilnehmer_id = '' THEN " . $r[$i]["teilnehmer_id"] . "
                            WHEN FIND_IN_SET('" . $r[$i]["teilnehmer_id"] . "', teilnehmer_id) = 0 THEN CONCAT(teilnehmer_id, '," . $r[$i]["teilnehmer_id"] . "')
                                ELSE teilnehmer_id
                        END
                    WHERE id = $newThId;");
    */
    $i += 1;                                               
} 
?>
