<?php
// ============================================================================
// 01_fill_semantische_dichte_teilnehmer_7d.php
// v3: Skalenvektor, Emotionsvektor und FRZK-Fusion werden getrennt berechnet.
// ============================================================================

function frzk7d_build_skalenvektor(array $r, array $dimensions): array {
    $sum = array_fill_keys($dimensions, 0.0);
    $weight = array_fill_keys($dimensions, 0.0);

    $mitarbeit          = signedFromRow($r,'mitarbeit');
    $absprachen         = signedFromRow($r,'absprachen');
    $selbst             = signedFromRow($r,'selbststaendigkeit');
    $konz               = signedFromRow($r,'konzentration');
    $fleiss             = signedFromRow($r,'fleiss');
    $lern               = signedFromRow($r,'lernfortschritt');
    $beh                = signedFromRow($r,'beherrscht_thema');
    $trans              = signedFromRow($r,'transferdenken');
    $basis              = signedFromRow($r,'basiswissen');
    $vorb               = signedFromRow($r,'vorbereitet');
    $thema              = signedFromRow($r,'themenauswahl');
    $mat                = signedFromRow($r,'materialien');
    $meth               = signedFromRow($r,'methodenvielfalt');
    $ind                = signedFromRow($r,'individualisierung');
    $auff               = signedFromRow($r,'aufforderung');
    $ziel               = signedFromRow($r,'zielgruppen');

    // Kognition
    addWeighted($sum,$weight,'kognition',$lern,1.0);
    addWeighted($sum,$weight,'kognition',$beh,1.2);
    addWeighted($sum,$weight,'kognition',$trans,1.3);
    addWeighted($sum,$weight,'kognition',$basis,1.2);

    // Sozial
    addWeighted($sum,$weight,'sozial',$mitarbeit,1.0);
    addWeighted($sum,$weight,'sozial',$absprachen,1.1);
    addWeighted($sum,$weight,'sozial',$auff,0.8);
    addWeighted($sum,$weight,'sozial',$ziel,0.9);

    // Affektiv
    addWeighted($sum,$weight,'affektiv',$fleiss,0.7);
    addWeighted($sum,$weight,'affektiv',$lern,0.5);
    addWeighted($sum,$weight,'affektiv',$thema,0.5);

    // Motivation
    addWeighted($sum,$weight,'motivation',$fleiss,1.2);
    addWeighted($sum,$weight,'motivation',$vorb,0.8);
    addWeighted($sum,$weight,'motivation',$thema,0.9);
    addWeighted($sum,$weight,'motivation',$lern,0.7);

    // Methodik
    addWeighted($sum,$weight,'methodik',$mat,1.0);
    addWeighted($sum,$weight,'methodik',$meth,1.1);
    addWeighted($sum,$weight,'methodik',$ind,1.0);
    addWeighted($sum,$weight,'methodik',$thema,0.7);

    // Performanz
    addWeighted($sum,$weight,'performanz',$mitarbeit,0.8);
    addWeighted($sum,$weight,'performanz',$lern,1.2);
    addWeighted($sum,$weight,'performanz',$beh,1.3);
    addWeighted($sum,$weight,'performanz',$trans,1.0);

    // Regulation
    addWeighted($sum,$weight,'regulation',$selbst,1.3);
    addWeighted($sum,$weight,'regulation',$konz,1.2);
    addWeighted($sum,$weight,'regulation',$vorb,1.0);
    addWeighted($sum,$weight,'regulation',$absprachen,1.0);

    $S = [];
    foreach ($dimensions as $d) $S[$d] = $weight[$d] > 0 ? $sum[$d] / $weight[$d] : 0.0;
    return $S;
}

