<?php
    $settings = parse_ini_file('../../ini/settings.ini', TRUE);
    $dns = $settings['database']['type'] . 
                ':host=' . $settings['database']['host'] . 
                ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
                ';dbname='. $settings['database']['schema'];
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
    $q = "SELECT id, ue_unterrichtseinheit_id, teilnehmer_id FROM ue_unterrichtseinheit_zw_thema";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    var_dump($r);
    while ($i < $l ) {
        // code...
        $ids = explode( ",", $r[$i]->teilnehmer_id );
        $k = count( $ids );
        $j = 0;
        while ($j < $k ) {
            $q_result_alt = "INSERT INTO `ue_zuweisung_teilnehmer` (`ue_zuweisung_lernthema_id`, `teinehmer_id`) VALUES (" . $r[$i]->id . ", " . $ids[$j] . ")";
            echo $q_result_alt;      
            if( $ids[$j] <> "" ) $db_pdo -> query( $q_result_alt );
           $j += 1;
        }
        $i += 1;
    }

?>
