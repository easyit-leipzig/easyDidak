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
                $db_pdo->query( "insert into mtr_emotions (teilnehmer_id, datum, ue_zuweisung_teilnehmer_id ) values ($tmpTN,'$datum',$zwId)");
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
            $db_pdo->query( "insert into mtr_emotions ($str_fields teilnehmer_id, datum, ue_zuweisung_teilnehmer_id ) values($str_values$tmpTN,'$datum',$zwId)");
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
function checkZuwTn($db_pdo, $thId, $datumZeit, $gruppeId, $tnId ) {
    $return = new \stdClass();
    $rst = $db_pdo -> query("SELECT id FROM `ue_zuweisung_teilnehmer` WHERE ue_unterrichtseinheit_zw_thema_id=$thId 
                            and teilnehmer_id= $tnId") -> fetchAll();
    if( count( $rst ) == 0 ) {
        $db_pdo -> exec("INSERT INTO `ue_zuweisung_teilnehmer` ( `ue_unterrichtseinheit_zw_thema_id`, `datum`, `gruppe_id`, `teilnehmer_id`)
                             VALUES (  $thId, '$datumZeit', $gruppeId, $tnId)");
        $return -> zuwTn = $db_pdo -> lastInsertId();
    } else {
        $return -> zuwTn = $rst[0]["id"];
    }
    return $return;
}
function checkThema($db_pdo, $ueId, $gruppeId, $datumZeit, $tnId ) {
    $return = new \stdClass();
    $rst = $db_pdo -> query("select id, teilnehmer_id from ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id = $ueId and beschreibung ='Gruppe " . $gruppeId ."'") -> fetchAll();
    if( count( $rst ) == 0 ) {
        $db_pdo -> exec("INSERT INTO `ue_unterrichtseinheit_zw_thema` ( `ue_unterrichtseinheit_id`, `datum`, `beschreibung`) 
                VALUES ( $ueId, '" . $datumZeit . "', 'Gruppe $gruppeId')");
    }
    $rst = $db_pdo -> query("select id, teilnehmer_id from ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id = $ueId and beschreibung ='Gruppe " . $gruppeId ."'") -> fetchAll();
    if( $rst[0]["teilnehmer_id"] !="" ) $tmp = explode( ",", $rst[0]["teilnehmer_id"]);
    $tmp[] = $tnId;
    $tmp = array_unique( $tmp );
    $tmp = join( ",", $tmp );
    $db_pdo -> exec("UPDATE `ue_unterrichtseinheit_zw_thema` SET `teilnehmer_id` = '$tmp' 
            WHERE `ue_unterrichtseinheit_zw_thema`.`id` = " . $rst[0]["id"]);
    $return -> themaId = $rst[0]["id"];
    $return ->ueThId = checkZuwTn( $db_pdo, $return -> themaId, $datumZeit, $gruppeId, $tnId) -> zuwTn;
    return $return;
}
function checkUe($db_pdo , $gruppeId, $startzeit, $datum, $tnId) {
    $return = new \stdClass();
    $currentDate = new \DateTime();
    $rst= $db_pdo -> query("select id from ue_unterrichtseinheit where datum = '" . $datum . "' and zeit = '" . $startzeit . "' and  gruppe_id=$gruppeId")->fetchAll(); 
    if( count( $rst ) == 0 ) {
        $db_pdo -> exec( "INSERT INTO `ue_unterrichtseinheit` (`gruppe_id`, `datum`, `zeit`, `dauer`, `beschreibung`) 
        VALUES ( $gruppeId, '" . $datum . "', '" . $startzeit . "', '90', '');");
        $return -> ueId = $db_pdo -> lastInsertId();
    } else {
        $return -> ueId = $rst[0]["id"];
    }
    $return -> result = checkThema($db_pdo, $return -> ueId, $gruppeId, $datum . " " . $startzeit, $tnId);
    return $return;
    
}
function setMtrTeilnehmer($db_pdo, $mtrId, $tnId, $erfasstAm ) {
    $return = new \stdClass();
    $currentDate = new \DateTime();
    $diff = 30;
    $startdiff = $currentDate->modify("-" . $diff . " minutes");
    $startdiff = $currentDate->format("Y-m-d H:i:s");
    $currentDate->modify("+" . (2 * $diff) . " minutes");
    $enddiff = $currentDate ->format("Y-m-d H:i:s");

    $return -> wochentag = $currentDate->format('N')+1;
    $query = "select id, uhrzeit_start FROM `ue_gruppen` where day_number=" . $return -> wochentag . " and uhrzeit_ende between '" .  $startdiff . "' and '" . $enddiff . "'";
    try {
        $stm = $db_pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
    } catch ( Exception $e ) {
        $return -> success = false;
        $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
        return $return;   
    }
    $return -> gruppenId = $result[0]["id"];
    $return -> datum = $currentDate ->format("Y-m-d");  
    $return -> starzeit = $result[0]["uhrzeit_start"];  
    $return -> result = checkUe( $db_pdo, $return -> gruppenId, $return -> starzeit, $return -> datum, $tnId );
    return $return;
}
/* mock $_POST */
/**/
switch( $_POST["command"]) {
    // start standard functions
    case "setGroup":
                            /* prüfe, ob leerer Datensatz*/
                            /*
                            SELECT id FROM `mtr_rueckkopplung_teilnehmer` WHERE
COALESCE(mitarbeit,0) = 0 AND
COALESCE(absprachen,0) = 0 AND
COALESCE(selbststaendigkeit,0) = 0 AND
COALESCE(konzentration,0) = 0 AND
COALESCE(fleiss,0) = 0 AND
COALESCE(lernfortschritt,0) = 0 AND
COALESCE(beherrscht_thema,0) = 0 AND
COALESCE(transferdenken,0) = 0 AND
COALESCE(basiswissen,0) = 0 AND
COALESCE(vorbereitet,0) = 0 AND
COALESCE(themenauswahl,0) = 0 AND
COALESCE(materialien,0) = 0 AND
COALESCE(methodenvielfalt,0) = 0 AND
COALESCE(individualisierung,0) = 0 AND
COALESCE(aufforderung,0) = 0 AND
COALESCE(zielgruppen,0) = 0 AND
(emotions = '' OR emotions IS NULL) AND
(bemerkungen = '' OR bemerkungen IS NULL) AND id=" . $_POST["id"];
*/
$r = $db_pdo->query("SELECT id FROM `mtr_rueckkopplung_teilnehmer` WHERE
COALESCE(mitarbeit,0) = 0 AND
COALESCE(absprachen,0) = 0 AND
COALESCE(selbststaendigkeit,0) = 0 AND
COALESCE(konzentration,0) = 0 AND
COALESCE(fleiss,0) = 0 AND
COALESCE(lernfortschritt,0) = 0 AND
COALESCE(beherrscht_thema,0) = 0 AND
COALESCE(transferdenken,0) = 0 AND
COALESCE(basiswissen,0) = 0 AND
COALESCE(vorbereitet,0) = 0 AND
COALESCE(themenauswahl,0) = 0 AND
COALESCE(materialien,0) = 0 AND
COALESCE(methodenvielfalt,0) = 0 AND
COALESCE(individualisierung,0) = 0 AND
COALESCE(aufforderung,0) = 0 AND
COALESCE(zielgruppen,0) = 0 AND
(emotions = '' OR emotions IS NULL) AND
(bemerkungen = '' OR bemerkungen IS NULL) AND id=" . $_POST["id"])->fetchAll();
if( count( $r) == 1 ) {
    $db_pdo -> exec("DELETE FROM mtr_rueckkopplung_teilnehmer where id=" . $r[0]["id"] );
    $return -> message = "empty record";
    print_r( json_encode( $return )); 
    break;
}

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
                            $tmpEmotions = $result[0]["emotions"];
                            $result = setMtrTeilnehmer( $db_pdo, $_POST["id"], $tnId, $date);
                            $db_pdo -> exec( "UPDATE `mtr_rueckkopplung_teilnehmer` SET `ue_id` = " . $result->result->ueId . ", `ue_zuweisung_teilnehmer_id` = " . $result->result->result->ueThId. ", `gruppe_id` = " . $result->gruppenId . ", `erfasst_am` = '" .  $result->datum . " " . $result->starzeit  . "' WHERE `mtr_rueckkopplung_teilnehmer`.`id` = " . $_POST["id"] );
                            setEmotions( $db_pdo, $tnId, $result->result->result->ueThId, $result->datum . " " . $result->starzeit, $tmpEmotions );

                            $r =  $db_pdo -> query( "select lernfortschritt, beherrscht_thema, transferdenken, vorbereitet from mtr_rueckkopplung_teilnehmer where id= " . $_POST["id"] )->fetchAll();
                            $rtn = $db_pdo -> query( "select basiswissen,belastbarkeit, note from mtr_persoenlichkeit where teilnehmer_id = $tnId" )->fetchAll();
/*
                            $q="insert into mtr_leistung (ue_zuweisung_teilnehmer_id,datum,teilnehmer_id,lernfortschritt,beherrscht_thema,transferdenken,basiswissen,vorbereitet,belastbarkeit,note) VALUES 
                                                            ($zwId, '" . $return -> currentDate->format('Y-m-d') . " " . $a . "',$tnId," . $r[0]["lernfortschritt"] . ", " . $r[0]["beherrscht_thema"] . "," . $r[0]["transferdenken"] . "," . $rtn[0]["basiswissen"] . "," . $r[0]["vorbereitet"] . 
                                                            "," . $rtn[0]["belastbarkeit"] . "," . $rtn[0]["note"] ." )";

                            $db_pdo -> query( $q );
*/
                            $r =  $db_pdo -> query( "select ue_zuweisung_teilnehmer_id, teilnehmer_id, erfasst_am, themenauswahl, methodenvielfalt,individualisierung, aufforderung, materialien, zielgruppen from mtr_rueckkopplung_teilnehmer where id= " . $_POST["id"] )->fetchAll();
                            $return -> t = "INSERT INTO `mtr_didaktik` (`ue_zuweisung_teilnehmer_id`, `teilnehmer_id`, `datum`, `themenauswahl`, `methodenvielfalt`, `individualisierung`, `aufforderung`, `materialien`, `zielgruppen`) VALUES (" 
                            . $r[0]["ue_zuweisung_teilnehmer_id"] . ", " . $r[0]["teilnehmer_id"] . ", '" .  $result->datum . " " . $result->starzeit  . "', " . $r[0]["themenauswahl"] . ", " . $r[0]["methodenvielfalt"] . ", "  . $r[0]["individualisierung"] . ", "
                            . $r[0]["aufforderung"] . ", " . $r[0]["materialien"] . ")";
                            
                             //$return -> q = $test;
                   $emotionen = [];
                    $res = $db_pdo->query("SELECT id AS code, valenz, aktivierung FROM _mtr_emotionen");
                    foreach ($res as $r) {
                        $emotionen[strtolower($r['code'])] = [
                            'v' => (float)$r['valenz'],
                            'a' => (float)$r['aktivierung']
                        ];
                    }

                    $return -> tnId = $tnId;
  
                    $return -> ueId = $result->result->ueId;
                    $r = $db_pdo -> query("select *, concat(datum, ' ', zeit) as erfasst from ue_unterrichtseinheit where id=" .  $return -> ueId ) -> fetchAll();
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $return -> tnId) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' and fach_id=1 order by lernthema") -> fetchAll();
                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option value="' . $r_lernthemen[$i]["value"] . '">' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    //$db_pdo -> exec( "update ue_unterrichtseinheit_zw_thema set schulform_id= " . $r_tn[0]["KlassentypID"] . " where ue_unterrichtseinheit_id=" .  $_POST["ueId"] . " and teilnehmer_id = " . $r[0]["teilnehmer_id"]);
                    $return -> tn = $r_tn;
                    $return -> lernthemen = $option;
                    $return -> currentDate = $result->datum . " " . $result->starzeit;
 /*
                    $return -> q = $q;
                    $return -> rtn = $rtn;
                    $return -> emotionen = $emotionen;
  */
                    print_r( json_encode( $return )); 
    break;
    case "getUeData":
                    $return -> ueId = $_POST["ueId"];
                    $r_date = $db_pdo -> query("select concat(datum, ' ', zeit) as date from ue_unterrichtseinheit where id=" .  $_POST["ueId"]) -> fetchAll();
                    $return -> datum = $r_date[0]["date"];
                    $r = $db_pdo -> query("select * from ue_unterrichtseinheit_zw_thema where ue_unterrichtseinheit_id=" .  $_POST["ueId"]) -> fetchAll();
                    $r_tn = $db_pdo -> query("select std_teilnehmer.*, _std_schulform.schulform as schulform from std_teilnehmer, _std_schulform where std_teilnehmer.KlassentypID = _std_schulform.id and std_teilnehmer.id=" .  $r[0]["teilnehmer_id"]) -> fetchAll();
                    $r_lernthemen = $db_pdo -> query("select id as value, lernthema as text from std_lernthema where schulform='" .  $r_tn[0]["schulform"] . "' and fach_id=1 order by lernthema") -> fetchAll();

                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option value="' . $r_lernthemen[$i]["value"] . '">' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $r_lernthemen = $option;
                    //$db_pdo -> exec( "update ue_unterrichtseinheit_zw_thema set schulform_id= " . $r_tn[0]["KlassentypID"] . " where ue_unterrichtseinheit_id=" .  $_POST["ueId"] . " and teilnehmer_id = " . $r[0]["teilnehmer_id"]);
                    $return -> r = $r;
                    $return -> r_tn = $r_tn;
                    $return -> lernthemen = $r_lernthemen;
  /*
                    $return -> rtn = $rtn;
                    $return -> emotionen = $emotionen;
  */
                    print_r( json_encode( $return )); 
    
    break;
    case "changeFach":
                    $r_tn = $db_pdo -> query("select * from std_teilnehmer where id=" . $_POST["tn"] ) -> fetchAll();
                    $return -> ueId = $_POST["ueID"];
                    print_r( json_encode( $return )); 
    
    break;
    case "getThemenFromLernfeld":
                    $return -> thema = $_POST["thema"];
                    $return -> ueId = $_POST["ueId"];
                    $return -> Id = $_POST["Id"];
                    $r_lernthemen = $db_pdo -> query("select _std_lernthema_inhalt.id as value, _std_lernthema_inhalt.inhalt as text from _std_lernthema_inhalt, std_lernthema where std_lernthema.id=_std_lernthema_inhalt.std_lernthema_id and std_lernthema.lernthema='" . $_POST["value"] . "' order by inhalt;") -> fetchAll();
                                                                                                                                                                             
                    $l = count( $r_lernthemen );
                    $i = 0;
                    $option = "";
                    while ($i < $l ) {
                        // code...
                        $option .= '<option value="' . $r_lernthemen[$i]["value"] . '">' . $r_lernthemen[$i]["text"] . '</option>';
                        $i += 1;
                    }
                    $r_lernthemen = $option;
                    print_r( json_encode( $return ));                     
    break;
    default:
                            print_r( json_encode( $return )); 
    break;
}
?>
