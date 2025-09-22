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
switch( $_POST["command"]) {
    // start standard functions
    case "getLernthema":
                            $query = "SELECT schulform FROM `_std_schulform` where id=" . $_POST["schulformV"] ;
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $l = count( $result );
                            $i = 0;
                            while( $i < $l ) {
                                 $schulform = $result[$i]["schulform"];
                                $i += 1;
                            }
                           
                            $query = "SELECT * FROM `std_lernthema` where schulform = '" . $schulform . "' or schulform = 'frei' AND fach_id=" . $_POST["fachV"];
                                        try {
                                            $stm = $db_pdo -> query( $query );
                                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                        } catch ( Exception $e ) {
                                            $return -> success = false;
                                            $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                            return $return;   
                                        }
                            $l = count( $result );
                            $i = 0;
                            $lernthema = "";
                            while( $i < $l ) {
                                 $lernthema = $lernthema . "<option>" . $result[$i]["lernthema"] . "</option>";
                                $i += 1;
                            }

                            $return->id = $_POST["id"];
                            $return->schulform = $schulform;
                            $return->lernthema = $lernthema;
                            print_r( json_encode( $return ));   
    break;
    case "getLerninhalt":
                            $query = "SELECT schulform FROM `_std_schulform` where id=" . $_POST["schulform"] ;
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $l = count( $result );
                            $i = 0;
                            while( $i < $l ) {
                                 $schulform = $result[$i]["schulform"];
                                $i += 1;
                            }
                            $query = "SELECT id FROM `std_lernthema` where lernthema='" . $_POST["value"] . "'"; //" and schulform='$schulform' or schulform='frei'";
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $l = count( $result );
                            $i = 0;
                            while( $i < $l ) {
                                 $id = $result[$i]["id"];
                                $i += 1;
                            }
                            $query = "SELECT inhalt FROM `_std_lernthema_inhalt` where std_lernthema_id=$id";
                                        try {
                                            $stm = $db_pdo -> query( $query );
                                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                        } catch ( Exception $e ) {
                                            $return -> success = false;
                                            $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                            return $return;   
                                        }
                            $l = count( $result );
                            $i = 0;
                            $lerninhalt = "";
                            while( $i < $l ) {
                                 $lerninhalt = $lerninhalt . "<option>" . $result[$i]["inhalt"] . "</option>";
                                $i += 1;
                            }
                            //$return->test = $_POST["schulform"];
                            $return->lerninhalt = $lerninhalt;
                            $return->id = $_POST["tmpId"];
                            //$return->schulform = $schulform;
                            print_r( json_encode( $return ));   
    break;
    case "setUeTeinehmer":
                            $query = "SELECT teilnehmer_id FROM `ue_unterrichtseinheit_zw_thema` where id = " .  $_POST["id"];
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $l = count( $result );
                            $i = 0;
                            while( $i < $l ) {
                                 $teilnehmer_id = $result[$i]["teilnehmer_id"];
                                $i += 1;
                            }
                            $teilnehmer_id = explode(",", $teilnehmer_id);
                            $q = "delete from ue_zuweisung_teilnehmer where ue_unterrichtseinheit_zw_thema_id = " . $_POST["id"];
                            $db_pdo -> query( $q );
                            $l = count( $teilnehmer_id );
                            $i = 0;
                            while( $i < $l ) {
                                $query = "INSERT INTO `ue_zuweisung_teilnehmer` ( `ue_unterrichtseinheit_zw_thema_id`, `teilnehmer_id`) VALUES (" . $_POST["id"] . ", " . $teilnehmer_id[$i] . ")";
                                $db_pdo -> query( $query );
                                $i += 1;
                            }                            
                            
                            print_r( json_encode( $return ));   
                            
    break;
    case "getAVG":
                            $return -> resZWThemen = $db_pdo -> query( "SELECT * FROM `ue_unterrichtseinheit_zw_thema` where     ue_unterrichtseinheit_id = " . $_POST["id"] )->fetchAll();
                            $return -> resZWTN = $db_pdo -> query( "SELECT teilnehmer_id FROM `ue_zuweisung_teilnehmer` where  ue_unterrichtseinheit_zw_thema_id  = " . $return -> resZWThemen[0]["id"] )->fetchAll();
                            $l = count( $return -> resZWTN );
                            $i = 0;
                            $str = "";
                            while( $i < $l ) {
                                $str .= $return -> resZWTN[$i]["teilnehmer_id"] . ",";
                                $i += 1;
                            }
                            $str = substr($str, 0, -1);
                            $query = "SELECT avg(mitarbeit) as mitarbeit, avg(aufforderung) as aufforderung, avg(absprachen) as absprachen, avg(selbststaendigkeit) as selbststaendigkeit, avg(konzentration) as konzentration, avg(fleiss) as fleiss, avg(lernfortschritt) as lernfortschritt, 
                                        avg(beherrscht_thema) as beherrscht_thema, avg(transferdenken) as transferdenken, avg(vorbereitet) as vorbereitet, avg(themenauswahl) as themenauswahl, avg(materialien) as materialien, avg(methodenvielfalt) as methodenvielfalt, 
                                        avg(individualisierung) as individualisierung, avg(zielgruppen) as zielgruppen from mtr_rueckkopplung_teilnehmer where teilnehmer_id in($str)";
                            try {
                                $stm = $db_pdo -> query( $query );
                                $return -> tn = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $l = count( $return -> tn );
                            $i = 0;
                            while( $i < $l ) {
                                $return -> keys = array_keys($return -> tn[$i]);
                                $m = count( $return -> keys );
                                $j = 0;
                                while( $j < $m ) {
                                    $zufallszahl = (rand() / getrandmax()) - 0.5;  // Normalisiert auf den Bereich -0.5 bis +0.5
                                    $return -> tn[$i][$return -> keys[$j]] = $return -> tn[$i][$return -> keys[$j]] + $zufallszahl;
                                    $j += 1;
                                }
                                $i += 1;
                            }

                            $return -> keys = array_keys($return -> tn[0]);

                            //$zufallszahl = (rand() / getrandmax()) - 0.5;  // Normalisiert auf den Bereich -0.5 bis +0.5

// Neue Zahl berechnen
//$neuerWert = $wertx + $zufallszahl;

                            $return -> datum = $_POST["datum"] . " 00:00:00";
                            print_r( json_encode( $return ));   
                            
    break;
    default:
                            print_r( json_encode( $return )); 
    break;
}
?>
