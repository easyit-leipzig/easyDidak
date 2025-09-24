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
    case "getAVG":
                            /*
                            
                                1    id Primärschlüssel    int(11)            Nein    kein(e)        AUTO_INCREMENT    Bearbeiten Bearbeiten    Löschen Löschen    
    2    ue_zuweisung_teilnehmer_id Index    int(11)            Nein    kein(e)            Bearbeiten Bearbeiten    Löschen Löschen    
    3    datum    datetime            Nein    current_timestamp()            Bearbeiten Bearbeiten    Löschen Löschen    
    4    teilnehmer_id    int(11)            Nein    kein(e)            Bearbeiten Bearbeiten    Löschen Löschen    
    5    lernfortschritt Index    decimal(2,1)            Ja    4.0            Bearbeiten Bearbeiten    Löschen Löschen    
    6    beherrscht_thema Index    decimal(2,1)            Ja    4.0            Bearbeiten Bearbeiten    Löschen Löschen    
    7    transferdenken Index    decimal(2,1)            Ja    4.0            Bearbeiten Bearbeiten    Löschen Löschen    
    8    basiswissen Index    decimal(2,1)            Ja    4.0            Bearbeiten Bearbeiten    Löschen Löschen    
    9    vorbereitet Index    decimal(2,1)            Ja    4.0            Bearbeiten Bearbeiten    Löschen Löschen    
    10    note
    */
                            $query = "SELECT avg(lernfortschritt) as lernfortschritt, avg(beherrscht_thema) as beherrscht_thema, avg(transferdenken) as transferdenken, avg(basiswissen) as basiswissen, 
                                        avg(lernfortschritt) as lernfortschritt, avg(vorbereitet) as vorbereitet, avg(note) as note FROM `mtr_leistung`  where   teilnehmer_id=" . $_POST["tn"];
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            $return->dates = $db_pdo -> query( "select min(datum) as minDate, max(datum) as maxDate, count(id) as anz  FROM `mtr_leistung`  where   teilnehmer_id=" . $_POST["tn"] )->fetchAll();
                            $return -> res = $result;
                                print_r( json_encode( $return )); 
    break;
    case "getAVG":
                            $rows = $db_pdo -> query("select min(datum) as minDate, max(datum) as maxDate, count(id) from mtr_rueckkopplung_teilnehmer where ue_zuweisung_teilnehmer_id = " . $_POST["id"])->fetchAll();
                            print_r( json_encode( $return )); 
    break;
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
