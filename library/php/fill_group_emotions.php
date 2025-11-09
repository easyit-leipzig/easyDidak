<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ERROR);

$pdo = new PDO("mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  $sql="select ue_id from frzk_group_semantische_dichte";
$rows = $pdo->query($sql)->fetchAll();

foreach ($rows as $r) {
    $sql = "SELECT frzk_group_semantische_dichte.* FROM frzk_group_semantische_dichte order by id";
    $rows_tn = $pdo->query($sql)->fetchAll();
    $l = count( $rows_tn );
    $i = 0;
    while( $i < $l ) {
        $sql_sd_tn = "select count(id) as anz_tn, avg(x_kognition) as x_kognition, avg(y_sozial) as y_sozial, avg(z_affektiv) as z_affektiv from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
        $rows_sd_tn = $pdo->query($sql_sd_tn)->fetchAll();
        //$pdo->exec("update frzk_tmp_group_semantische_dichte set anz_tn=" . $rows_sd_tn[0]["anz_tn"] . ", x_kognition=" . $rows_sd_tn[0]["x_kognition"] . ", y_sozial=" . $rows_sd_tn[0]["y_sozial"] . ", z_affektiv=" . $rows_sd_tn[0]["z_affektiv"] . "  where id=" . $rows_tn[$i]["id"]);
        $tnIds = "";
        /*
        foreach ($rows_sd_tn as $sd_tn) {
            $tnIds .= $sd_tn["teilnehmer_id"] . ",";
        }
        $tnIds = substr($tnIds, 0, -1);
        */
        $sql_sd_em = "select emotions from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
        $rows_sd_em = $pdo->query($sql_sd_em)->fetchAll();
        $tnEmotions = "";
        foreach ($rows_sd_em as $sd_em) {
            $tnEmotions .= $sd_em["emotions"] . ",";
        }
        $tnEmotions = substr($tnEmotions, 0, -1);
        $tnEmotionsArr = explode( ",", $tnEmotions );
            $stmt = $pdo->query("SELECT id, emotion, valenz, aktivierung FROM _mtr_emotionen");
            $emotionMatrix = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $emotionMatrix[(int)$row['id']] = [
                    'emotion' => $row['emotion'],
                    'valenz' => (float)$row['valenz'],
                    'aktivierung' => (float)$row['aktivierung'],
                ];
            }

            // --- 2) Schwellenwerte für „wesentliche“ Emotionen ---
            $minValenz = 0.7;
            $minAktivierung = 0.5;
            $datensaetze[$rows_tn["id"]]['emotionen']=$tnEmotionsArr;
            // --- 3) JSON-Ausgabe vorbereiten ---
            $ergebnisse = [];

            foreach ($datensaetze as $datensatz) {
                $alle = $datensatz['emotionen'];
                $anzahl = array_count_values($alle);

                $wesentliche = [];

                foreach ($anzahl as $id => $count) {
                    if (!isset($emotionMatrix[$id])) continue;
                    $val = $emotionMatrix[$id]['valenz'];
                    $act = $emotionMatrix[$id]['aktivierung'];

                    // Bedingung: mehrfach & hohe Gewichtung
                    if (/*$count > 1 && */$val >= $minValenz && $act >= $minAktivierung) {
                        $wesentliche[] = [
                            'id' => $id,
                            'emotion' => $emotionMatrix[$id]['emotion'],
                            'anzahl' => $count,
                            'valenz' => $val,
                            'aktivierung' => $act,
                            'score' => ($val + $act) / 2
                        ];
                    }
                }

                $ergebnisse[] = [
                    //'datensatz_id' => $rows_tn[$i]["id"],
                    //'gruppe_id' => $datensatz['gruppe_id'],
                    'alle_emotionen' => $alle,
                    'anzahl_emotionen' => $anzahl,
                    'wesentliche_emotionen' => $wesentliche
                ];
            }
            $js = json_encode( $ergebnisse );
            $pdo->exec("update frzk_group_semantische_dichte set emotions ='" . json_encode( $ergebnisse ) . "' where id=" . $rows_tn[$i]["id"]);
        $i += 1;
    }
    if( $tnIds != "") {
    }
}

echo "✅ Aggregation abgeschlossen: {$written} Datensätze aktualisiert.\n";
echo "📄 JSON exportiert: frzk_tmp_group_semantische_dichte.json\n";
// --------------------------------------------------------------------------
// 6️⃣ JSON Exporte
// --------------------------------------------------------------------------
foreach(["frzk_group_semantische_dichte","frzk_group_transitions",
         "frzk_group_reflexion","frzk_group_loops"] as $t){
 $rows=$pdo->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC);
 file_put_contents(__DIR__."/$t.json",json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo "📄 Exportiert: $t (" . count($rows) . " Einträge)\n";
}

echo "🏁 Fertig: Alle Gruppendynamiken (Semantik + Transition + Reflexion + Loops) berechnet.\n";

?>
