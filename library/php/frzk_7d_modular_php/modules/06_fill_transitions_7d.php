<?php
function frzk7d_fill_transitions(PDO $pdo, array $dimensions): void {
    $sql="SELECT t1.*, (SELECT MIN(t2.zeitpunkt) FROM frzk_semantische_dichte_teilnehmer_7d t2 WHERE t2.teilnehmer_id=t1.teilnehmer_id AND t2.zeitpunkt>t1.zeitpunkt) AS next_time FROM frzk_semantische_dichte_teilnehmer_7d t1 ORDER BY t1.teilnehmer_id,t1.zeitpunkt";
    $find=$pdo->prepare("SELECT * FROM frzk_semantische_dichte_teilnehmer_7d WHERE teilnehmer_id=? AND zeitpunkt=? LIMIT 1");
    $ins=$pdo->prepare("INSERT INTO frzk_transitions_7d (teilnehmer_id,zeitpunkt_von,zeitpunkt_nach,dominante_dimension_von,dominante_dimension_nach,d_von,d_nach,delta,transition_typ,dominanzwechsel,bemerkung) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $c=0; foreach($pdo->query($sql) as $r){ if(empty($r['next_time'])) continue; $find->execute([(int)$r['teilnehmer_id'],$r['next_time']]); $n=$find->fetch(); if(!$n) continue; $dv=[]; foreach($dimensions as $d){$dv[$d]=(float)$n['sum_'.$d]-(float)$r['sum_'.$d];} $delta=normN($dv); $wechsel=$r['dominante_dimension']!==$n['dominante_dimension']?1:0; $typ=transitionMarker7d($delta,(float)$n['stabilitaet']); $bem=sprintf('%s → %s | Δ=%.4f | D %.4f→%.4f',$r['dominante_dimension'],$n['dominante_dimension'],$delta,(float)$r['d_semantisch'],(float)$n['d_semantisch']); $ins->execute([(int)$r['teilnehmer_id'],$r['zeitpunkt'],$n['zeitpunkt'],$r['dominante_dimension'],$n['dominante_dimension'],(float)$r['d_semantisch'],(float)$n['d_semantisch'],$delta,$typ,$wechsel,$bem]); $c++; }
    echo "✅ frzk_transitions_7d befüllt ($c Transitionen).\n";
}
?>
