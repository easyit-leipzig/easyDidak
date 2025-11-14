<?php
session_start();
error_reporting( E_ALL ^E_NOTICE );
date_default_timezone_set('Europe/Berlin');
// fetch call to $_POST variables
$json = file_get_contents("php://input");
if (!empty($json)) {
    $data = json_decode($json, true);
    foreach ($data as $key => $value) {
        $_POST[$key] = $value;
    }
}
// end fetch
define( "ROOT", "../../"); 
//var_dump( $_POST );
foreach($_POST  as $key => $val ){
  
    // Accessing individual elements
    $i =  $key;
    $j = json_decode( $i );
    if( !is_null( $j ) ) {
        foreach( $j as  $key => $val ) {
            //if( is_numeric( $val ) ) continue;
            $_POST[$key] = $val;
        }        
    }
}
/*
$_POST["command"] = "setGroup";
$_POST["id"] = "45";
*/
$return = new \stdClass();
$return -> command = $_POST["command"];
if( isset( $_POST["param"] ) ) {
    $return -> param = $_POST["param"];
}
$settings = parse_ini_file('../../ini/settings.ini', TRUE);

$dns = $settings['database']['type'] . 
            ':host=' . $settings['database']['host'] . 
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
            ';dbname=' . $settings['database']['schema'];
