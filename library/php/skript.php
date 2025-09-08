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
    $q = "select id, val_emotions from mtr_rueckkopplung_teilnehmer where val_emotions<>''";
        $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $ids = explode( ",", $r[$i]->val_emotions );
        $k = count( $ids );
        $j = 0;
        while ($j < $k ) {
            $q_result_alt = "select emotion FROM `_mtr_emotionen` where id = " . $ids[$j];      
            $s_alt = $db_pdo -> query( $q );
            $r_alt = $s -> fetchAll( PDO::FETCH_CLASS );
           $j += 1;
        }
        $i += 1;
    }

?>
