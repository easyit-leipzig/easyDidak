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
function sanitize_column_name($str) {
    $str = str_replace(
        ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'],
        ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'],
        $str
    );
    // Nur Buchstaben, Zahlen und Unterstrich erlauben
    return preg_replace('/[^A-Za-z0-9_]/', '', $str);
}
require_once("functions.php"); 
foreach ( $_POST as &$str) {
    //var_dump($str);
    $str = replaceUnwantetChars($str);
}
switch( $_POST["command"]) {
    // start standard functions
    case "setGroup":
                            $query = "SELECT teilnehmer_id, erfasst_am FROM `mtr_rueckkopplung_teilnehmer` where id=" . $_POST["id"] ;
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
                                        $q = "INSERT INTO `ue_unterrichtseinheit_zw_thema` (`ue_unterrichtseinheit_id`, `schulform_id`, `fach_id`, `zieltyp_id`, `lernmethode_id`, `std_lernthema_id`, `thema`, `dauer`, `teilnehmer_id`, `beschreibung`) 
                                                VALUES ($newId, '', '1', '1', 24, '', '', 90, $tnId, 'Gruppe " . $groupId . "')" ;
                                        $db_pdo -> query( $q );
                                        $ueId = $db_pdo -> lastInsertId();
                                    } else {
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
                   $q = "INSERT INTO `ue_zuweisung_teilnehmer` (`ue_unterrichtseinheit_zw_thema_id`, `teilnehmer_id`, datum) VALUES (" . $result[0]["id"] . ", $tnId, '" . $return -> currentDate->format('Y-m-d') . " " . $a . "')";
                   $db_pdo -> query( $q );
                            $zwId = $db_pdo -> lastInsertId();
                            $q = "INSERT INTO `mtr_leistung` (`ue_zuweisung_teilnehmer_id`) VALUES ($zwId)";
                  $db_pdo -> query( $q );
                             $return -> q = $q;
                           $return -> t = $zwId;
 
                            $return -> s = $tnId;
                           print_r( json_encode( $return )); 
    break;
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