try {
    $db_pdo = new \PDO( $dns, $settings['database']['username'], $settings['database']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
    $db_pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_pdo -> setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false );
}
catch( \PDOException $e ) {
    $return -> command = "connect_error";
    $return -> message = $e->getMessage();
    print_r( json_encode( $return ));
    die;
}
require_once("functions.php"); 
foreach ( $_POST as &$str) {
    //var_dump($str);
    $str = replaceUnwantetChars($str);
}
function sanitize_column_name($str) {
    $str = str_replace(
        ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'],
        ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'],
        $str
    );
    // Nur Buchstaben, Zahlen und Unterstrich erlauben
    return preg_replace('/[^A-Za-z0-9_]/', '', $str);
}
function setEmotions($db_pdo,$tmpTN,$zwId,$datum, $tmpEmotions){
    try {
        // 1) Emotions-Mapping laden
        if( $tmpEmotions === "") {
                $db_pdo->query( "insert into mtr_emotions (teilnehmer_id, datum,     ue_zuweisung_teilnehmer_id ) values ($tmpTN,'$datum',$zwId)");
            } else {
            $stmt = $db_pdo->query("SELECT id, map_field FROM _mtr_emotionen where id in ($tmpEmotions) ORDER BY id")->fetchAll();
            $l = count( $stmt );
            $i = 0;
            $str_fields = "";
            $str_values = "";
            while( $i < $l ) {
                $str_fields .= $stmt[$i]["map_field"] . ",";
                $str_values.= "1,";
                $i += 1;
            }
            $db_pdo->query( "insert into mtr_emotions ($str_fields teilnehmer_id, datum,     ue_zuweisung_teilnehmer_id ) values($str_values$tmpTN,'$datum',$zwId)");
        }
        return;
    } catch (Exception $e) {
        if (isset($db_pdo) && $db_pdo->inTransaction()) {
            $db_pdo->rollBack();
        }
        echo "Fehler: " . $e->getMessage() . "\n";
        exit(1);
    }

}
/* mock $_POST */
/**/
switch( $_POST["command"]) {
    // start standard functions
    case "setGroup":
                            $query = "SELECT teilnehmer_id, erfasst_am, emotions FROM `mtr_rueckkopplung_teilnehmer` where id=" . $_POST["id"] ;
                            $return -> s = $query;
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $tnId =  $result[0]["teilnehmer_id"];
                            $date = $result[0]["erfasst_am"];
                            $diff = 30;
                            $a = new DateTime($date);
                            $b = new DateTime($date);
                            $tmpTN = $result[0]["teilnehmer_id"];
                            $tmpEmotions = $result[0]["emotions"];
                            $return -> startdiff = $a->sub(new DateInterval('PT' . $diff . 'M'));
                            $return -> enddiff = $b->add(new DateInterval('PT' . $diff . 'M'));
                            $return -> currentDate = new DateTime();
                            $return -> currentDate->setTime(0, 0, 0);
                            $return -> wochentag = $return -> currentDate->format('N')+1;
                            $query = "select id, uhrzeit_ende, uhrzeit_start FROM `ue_gruppen` where day_number=" . $return -> wochentag . " and uhrzeit_ende between '" .  $return -> startdiff->format('H:i:s') . "' and '" . $return -> enddiff->format('H:i:s') . "'";
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            if( count($result)===0) {
                                print_r( json_encode( $result )); 
                                return;
                            }
                            $groupId = $result[0]["id"];
                            $l = count( $result );
                            $i = 0;
 
                            while( $i < $l ) {
                                $timearr =  explode( ":", $result[$i]["uhrzeit_ende"] );
                                $return -> currentDate->setTime( $timearr[0], $timearr[1] );
                                if ( $return -> currentDate >= $return -> startdiff && $return -> currentDate <= $return -> enddiff ) {
                                    $q = "update mtr_rueckkopplung_teilnehmer set gruppe_id=" . $result[$i]["id"] . " where id=". $_POST["id"];
                                    $db_pdo -> query( $q );
                                    $a =  $result[$i]["uhrzeit_start"];
                                    $timearr =  explode( ":", $result[$i]["uhrzeit_start"] );
                                    $group_id = $result[$i]["id"];
                                    $return -> currentDate->setTime( $timearr[0], $timearr[1] );                            
                                    $mysqlDate = $return -> currentDate->format('Y-m-d');
                                    $q = "select id from ue_unterrichtseinheit where datum= '" . $mysqlDate . "' and gruppe_id=" . $result[$i]["id"];
                                    try {
                                        $stm = $db_pdo -> query( $q );
                                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                    } catch ( Exception $e ) {
                                        $return -> success = false;
                                        $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                        return $return;   
                                    }
                                    if( count( $result ) === 0 ) {
                                        $q = $result;
                                        // 1. neue ue
                                        $q = "INSERT INTO `ue_unterrichtseinheit` (`gruppe_id`, `datum`, `zeit`, `beschreibung`) VALUES (" . $group_id . ", '" . $return -> currentDate->format('Y-m-d') . "', '" . $return -> currentDate->format('H:i:s') . "',  'Gruppenveranstaltung')";
                                        $db_pdo -> query( $q );
                                        $newId = $db_pdo -> lastInsertId();
                                        $db_pdo->exec("update mtr_rueckkopplung_teilnehmer set ue_id=" . $newId . " where id=". $_POST["id"]);
                                        $q = "INSERT INTO `ue_unterrichtseinheit_zw_thema` (`ue_unterrichtseinheit_id`, `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `dauer`, `teilnehmer_id`, `beschreibung`) 
                                                VALUES ($newId, '', '1', '1', 24, '', '', 90, $tnId, 'Gruppe " . $groupId . "')" ;
                                        $db_pdo -> query( $q );
                                        $ueId = $db_pdo -> lastInsertId();
                                    } else {
                                        $newId = $result[0]["id"];
                                        $q = "select `teilnehmer_id` from `ue_unterrichtseinheit_zw_thema` where ue_unterrichtseinheit_id=" . $result[0]["id"];
                                        $ueId =  $result[0]["id"];
                                         try {
                                            $stm = $db_pdo -> query( $q );
                                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                        } catch ( Exception $e ) {
                                            $return -> success = false;
                                            $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                            return $return;   
                                        }
                                        // tn id anfügen
                                        $tmpTNIds = explode( ",", $result[0]["teilnehmer_id"] );
                                        if( !in_array($tnId, $tmpTNIds) ) {
                                            if( count( $tmpTNIds ) === 1) {
                                                $appendTnId = $tnId;
                                            } else {
                                                $appendTnId = $result[0]["teilnehmer_id"] . "," . $tnId;                                                
                                            }
                                            $q= "update ue_unterrichtseinheit_zw_thema set teilnehmer_id='" . $appendTnId . "' where ue_unterrichtseinheit_id =$ueId";
                                            $db_pdo -> query( $q );                                            
                                        }
                                    }

                                    } else {
 
                                    //echo "Die Zeit liegt außerhalb des Bereichs.";
                                }
                                $i += 1;
                            } 

                   $q = "select id from ue_unterrichtseinheit_zw_thema where FIND_IN_SET('$tnId', teilnehmer_id) > 0 And beschreibung='Gruppe " . $group_id . "'"; 
                                    try {
                                        $stm = $db_pdo -> query( $q );
                                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                    } catch ( Exception $e ) {
                                        $return -> success = false;
                                        $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                        return                            print_r( json_encode( $return )); 
;   
                                    }
                   $q = "INSERT INTO `ue_zuweisung_teilnehmer` (`ue_unterrichtseinheit_zw_thema_id`, `teilnehmer_id`, gruppe_id, datum) VALUES (" . $result[0]["id"] . ", $tnId, $group_id, '" . $return -> currentDate->format('Y-m-d') . " " . $a . "')";
                   $db_pdo -> query( $q );
                            $zwId = $db_pdo -> lastInsertId();
                            $r =  $db_pdo -> query( "select lernfortschritt, beherrscht_thema, transferdenken, vorbereitet from mtr_rueckkopplung_teilnehmer where id= " . $_POST["id"] )->fetchAll();
                            $rtn = $db_pdo -> query( "select basiswissen,belastbarkeit, note from mtr_persoenlichkeit where teilnehmer_id = $tnId" )->fetchAll();
/*
                            $q="insert into mtr_leistung (ue_zuweisung_teilnehmer_id,datum,teilnehmer_id,lernfortschritt,beherrscht_thema,transferdenken,basiswissen,vorbereitet,belastbarkeit,note) VALUES 
                                                            ($zwId, '" . $return -> currentDate->format('Y-m-d') . " " . $a . "',$tnId," . $r[0]["lernfortschritt"] . ", " . $r[0]["beherrscht_thema"] . "," . $r[0]["transferdenken"] . "," . $rtn[0]["basiswissen"] . "," . $r[0]["vorbereitet"] . 
                                                            "," . $rtn[0]["belastbarkeit"] . "," . $rtn[0]["note"] ." )";

                            $db_pdo -> query( $q );
*/
                            $db_pdo -> query( "update mtr_rueckkopplung_teilnehmer set ue_zuweisung_teilnehmer_id=$zwId, erfasst_am='" . $return -> currentDate->format('Y-m-d') . " " . $a . "' where id=" . $_POST["id"] );
                            setEmotions( $db_pdo,$tmpTN,$zwId,$return -> currentDate->format('Y-m-d') . " " . $a, $tmpEmotions );
                            $r =  $db_pdo -> query( "select ue_zuweisung_teilnehmer_id, teilnehmer_id, erfasst_am, themenauswahl, methodenvielfalt,individualisierung, aufforderung, materialien, zielgruppen from mtr_rueckkopplung_teilnehmer where id= " . $_POST["id"] )->fetchAll();
                            $return -> t = "INSERT INTO `mtr_didaktik` (`ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `datum`, `themenauswahl`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `materialien`, `zielgruppen`) VALUES (" 
                            . $r[0]["ue_zuweisung_teilnehmer_id"] . ", " . $r[0]["teilnehmer_id"] . ", '" .  $return -> currentDate->format('Y-m-d') . " " . $a . "', " . $r[0]["themenauswahl"] . ", " . $r[0]["methodenvielfalt"] . ", "  . $r[0]["individualisierung"] . ", "
                            . $r[0]["aufforderung"] . ", " . $r[0]["materialien"] . ", "  . $r[0]["materialien"] . ")";
                            
                            $db_pdo -> query("INSERT INTO `mtr_didaktik` (`ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `datum`, `themenauswahl`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `materialien`, `zielgruppen`) VALUES (" 
                            . $r[0]["ue_zuweisung_teilnehmer_id"] . ", " . $r[0]["teilnehmer_id"] . ", '" .  $return -> currentDate->format('Y-m-d') . " " . $a . "', " . $r[0]["themenauswahl"] . ", " . $r[0]["methodenvielfalt"] . ", "  . $r[0]["individualisierung"] . ", "
                            . $r[0]["aufforderung"] . ", " . $r[0]["materialien"] . ", "  . $r[0]["materialien"] . ")");
                             //$return -> q = $test;
                   $emotionen = [];
                    $res = $db_pdo->query("SELECT id AS code, valenz, aktivierung FROM _mtr_emotionen");
                    foreach ($res as $r) {
                        $emotionen[strtolower($r['code'])] = [
                            'v' => (float)$r['valenz'],
                            'a' => (float)$r['aktivierung']
                        ];
                    }

                    $return -> s = $tnId;
  
                    $return -> ueId = $newId;
  /*
                    $return -> q = $q;
                    $return -> rtn = $rtn;
                    $return -> emotionen = $emotionen;
  */
                    print_r( json_encode( $return )); 
    break;
    case "getUeData":
                    $return -> ueId = $_POST["ueId"];
                    $r = $db_pdo -> query("select * from ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id=" .  $_POST["ueId"]) -> fetchAll();
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $r[0]["teilnehmer_id"]) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' order by lernthema") -> fetchAll();

                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option value="' . $r_lernthemen[$i]["value"] . '">' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $r_lernthemen = $option;
                    $db_pdo -> exec( "update ue_unterrichtseinheit_zw_thema set schulform_id= " . $r_tn[0]["KlassentypID"] . " where ue_unterrichtseinheit_id=" .  $_POST["ueId"]);
                    $return -> r = $r;
                    $return -> r_tn = $r_tn;
                    $return -> r_lernthemen = $r_lernthemen;
  /*
                    $return -> rtn = $rtn;
                    $return -> emotionen = $emotionen;
  */
                    print_r( json_encode( $return )); 
    
    break;
    case "getLernthemenData":
        //var_dump( $_POST );
        if( $_POST["ueId"] == "new" ) {
                                $return -> lernthemen = "";
            
        } else {
                    $r = $db_pdo -> query("select * from ue_unterrichtseinheit_zw_thema where id=" .  $_POST["ueId"] . " and teilnehmer_id = "  .  $_POST["tn"]) -> fetchAll();
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $r[0]["teilnehmer_id"]) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' and fach_id=". $_POST["fachId"] . " order by lernthema") -> fetchAll();
                    $return -> ueId = $_POST["ueId"];
                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option>' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $return -> lernthemen = $option;
        }
  /*
                    $return -> rtn = $rtn;
                    $return -> emotionen = $emotionen;
  */
                    print_r( json_encode( $return )); 

    break;
    case "getLernthemenDataNew":
                    $r_ueId = $db_pdo -> query("select id,fach_id from ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id=" .  $_POST["ueId"] . " and teilnehmer_id = "  .  $_POST["tn"]) -> fetchAll();
                    $r = $db_pdo -> query("select * from ue_unterrichtseinheit_zw_thema where id=" .  $r_ueId[0]["id"] . " and teilnehmer_id = "  .  $_POST["tn"]) -> fetchAll();
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $r[0]["teilnehmer_id"]) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' and fach_id=". $_POST["fach_id"] . " order by lernthema") -> fetchAll();
                    $return -> ueId = $_POST["ueId"];
                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option>' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $return -> lernthemen = $_POST["lernthemen"];
                    $return -> ueId = $_POST["ueId"];
                    $return -> Id = $r_ueId[0]["id"];
                    $return -> tn = $_POST["tn"];
                    print_r( json_encode( $return ));     
    break;
    case "getThemenPerFach":
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $_POST["tn"] ) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' and fach_id=". $_POST["fachId"] . " order by lernthema") -> fetchAll();
                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option>' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $return -> lernthemen = $option;
                    if( $_POST["ueId"] === "new" ) {
                        
                    } else {
                        
                    }
                    //$return -> lernthemen = $option;
                    $return -> fachId = $_POST["fachId"];
                    $return -> ueId = $_POST["ueId"];
                    $return -> Id = $_POST["id"];
                    $return -> tn = $_POST["tn"];
                    print_r( json_encode( $return ));     
    
    break;
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
