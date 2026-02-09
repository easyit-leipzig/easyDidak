<?php
header('Content-Type: application/json');

$host = "localhost";
$db   = "icas";
$user = "root";
$pass = "";

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$r = $pdo -> query("select * from mtr_rueckkopplung_datenmaske where thema is not null")->fetchAll(PDO::FETCH_ASSOC);
$l = count( $r ) ;
$i = 0;
while( $i < $l ) {
    /*
    bestimmen der werte aus _mtr_datenmaske_values_wertung
    */
    $tmp = explode(":", $r[$i]["thema"] );
    if( count( $tmp ) == 2 ) {
        $thema = $tmp[0];
        $unterthema = $tmp[1];
    $tmp = explode("|", $r[$i]["bemerkung"]);
    } else {
        $thema = $tmp[0];
        $unterthema = "";
    }
    $j = count( $tmp );
    $k = 0;
    $str = "";
    while( $k < $j ) {
        $str.= "'" . trim( $tmp[$k] ) ."',";
        $k++;     
    }
    $str = substr($str, 0, -1);
    
    $sql_dm = "select * from _mtr_datenmaske_values_wertung where value in (" . $str . ")";
    
    $r_dm = $pdo->query($sql_dm)->fetchAll(PDO::FETCH_ASSOC);
    /* */
    /*
    bestimmen startzeit aus ue_gruppen
    */
    $sql_gr = "select uhrzeit_start from ue_gruppen where id = " . $r[$i]["gruppe_id"];
    $r_gr = $pdo->query()->fetchAll(PDO::FETCH_ASSOC);
    $datum_zeit = $r[$i]["datum"] . " " . $r_gr[0]["uhrzeit_start"];
    /**/
    /*
    bestimmen ue_id
    */
    $sql_ue = "select id from ue_unterrichtseinheit where datum='" . $r[$i]["datum"] . "' and zeit='" . $r_gr[0]["uhrzeit_start"] . "'" ;
    $r_ue = $pdo->query()->fetchAll(PDO::FETCH_ASSOC);
    if( count() == 0 ) {
        $ue_id = null;
    } else {
        $ue_id = $r_ue[0]["id"];
    }
    $k = count( $r_dm );
    $j = 0;
    while( $j < $k ) {
        /**/
        /* insert values */
        /*
        INSERT INTO `mtr_rueckkopplung_lehrkraft_datenmaske` 
            (`_mtr_datenmaske_values_wertung_id`, `ue_id`, `fach`, `thema`, `unterthema`, 
            `lehrkraft`, `datum_zeit`, `gruppe_id`, `teilnehmer_id`, `value`, `note`, `wichtung`, 
            `emotional`, `affektiv`, `kognitiv`, `sozial`, `leistung`, `valenz_avg`, `aktivierung_avg`, `gesamt_index`) 
            VALUES 
            ('1', '2', 'MAT', 'Thema', 'Unterthema', 'lehrer', '2026-02-06 07:31:03', '3', '4', 'bemerkung', 
            '4.0', '4.0', '4.0', '4.0', '4.0', '4.0', '4.0', '4.0', '4.0', '4.0')
        */
        
        /**/
        $fill_dm_sql = "
        INSERT IGNORE INTO `mtr_rueckkopplung_lehrkraft_datenmaske` 
            (`_mtr_datenmaske_values_wertung_id`, `ue_id`, `fach`, `thema`, `unterthema`, 
            `lehrkraft`, `datum_zeit`, `gruppe_id`, `teilnehmer_id`, `value`, `note`, `wichtung`, 
            `emotional`, `affektiv`, `kognitiv`, `sozial`, `leistung`, `valenz_avg`, `aktivierung_avg`, `gesamt_index`) 
            VALUES 
            (
            " . $r_dm[$j]["id"] . "
            , $ue_id
            , '2'
            , '" . $r[$i]["fach"] . "'
            , '$thema'
            , '$uerthema'
            , '" . $r[$i]["lehrkraft
            , '2026-02-06 07:31:03'
            , '3'
            , '4'
            , 'bemerkung'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            , '4.0'
            )
        
        
        
        
        
        
        
        
        
        ";
        $j++;
    }
    $i++;
}
?>
