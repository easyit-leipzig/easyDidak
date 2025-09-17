<?php
$return = new \stdClass();
session_start();
$settings = parse_ini_file( '../../ini/settings.ini', TRUE );
$dns = $settings['database']['type'] . 
            ':host=' . $settings['database']['host'] . 
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
            ';dbname=' . $settings['database']['schema'];
try {
    $db_pdo = new \PDO( $dns, $settings['database']['username'], $settings['database']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
    $db_pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_pdo -> setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false );
    print_r( json_encode( $return ));
}
catch( \PDOException $e ) {
    $return -> command = "connect_error";
    $return -> message = $e->getMessage();
    print_r( json_encode( $return ));
    die;
}

/*
$filename = $_POST["targetFileName"];
if( $_POST["replace"] === "true" && $_POST["oldFileName"] !== "" ) {
$glob = glob( "../documents/cal_ev_appendix_" . $_POST["event_id"] . "*.*" );
    for( $i = 0; $i < count( $glob ); $i++ ) {
        if( file_exists( $glob[$i] ) ) {
            unlink( $glob[$i] );
        }    
    }    
}
$result = move_uploaded_file( $_FILES["file"]['tmp_name'], $_POST["targetPath"] . $_FILES["file"]["name"] );  
*/
$result = move_uploaded_file( $_FILES["file"]['tmp_name'], "../../" . $_POST["targetPath"] . $_FILES["file"]["name"] );  
//$result = rename( "../documents/" . $_FILES["file"]["name"], "../documents/$filename" ); 
$return -> filename = "test";
print_r( json_encode( $return ));       
   
//header("Location: ../../s_and_a.php");
?>
