<?php
function frzk7d_fill_loops(PDO $pdo): void {
    $groups=[]; foreach($pdo->query("SELECT teilnehmer_id,zeitpunkt,d_semantisch,drift_norm,stabilitaet FROM frzk_semantische_dichte_teilnehmer_7d ORDER BY teilnehmer_id, zeitpunkt") as $r){$groups[(int)$r['teilnehmer_id']][]=$r;}
    $ins=$pdo->prepare("INSERT INTO frzk_loops_7d (teilnehmer_id,start_zeit,end_zeit,schleifen_typ,dauer,drift_avg,verdichtungsgrad,stabilitaet,pausenmarker,bemerkung) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $c=0; foreach($groups as $tid=>$rows){ if(count($rows)<2) continue; $drifts=array_map(fn($x)=>(float)$x['drift_norm'],$rows); $ds=array_map(fn($x)=>(float)$x['d_semantisch'],$rows); $st=array_map(fn($x)=>(float)$x['stabilitaet'],$rows); $avgD=array_sum($drifts)/count($drifts); $verd=max($ds)-min($ds); $stab=array_sum($st)/count($st); $typ=$avgD<0.15?'stabilisierend':($avgD<0.5?'adaptiv':'instabil'); $pause=$avgD<0.05?'Trägheitsmodus':'Dynamikmodus'; $bem=sprintf('Loop 7D: Drift_avg=%.4f Verdichtung=%.4f Stabilität=%.4f',$avgD,$verd,$stab); $ins->execute([$tid,$rows[0]['zeitpunkt'],end($rows)['zeitpunkt'],$typ,count($rows),$avgD,$verd,$stab,$pause,$bem]); $c++; }
    echo "✅ $c Rückkopplungsschleifen in frzk_loops_7d eingefügt.\n";
}
?>
