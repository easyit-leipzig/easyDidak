<?php
function frzk7d_fill_group_dynamics(PDO $pdo, array $dimensions): void {
    // Gruppentransitionen
    $rows=$pdo->query("SELECT * FROM frzk_group_semantische_dichte_7d ORDER BY gruppe_id, zeitpunkt")->fetchAll();
    $by=[]; foreach($rows as $r){$by[(int)$r['gruppe_id']][]=$r;}
    $insT=$pdo->prepare("INSERT INTO frzk_group_transitions_7d (gruppe_id,teilnehmer_id,zeitpunkt_von,zeitpunkt_nach,dominante_dimension_von,dominante_dimension_nach,d_von,d_nach,delta,transition_typ,dominanzwechsel,bemerkung) VALUES (?,0,?,?,?,?,?,?,?,?,?,?)");
    $insR=$pdo->prepare("INSERT INTO frzk_group_reflexion_7d (gruppe_id,zeitpunkt,reflexionsgrad,meta_kohaerenz,stabilitaet,marker,bemerkung) VALUES (?,?,?,?,?,?,?)");
    $insL=$pdo->prepare("INSERT INTO frzk_group_loops_7d (gruppe_id,start_zeit,end_zeit,schleifen_typ,dauer,drift_avg,stabilitaet,bemerkung) VALUES (?,?,?,?,?,?,?,?)");
    $ct=0;$cr=0;$cl=0; foreach($by as $gid=>$rs){
        for($i=0;$i<count($rs)-1;$i++){ $a=$rs[$i]; $b=$rs[$i+1]; $delta=(float)$b['gruppen_drift_norm']; $wechsel=$a['dominante_dimension']!==$b['dominante_dimension']?1:0; $typ=transitionMarker7d($delta,(float)$b['gruppen_stabilitaet']); $bem=sprintf('Gruppe %d: %s → %s | Δ=%.4f',$gid,$a['dominante_dimension'],$b['dominante_dimension'],$delta); $insT->execute([$gid,$a['zeitpunkt'],$b['zeitpunkt'],$a['dominante_dimension'],$b['dominante_dimension'],(float)$a['d_semantisch_mean'],(float)$b['d_semantisch_mean'],$delta,$typ,$wechsel,$bem]); $ct++; }
        $dr=array_map(fn($x)=>(float)$x['gruppen_drift_norm'],$rs); $st=array_map(fn($x)=>(float)$x['gruppen_stabilitaet'],$rs); $meta=1/(1+varianceN($dr)); $stab=count($st)?array_sum($st)/count($st):0; $grad=0.6*$stab+0.4*$meta; $marker=$grad<0.33?'niedrig':($grad<0.66?'mittel':'hoch'); $insR->execute([$gid,end($rs)['zeitpunkt'],$grad,$meta,$stab,$marker,sprintf('Gruppenreflexion: Grad=%.4f Meta=%.4f Stabilität=%.4f',$grad,$meta,$stab)]); $cr++;
        if(count($rs)>1){$avg=count($dr)?array_sum($dr)/count($dr):0; $typ=$avg<0.15?'stabilisierend':($avg<0.5?'adaptiv':'instabil'); $insL->execute([$gid,$rs[0]['zeitpunkt'],end($rs)['zeitpunkt'],$typ,count($rs),$avg,$stab,sprintf('Gruppenloop: Drift_avg=%.4f Stabilität=%.4f',$avg,$stab)]); $cl++;}
    }
    echo "✅ Gruppendynamik 7D befüllt: $ct Transitionen, $cr Reflexionen, $cl Loops.\n";
}
?>
