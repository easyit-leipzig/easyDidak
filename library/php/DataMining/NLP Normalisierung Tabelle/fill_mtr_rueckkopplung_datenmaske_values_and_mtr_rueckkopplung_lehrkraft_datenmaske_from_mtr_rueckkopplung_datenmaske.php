<?php
require_once("../../functions/tokenize.php");
require_once("../../functions/analyseLastArrayField.php");
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo -> exec("truncate frzk_lexem_datenmaske_lexem_funktionsklasse_weight");
$pdo -> exec("truncate frzk_lexem_mapping_not_recognized");
/* get values */ 
$r_vals = $pdo -> query("select id, id_mtr_rueckkopplung_datenmaske, value from mtr_rueckkopplung_datenmaske_values
                           where length(value)>40 and value not like '%absprachen%' order by id") -> fetchAll(PDO::FETCH_ASSOC);
// iteriere über mtr_rueckkopplung_datenmaske_values */
$l = count( $r_vals );
$i = 0;
while( $i < $l )  {
    $text = tokenize( $r_vals[$i]["value"] );
    $text = checkLastItem( $text );
    $k = count( $text );
    $j = 0;
    $lexem_array = [];
    while( $j < $k )  {
        $r_map = $pdo -> query("select funktionsklasse_id, wortart from frzk_lexem_mapping
                   where lexem='" . $text[$j] . "'") -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $r_map ) == 0 ) {
            $pdo -> exec("INSERT ignore INTO `frzk_lexem_mapping_not_recognized` (`lexem`) 
                        VALUES ('" . $text[$j] . "')");
            $pdo -> exec("UPDATE frzk_lexem_mapping_not_recognized SET anz = anz + 1 WHERE lexem = '" . $text[$j] . "'");
            //$tmp = $pdo->query("select anz from frzk_lexem_mapping_not_recognized where lexem='" . $text[$j] . "'")->fetchAll(PDO::FETCH_ASSOC);
            //$pdo->exec("update frzk_lexem_mapping_not_recognized set anz=" . ($tmp[0]["anz"] + 1)) . " where lexem='" . $text[$j] . "'";
        } else {
            // unterrichtsdaten aus datenmaske holen
            $sql = "select gruppe_id, teilnehmer_id, fach, datum, lehrkraft, thema
                        from mtr_rueckkopplung_datenmaske where thema is not null and 
                        id = " . $r_vals[$i]["id_mtr_rueckkopplung_datenmaske"];
            $r_datenmaske = $pdo -> query( $sql ) -> fetchAll(PDO::FETCH_ASSOC);
            $tmp = explode( ":", $r_datenmaske[0]["thema"] );
            if( count( $tmp )==2 ) {
                $thema = $tmp[0];
                $unterthema = $tmp[1];
            } else {
                $thema = $tmp[0];
                $unterthema = "";
            }
            // hole ue_id
            if( $r_datenmaske[0]["gruppe_id"] == 0 ) {
                $uhrzeit_start = "00:00:00";
                $ue_id = 0;    
            } else {
                $sql = "select uhrzeit_start from ue_gruppen where id = " . $r_datenmaske[0]["gruppe_id"];
                $r_gr = $pdo -> query( $sql ) -> fetchAll( PDO::FETCH_ASSOC );
                $uhrzeit_start =  $r_gr[0]["uhrzeit_start"];
                $sql = "select id from ue_unterrichtseinheit where datum = '" . $r_datenmaske[0]["datum"] . "' 
                        and gruppe_id = ". $r_datenmaske[0]["gruppe_id"] . " and zeit = '" . $r_gr[0]["uhrzeit_start"] . "'";
                $r_ue = $pdo -> query( $sql ) -> fetchAll( PDO::FETCH_ASSOC );
                if( count( $r_ue ) == 0 ) {
                    $ue_id = 0;
                } else {
                    $ue_id = $r_ue[0]["id"];
                }
            }
            // hole wichtungswerte für funktionsklasse
            $sql = "select * from frzk_funktionsklasse_weight where funktionsklasse_id=" . $r_map[0]["funktionsklasse_id"];
            $r_fktKl = $pdo -> query( $sql ) -> fetchAll( PDO::FETCH_ASSOC );
            // insert in frzk_lexem_datenmaske_lexem_funktionsklasse_weight
            /*
            INSERT INTO `frzk_lexem_datenmaske_lexem_funktionsklasse_weight` 
                (`funktionsklasse_id`, `frzk_lexem_id`, `wortart`, `ue_id`, `gruppe_id`, 
                `fach`, `thema`, `unterthema`, `lehrkraft`, `datum_zeit`, `teilnehmer_id`, 
                `kognition`, `sozial`, `affektiv`, `motivation`, `methodik`, `performanz`, 
                `regulation`) VALUES ('2', '3', 'a', '2', '3', 'MAT', 'ww', '11', '11', 
                '2026-02-17 17:30:09.000000', '1', '2.3', '0.000', '0.000', NULL, NULL, NULL, NULL)
            */
            $sql = "INSERT INTO `frzk_lexem_datenmaske_lexem_funktionsklasse_weight`
            (mtr_rueckkopplung_datenmaske_values_id, id_mtr_rueckkopplung_datenmaske, `funktionsklasse_id`, `lexem`, `wortart`, `ue_id`, `gruppe_id`,
            `fach`, `thema`, `unterthema`, `lehrkraft`, `datum_zeit`, `teilnehmer_id`,
            `kognition`, `sozial`, `affektiv`, `motivation`, `methodik`, `performanz`,
            `regulation`) VALUES (
            " . $r_vals[$i]["id"] . ",
            " . $r_vals[$i]["id_mtr_rueckkopplung_datenmaske"] . ",
            " . $r_map[0]["funktionsklasse_id"] . ",
            '" . $text[$j] . "',
            '" . $r_map[0]["wortart"] . "',
            $ue_id,
            '" . $r_datenmaske[0]["gruppe_id"] . "',
            '" . $r_datenmaske[0]["fach"] . "',
            '" . $thema . "',
            '" . $unterthema . "',
            '" . $r_datenmaske[0]["lehrkraft"] . "',
            '" . $r_datenmaske[0]["datum"] . " " . $uhrzeit_start . "',
            '" . $r_datenmaske[0]["teilnehmer_id"] . "',
             '" . $r_fktKl[0]["kognition"] . "',
            '" . $r_fktKl[0]["sozial"] . "',
            '" . $r_fktKl[0]["affektiv"] . "',
            '" . $r_fktKl[0]["motivation"] . "',
            '" . $r_fktKl[0]["methodik"] . "',
            '" . $r_fktKl[0]["performanz"] . "',
            '" . $r_fktKl[0]["regulation"] . "');   
            ";
            $pdo -> exec( $sql );
        }
        $j += 1;
    }
    
    $i += 1;
}
?>             
