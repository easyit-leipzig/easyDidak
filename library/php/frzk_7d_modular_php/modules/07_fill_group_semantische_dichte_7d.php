<?php
function frzk7d_fill_group_semantische_dichte(PDO $pdo, array $dimensions): void {
    $rows=$pdo->query("SELECT * FROM frzk_semantische_dichte_teilnehmer_7d ORDER BY gruppe_id, DATE(zeitpunkt), zeitpunkt")->fetchAll();
    $g=[];
    foreach($rows as $r){
        $key=(int)$r['gruppe_id'].'|'.substr($r['zeitpunkt'],0,10);
        if(!isset($g[$key])){
            $g[$key]=[
                'gruppe_id'=>(int)$r['gruppe_id'],'date'=>substr($r['zeitpunkt'],0,10),'n'=>0,
                'sum'=>array_fill_keys($dimensions,0.0),'skala'=>array_fill_keys($dimensions,0.0),'emotion'=>array_fill_keys($dimensions,0.0),
                'd'=>0.0,'val'=>0.0,'akt'=>0.0,'emoN'=>0
            ];
        }
        $g[$key]['n']++;
        foreach($dimensions as $d){
            $g[$key]['sum'][$d]+=(float)$r['sum_'.$d];
            $g[$key]['skala'][$d]+=(float)($r['skala_'.$d] ?? 0.0);
            $g[$key]['emotion'][$d]+=(float)($r['emotion_vector_'.$d] ?? 0.0);
        }
        $g[$key]['d']+=(float)$r['d_semantisch'];
        if($r['emotion_valenz']!==null){$g[$key]['val']+=(float)$r['emotion_valenz'];$g[$key]['akt']+=(float)$r['emotion_aktivierung'];$g[$key]['emoN']++;}
    }
    ksort($g);
    $ins=$pdo->prepare("INSERT INTO frzk_group_semantische_dichte_7d
        (gruppe_id,zeitpunkt,anz_tn,
         mean_kognition,mean_sozial,mean_affektiv,mean_motivation,mean_methodik,mean_performanz,mean_regulation,
         d_semantisch_mean,dominante_dimension,dominante_dimension_wert,polaritaet_gesamt,gruppen_drift_norm,gruppen_stabilitaet,gruppen_transition_marker,
         mean_emotion_valenz,mean_emotion_aktivierung,emotion_n,
         mean_emotion_vector_kognition,mean_emotion_vector_sozial,mean_emotion_vector_affektiv,mean_emotion_vector_motivation,mean_emotion_vector_methodik,mean_emotion_vector_performanz,mean_emotion_vector_regulation,
         mean_skala_kognition,mean_skala_sozial,mean_skala_affektiv,mean_skala_motivation,mean_skala_methodik,mean_skala_performanz,mean_skala_regulation,
         bemerkung)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $prev=[]; $json=[]; $c=0;
    foreach($g as $row){
        $mean=[]; $meanSkala=[]; $meanEmotion=[];
        foreach($dimensions as $d){
            $mean[$d]=$row['sum'][$d]/max(1,$row['n']);
            $meanSkala[$d]=$row['skala'][$d]/max(1,$row['n']);
            $meanEmotion[$d]=$row['emotion'][$d]/max(1,$row['n']);
        }
        $dmean=$row['d']/max(1,$row['n']); [$dom,$domVal]=dominantDimension($mean,$dimensions); $pol=polarityFromValues($mean); $gid=$row['gruppe_id'];
        $drift=0.0; if(isset($prev[$gid])){$dv=[]; foreach($dimensions as $d){$dv[$d]=$mean[$d]-$prev[$gid][$d];} $drift=normN($dv);}
        $stab=1.0/(1.0+varianceN(array_values($mean))+$drift); $marker=transitionMarker7d($drift,$stab);
        $bem=sprintf('Gruppe %d %s: n=%d D=%.4f Drift=%.4f Stabilität=%.4f; v3: Mittel aus fusioniertem Zustand, Skalen- und Emotionsoperator separat gespeichert.',$gid,$row['date'],$row['n'],$dmean,$drift,$stab);
        $ins->execute([
            $gid,$row['date'],$row['n'],
            $mean['kognition'],$mean['sozial'],$mean['affektiv'],$mean['motivation'],$mean['methodik'],$mean['performanz'],$mean['regulation'],
            $dmean,$dom,$domVal,$pol,$drift,$stab,$marker,
            $row['emoN']?$row['val']/$row['emoN']:null,$row['emoN']?$row['akt']/$row['emoN']:null,$row['emoN'],
            $meanEmotion['kognition'],$meanEmotion['sozial'],$meanEmotion['affektiv'],$meanEmotion['motivation'],$meanEmotion['methodik'],$meanEmotion['performanz'],$meanEmotion['regulation'],
            $meanSkala['kognition'],$meanSkala['sozial'],$meanSkala['affektiv'],$meanSkala['motivation'],$meanSkala['methodik'],$meanSkala['performanz'],$meanSkala['regulation'],
            $bem
        ]);
        $json[]=['gruppe_id'=>$gid,'zeitpunkt'=>$row['date'],'anz_tn'=>$row['n'],'mean_fusion'=>$mean,'mean_skala'=>$meanSkala,'mean_emotion'=>$meanEmotion,'d_semantisch_mean'=>$dmean,'dominante_dimension'=>$dom,'gruppen_drift_norm'=>$drift,'gruppen_stabilitaet'=>$stab,'gruppen_transition_marker'=>$marker];
        $prev[$gid]=$mean; $c++;
    }
    file_put_contents(__DIR__.'/../frzk_group_semantische_dichte_7d.json', json_encode($json, JSON_FLAGS)); echo "✅ frzk_group_semantische_dichte_7d v3 befüllt ($c Gruppenzustände).\n";
}
?>
