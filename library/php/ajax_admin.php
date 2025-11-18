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
/* mock $_POST */
//$_POST["command"] = "copyWebSQL";
/**/
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
switch( $_POST["command"]) {
    // start standard functions
    case "callProcedure":    
                    $return -> res =$db_pdo -> exec( "call updateGruppen");
                    print_r( json_encode( $return ));     
    break;
    case "rewriteSQL":
                    $file = file_get_contents('../../icas.sql');
                    if( $file[0] === "-" ) {
                        $file = "drop database icas;
create databse icas;
use icas;
" . $file;
                    $return -> res = file_put_contents('../../icas.sql', $file);
                    }
                    print_r( json_encode( $return ));     
    break;
    case "copySQL":
                    unlink( 'd:\xampp\htdocs\easyDidak\icas.sql' );
                    if( $_POST["withDrop"]) {
                        $file = file_get_contents('C:\Users\thiel\Downloads\icas.sql');
                            if( $file[0] === "-" ) {
                                $file = "drop database icas;
                                create databse icas;
                                use icas;
                                " . $file;
                            $return -> res = file_put_contents('C:\Users\thiel\Downloads\icas.sql', $file);
                        }
                        
                    }
                    $return -> file = copy('C:\Users\thiel\Downloads\icas.sql', 'd:\xampp\htdocs\easyDidak\icas.sql');
                    print_r( json_encode( $return ));     
    break;
    case "copyWebSQL":
                    unlink( 'd:\xampp\htdocs\easyDidak\usr_web411_2.sql' );
                    $return -> file = copy('C:\Users\thiel\Downloads\usr_web411_2.sql', 'd:\xampp\htdocs\easyDidak\usr_web411_2.sql');
                    print_r( json_encode( $return ));     
    break;
    case "migrateDatenmaske":
                    ob_start();
                    require_once('migrate_datenmaske_values_wichtungswerte.php');
                    $return -> res = ob_get_clean();
                    print_r( json_encode( $return )); 
    break;
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
