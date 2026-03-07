<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo -> exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo -> exec("truncate ue_unterrichtseinheit");
$pdo -> exec("truncate ue_unterrichtseinheit_zw_thema");
$pdo -> exec("truncate ue_zuweisung_teilnehmer");

$r = $pdo -> query("select gruppe_id, erfasst_am from mtr_rueckkopplung_teilnehmer 
     group by erfasst_am, gruppe_id order by id")-> fetchAll(PDO::FETCH_ASSOC);  
$l = count( $r );
$i = 0;
while( $i < $l ) {
    $dt = new \DateTime($r[$i]["erfasst_am"]);
    $wt = $dt->format("w");
    $start_zeit = $dt->format("H:i:s");
    $wt_gr = $wt + 1;
    // hole tn für datum und gruppe
    $r_tn = $pdo -> query("select * from mtr_rueckkopplung_teilnehmer where gruppe_id=" . 
                        $r[$i]["gruppe_id"] . " and erfasst_am='" .                          
                        $r[$i]["erfasst_am"] . "'") -> fetchAll(PDO::FETCH_ASSOC);
    $k = count( $r_tn );
    $j = 0;
    $ue_zw_tn_id = [];
    $r_id =[];
    $r_tn_id=[];
    while( $j < $k ) {
$pdo -> exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo -> exec("INSERT INTO `ue_zuweisung_teilnehmer` 
                        (`datum`, `gruppe_id`, `teilnehmer_id`) 
                        VALUES ('" . $r_tn[$j]["erfasst_am"] . 
                        "', " . $r_tn[$j]["gruppe_id"] . 
                        ", " . $r_tn[$j]["teilnehmer_id"] . ")");
        $ue_zw_tn_id[] = $pdo -> lastInsertId();
$pdo -> exec("SET FOREIGN_KEY_CHECKS = 1;");
        // neue ids in ue_zuweisung_teilnehmer
        $pdo -> exec("SET FOREIGN_KEY_CHECKS = 1;");
        // ids aus mtr_rueckkopplung_teilnehmer zur rückkopplung
        $r_id[] = $r_tn[$j]["id"];
        // tn ids aus mtr_rueckkopplung_teilnehmer zur rückkopplung
        $r_tn_id[] = $r_tn[$j]["teilnehmer_id"];
        $j += 1;
    }
    // join tn_ids for ue_unterrichtseinheit_zw_thema
    $ids_tn = join( ",", $r_tn_id );
    // join tn_id for select in
    $ids_tn_for_in = "'" . join( "','", $r_tn_id ) . "'";
    // join ids mtr_rueckkopplung_teilnehmer für in
    $ids_ue_zw_tn = "'" . join("','", $ue_zw_tn_id) . "'";
    // aktualisiere id_zw_thema in mtr_rueckkopplung_teilnehmer
    //$pdo -> exec("update mtr_rueckkopplung_teilnehmer set ")  
    // get schulform from std_teilnehmer
    $r_schulform = $pdo -> query("select KlassentypID from std_teilnehmer where id in (" . $ids_tn_for_in . ")") -> fetchAll(PDO::FETCH_ASSOC);    
    // neuer ds in ue_unterrichtseinheit_zw_thema
    /*
    INSERT INTO `ue_unterrichtseinheit_zw_thema` ( `datum`, `lehrkraft_id`, 
    `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `bemerkungen`, 
    `dauer`, `teilnehmer_id`, `beschreibung`) 
    VALUES ( '2026-02-18 07:22:18', '1', '2', '1', '3', '24', '', '', '', '90', '1,4,5', 'Gruppe 1')
    */
    // aktualisiere ue_unterrichtseinheit_zw_thema

    $pdo -> exec("INSERT INTO `ue_unterrichtseinheit_zw_thema` ( `datum`, `lehrkraft_id`, 
    `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `bemerkungen`, 
    `dauer`, `teilnehmer_id`, `beschreibung`) 
    VALUES ( '" . $r[$i]["erfasst_am"] . "', 
    '1', 
    '2', 
    '1', 
    '3', 
    '24', 
    '', 
    '', 
    '', 
    '90', 
    '" . $ids_tn . "', 
    'Gruppe " . $r[$i]["gruppe_id"] . "')");
    $new_id_zw_thema = $pdo -> lastInsertId();
    // aktualisiere ue_zuweisung_teilnehmer
    $pdo -> exec("update ue_zuweisung_teilnehmer set ue_unterrichtseinheit_zw_thema_id=" . $new_id_zw_thema . " where id in (" . $ids_ue_zw_tn . ")");
    // neuer ds in ue_unterrichtseinheit
    /*
    INSERT INTO `ue_unterrichtseinheit` (`gruppe_id`, `einrichtung_id`, 
    `datum`, `zeit`, `dauer`, `beschreibung`) 
    VALUES ('2', '1', '2026-02-01', '09:23:29', '90', 'aa')
    */
    $tmp = explode(" ", $r[$i]["erfasst_am"]);
    $pdo -> exec("INSERT INTO `ue_unterrichtseinheit` (`gruppe_id`, `einrichtung_id`, 
    `datum`, `zeit`, `dauer`, `beschreibung`) 
    VALUES (
    '" . $r[$i]["gruppe_id"] . "', 
    '1', 
    '" . $tmp[0] . "', 
    '" . $tmp[1] . "', '90', 'Gruppenveranstaltung')");
    $ue_id = $pdo -> lastInsertId();
    // rückkopplung
    // 1. ue_unterrichtseinheit_zw_thema
    $pdo -> exec("update ue_unterrichtseinheit_zw_thema set ue_unterrichtseinheit_id=$ue_id where id =" . $new_id_zw_thema);
    // 2. mtr_rueckkopplung_teilnehmer
    $k = count( $r_id );
    $j = 0;
    while( $j < $k ) {
        $pdo -> exec("update mtr_rueckkopplung_teilnehmer set ue_id = $ue_id, ue_zuweisung_teilnehmer_id=" . $ue_zw_tn_id[$j] . " where id = " . $r_id[$j]);
        $j += 1;
    } 
    
    $i += 1;
} 

$pdo -> exec("SET FOREIGN_KEY_CHECKS = 1;");


?>
