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
//$_POST["command"] = "fillFrzkWertungMapping";
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
    case "saveWebSQL":
                    $day = date("d") . "_" . date("m") . "_" . date("Y");
                    $fName = "usr_web411_2_" . $day . "_original.sql";
                    copy( 'C:/Users/thiel/Downloads/usr_web411_2.sql', '../../' . $fName );
                    print_r( json_encode( $return ));     
    break;
    case "rewriteSQL":
    copy( 'C:/Users/thiel/Downloads/usr_web411_2.sql', '../../icas.sql' );
                    $file = file_get_contents('../../icas.sql');
                    if( $file[0] === "-" ) {
                        $file = str_replace('`web411`', '`root`', $file );
                        $file = str_replace('Datenbank: `usr_web411_2`', 'Datenbank: `icas`', $file );
                        $file = "drop database icas;
create database icas;
use icas;
" . $file;
                    $return -> res = file_put_contents('../../icas.sql', $file);
                    }
                    print_r( json_encode( $return ));     
    break;
    case "rewriteWebSQL":
                    $day = date("d") . "_" . date("m") . "_" . date("Y");                    
                    $file = file_get_contents("D:/xampp/htdocs/easyDidak/usr_web411_2_" . $day . "_original.sql");
                        $file = str_replace('`root`', '`web411`', $file );
                        $file = str_replace('Datenbank: `icas`', 'Datenbank: `usr_web411_2`', $file );
                        $file = str_replace('drop database icas;
create database icas;
use icas;
', '', $file );
                        $file = "SET FOREIGN_KEY_CHECKS = 0;
drop table _frzk_hubs;
drop table _mtr_datenmaske_values_wertung;
drop table _mtr_definition_lernmethode;
drop table _mtr_definition_zieltyp;
drop table _mtr_einrichtung;
drop table _mtr_emotionen;
drop table _mtr_persoenlichkeitsmerkmal_definition;
drop table _mtr_soziale_beziehung_type;
drop table _mtr_soziale_beziehung_werte;
drop table _mtr_unterrichtsbewertung;
drop table _std_lernthema_inhalt;
drop table _std_lernthema_quelle;
drop table _std_schulform;
drop table _ue_fach;
drop table frzk_group_hubs;
drop table frzk_group_interdependenz;
drop table frzk_group_loops;
drop table frzk_group_operatoren;
drop table frzk_group_reflexion;
drop table frzk_group_semantische_dichte;
drop table frzk_group_transitions;
drop table frzk_hubs;
drop table frzk_interdependenz;
drop table frzk_loops;
drop table frzk_operatoren;
drop table frzk_reflexion;
drop table frzk_semantische_dichte;
drop table frzk_transitions;
drop table frzk_wertung_mapping;
drop table mtr_didaktik;
drop table mtr_emotions;
drop table mtr_ethik;
drop table mtr_leistung;
drop table mtr_persoenlichkeit;
drop table mtr_rueckkopplung_datenmaske;
drop table mtr_rueckkopplung_datenmaske_values;
drop table mtr_rueckkopplung_lehrkraft_lesson;
drop table mtr_rueckkopplung_lehrkraft_tn;
drop table mtr_rueckkopplung_teilnehmer;
drop table mtr_sozial;
drop table mtr_soziale_beziehungen;
drop table rooms;
drop table session_students;
drop table sessions;
drop table std_lehrkraft;
drop table std_lernthema;
drop table std_teilnehmer;
drop table students;
drop table subjects;
drop table tmp_teilnehmer;
drop table tmp_unterrichtseinheiten;
drop table ue_gruppen;
drop table ue_thema_zu_ue;
drop table ue_unterrichtseinheit;
drop table ue_unterrichtseinheit_zw_thema;
drop table ue_zuweisung_teilnehmer;
drop table verhaltens_mapping;
SET FOREIGN_KEY_CHECKS = 1;" . $file;
                    //unlink( '../../usr_web411_2.sql' );
                    $return -> res = file_put_contents("../../usr_web411_2_" . $day . "_readyForImport.sql", $file );
                    print_r( json_encode( $return ));     
    break;
    
    case "copySQL":
                    if( file_exists( 'd:\xampp\htdocs\easyDidak\icas.sql' )) unlink( 'd:\xampp\htdocs\easyDidak\icas.sql' );
                    if( $_POST["withDrop"]) {
                        $file = file_get_contents('C:\Users\thiel\Downloads\icas.sql');
                            if( $file[0] === "-" ) {
                                $file = "drop database icas;
create database icas;
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
    case "transferDatenmaskeValuesToFrzkWertungMapping":
                    $db_pdo -> exec( "truncate frzk_wertung_mapping");
                    $db_pdo -> exec( "INSERT INTO frzk_wertung_mapping
  (id, value, orig_note, wichtung, emotional, affektiv, kognitiv, sozial, leistung,
   valenz_avg, aktivierung_avg, last_update, dominanter_operator, frzk_vector, bemerkung)
SELECT
  m.id,
  m.value,
  CAST(m.note AS DECIMAL(6,4)),
  CAST(m.wichtung AS SIGNED),
  CAST(m.emotional AS DECIMAL(6,4)),
  CAST(m.affektiv AS DECIMAL(6,4)),
  CAST(m.kognitiv AS DECIMAL(6,4)),
  CAST(m.sozial AS DECIMAL(6,4)),
  CAST(m.leistung AS DECIMAL(6,4)),
  CAST(m.valenz_avg AS DECIMAL(7,6)),
  CAST(m.aktivierung_avg AS DECIMAL(7,6)),
  m.last_update,

  /* dominanter_operator: größte der Komponenten */
  CASE
    WHEN COALESCE(m.kognitiv,0) >= GREATEST(COALESCE(m.affektiv,0),COALESCE(m.sozial,0),COALESCE(m.emotional,0),COALESCE(m.leistung,0)) THEN 'kognitiv'
    WHEN COALESCE(m.affektiv,0) >= GREATEST(COALESCE(m.kognitiv,0),COALESCE(m.sozial,0),COALESCE(m.emotional,0),COALESCE(m.leistung,0)) THEN 'affektiv'
    WHEN COALESCE(m.sozial,0) >= GREATEST(COALESCE(m.kognitiv,0),COALESCE(m.affektiv,0),COALESCE(m.emotional,0),COALESCE(m.leistung,0)) THEN 'sozial'
    WHEN COALESCE(m.emotional,0) >= GREATEST(COALESCE(m.kognitiv,0),COALESCE(m.affektiv,0),COALESCE(m.sozial,0),COALESCE(m.leistung,0)) THEN 'emotional'
    WHEN COALESCE(m.leistung,0) >= GREATEST(COALESCE(m.kognitiv,0),COALESCE(m.affektiv,0),COALESCE(m.sozial,0),COALESCE(m.emotional,0)) THEN 'leistung'
    ELSE 'neutral'
  END AS dominanter_operator,

  JSON_OBJECT(
    'emotional', COALESCE(m.emotional,0),
    'affektiv',  COALESCE(m.affektiv,0),
    'kognitiv',  COALESCE(m.kognitiv,0),
    'sozial',    COALESCE(m.sozial,0),
    'leistung',  COALESCE(m.leistung,0),
    'gesamt_index', ((COALESCE(m.emotional,0)+COALESCE(m.affektiv,0)+COALESCE(m.kognitiv,0)+COALESCE(m.sozial,0)+COALESCE(m.leistung,0))/5)
  ) AS frzk_vector,

  CONCAT(
    CASE WHEN COALESCE(m.valenz_avg,0) < 0 THEN 'neg. Valenz; ' ELSE '' END,
    CASE WHEN COALESCE(m.aktivierung_avg,0) > 0.6 THEN 'hohe Aktivierung; ' ELSE '' END,
    'Quelle: _mtr_datenmaske_values_wertung'
  ) AS bemerkung

FROM `_mtr_datenmaske_values_wertung` m
ON DUPLICATE KEY UPDATE
  value = VALUES(value),
  orig_note = VALUES(orig_note),
  wichtung = VALUES(wichtung),
  emotional = VALUES(emotional),
  affektiv = VALUES(affektiv),
  kognitiv = VALUES(kognitiv),
  sozial = VALUES(sozial),
  leistung = VALUES(leistung),
  valenz_avg = VALUES(valenz_avg),
  aktivierung_avg = VALUES(aktivierung_avg),
  last_update = VALUES(last_update),
  dominanter_operator = VALUES(dominanter_operator),
  frzk_vector = VALUES(frzk_vector),
  bemerkung = VALUES(bemerkung);
    
    
");
                    
    
    break;
    
    default:
                            print_r( json_encode( $startdiff )); 
    break;
}
?>
