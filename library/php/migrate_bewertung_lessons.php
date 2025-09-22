<?php
    $ue = $db_pdo -> query( "SELECT * FROM ue_unterrichtseinheit" )->fetchAll();
 
 
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

?>