function frzk7d_fill_semantische_dichte_teilnehmer(PDO $pdo, array $dimensions): void {
    echo "Lade Emotionsdaten ...\n";
    $emotionMap=[];
    foreach($pdo->query("SELECT id,type_name,fine_label,emotion,valenz,aktivierung FROM _mtr_emotionen") as $e){
        $emotionMap[(int)$e['id']] = [
            'type_name'=>(string)($e['type_name']??''),
            'fine_label'=>(string)($e['fine_label']??''),
            'emotion'=>(string)($e['emotion']??''),
            'valenz'=>(float)($e['valenz']??0),
            'aktivierung'=>(float)($e['aktivierung']??0)
        ];
    }
    echo "→ ".count($emotionMap)." Emotionen geladen.\n";

    $rows=$pdo->query("SELECT * FROM mtr_rueckkopplung_teilnehmer WHERE erfasst_am IS NOT NULL ORDER BY teilnehmer_id ASC, erfasst_am ASC, id ASC")->fetchAll();
    echo "→ ".count($rows)." Teilnehmerdatensätze gefunden.\n";

    $insert=$pdo->prepare("INSERT INTO frzk_semantische_dichte_teilnehmer_7d
        (rueckkopplung_teilnehmer_id,ue_id,ue_zuweisung_teilnehmer_id,teilnehmer_id,gruppe_id,zeitpunkt,
         x_kognition,x_sozial,x_affektiv,x_motivation,x_methodik,x_performanz,x_regulation,
         sum_kognition,sum_sozial,sum_affektiv,sum_motivation,sum_methodik,sum_performanz,sum_regulation,
         emotion_ids,emotion_valenz,emotion_aktivierung,emotion_anzahl,
         emotion_vector_kognition,emotion_vector_sozial,emotion_vector_affektiv,emotion_vector_motivation,emotion_vector_methodik,emotion_vector_performanz,emotion_vector_regulation,
         skala_kognition,skala_sozial,skala_affektiv,skala_motivation,skala_methodik,skala_performanz,skala_regulation,
         fusion_alpha,fusion_beta,fusion_lambda,fusion_delta,emotion_cosine,
         dominante_dimension,dominante_dimension_wert,polaritaet_gesamt,d_semantisch,
         drift_norm,d_semantisch_delta,dominanzwechsel,stabilitaet,transition_marker)
         VALUES (?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?, ?,?,?,?,?)");

    $prev=[]; $json=[]; $eps=1e-9; $n=0;
    foreach($rows as $r){
        $tid=(int)$r['teilnehmer_id'];
        $S = frzk7d_build_skalenvektor($r, $dimensions);
        $emotionRawIds = normalizeEmotionIds($r['emotions'] ?? '');
        $emotionResult = buildEmotionVector7($emotionRawIds, $emotionMap, $dimensions);
        $Evec = $emotionResult['vector'];
        $V = fuseFrzkState7($S, $Evec, $dimensions);
        $emotionCos = cosine7($S, $Evec, $dimensions);

        $norm=normN($V); $X=[]; foreach($dimensions as $d){ $X[$d]=$V[$d]/($norm+$eps); }
        [$dom,$domVal]=dominantDimension($V,$dimensions); $pol=polarityFromValues($V);

        $drift=0.0; $dDelta=0.0; $domChange=0;
        if(isset($prev[$tid])){
            $dv=[]; foreach($dimensions as $d){$dv[$d]=$V[$d]-$prev[$tid]['V'][$d];}
            $drift=normN($dv); $dDelta=$norm-$prev[$tid]['d']; $domChange=($dom!==$prev[$tid]['dom'])?1:0;
        }
        $stab=1.0/(1.0+varianceN(array_values($V))+$drift); $marker=transitionMarker7d($drift,$stab);

        $insert->execute([
            (int)$r['id'],(int)($r['ue_id']??0),(int)($r['ue_zuweisung_teilnehmer_id']??0),$tid,(int)($r['gruppe_id']??0),$r['erfasst_am'],
            $X['kognition'],$X['sozial'],$X['affektiv'],$X['motivation'],$X['methodik'],$X['performanz'],$X['regulation'],
            $V['kognition'],$V['sozial'],$V['affektiv'],$V['motivation'],$V['methodik'],$V['performanz'],$V['regulation'],
            implode(',',$emotionResult['ids']),$emotionResult['valenz'],$emotionResult['aktivierung'],$emotionResult['count'],
            $Evec['kognition'],$Evec['sozial'],$Evec['affektiv'],$Evec['motivation'],$Evec['methodik'],$Evec['performanz'],$Evec['regulation'],
            $S['kognition'],$S['sozial'],$S['affektiv'],$S['motivation'],$S['methodik'],$S['performanz'],$S['regulation'],
            FRZK_ALPHA_EMOTION,FRZK_BETA_DAMPING,FRZK_LAMBDA_INTERFERENCE,FRZK_DELTA_RESONANCE,$emotionCos,
            $dom,$domVal,$pol,$norm,$drift,$dDelta,$domChange,$stab,$marker
        ]);

        $json[]=[
            'rueckkopplung_teilnehmer_id'=>(int)$r['id'],
            'teilnehmer_id'=>$tid,'gruppe_id'=>(int)($r['gruppe_id']??0),'zeitpunkt'=>$r['erfasst_am'],
            'V_skala'=>$S,'V_emotion'=>$Evec,'V_fusion'=>$V,'X'=>$X,
            'fusion_parameter'=>['alpha'=>FRZK_ALPHA_EMOTION,'beta'=>FRZK_BETA_DAMPING,'lambda'=>FRZK_LAMBDA_INTERFERENCE,'delta'=>FRZK_DELTA_RESONANCE],
            'emotion_cosine'=>$emotionCos,
            'd_semantisch'=>$norm,'dominante_dimension'=>$dom,'dominante_dimension_wert'=>$domVal,'polaritaet_gesamt'=>$pol,
            'drift_norm'=>$drift,'d_semantisch_delta'=>$dDelta,'dominanzwechsel'=>$domChange,'stabilitaet'=>$stab,'transition_marker'=>$marker,
            'emotion_ids'=>$emotionResult['ids'],'emotion_valenz'=>$emotionResult['valenz'],'emotion_aktivierung'=>$emotionResult['aktivierung'],'emotion_anzahl'=>$emotionResult['count']
        ];
        $prev[$tid]=['V'=>$V,'d'=>$norm,'dom'=>$dom];
        $n++; if($n%100===0) echo "→ $n / ".count($rows)." Datensätze verarbeitet\n";
    }
    file_put_contents(__DIR__.'/../frzk_semantische_dichte_teilnehmer_7d.json', json_encode($json, JSON_FLAGS));
    echo "✅ frzk_semantische_dichte_teilnehmer_7d v3 befüllt: Skala + Emotion + FRZK-Fusion.\n";
}
?>
