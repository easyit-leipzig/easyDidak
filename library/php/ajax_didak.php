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
    case "setGroup":
                            $query = "SELECT erfasst_am FROM `mtr_rueckkopplung_teilnehmer` where id=" . $_POST["id"] ;
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
                                 $date = $result[$i]["erfasst_am"];
                                $i += 1;
                            }
                            $diff = 30;
                            $a = new DateTime($date);
                            $b = new DateTime($date);
                            $return -> startdiff = $a->sub(new DateInterval('PT' . $diff . 'M'));
                            $return -> enddiff = $b->add(new DateInterval('PT' . $diff . 'M'));
                           print_r( json_encode( $return )); 
    break;
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
