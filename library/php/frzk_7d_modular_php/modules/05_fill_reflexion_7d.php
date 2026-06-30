<?php
function frzk7d_fill_reflexion(PDO $pdo): void {
    $groups=[]; foreach($pdo->query("SELECT * FROM frzk_semantische_dichte_teilnehmer_7d ORDER BY teilnehmer_id, zeitpunkt") as $r){$groups[(int)$r['teilnehmer_id']][]=$r;}
    $selfWords=['selbst','reflex','verstanden','klar','unklar','sicher','unsicher'];
    $ins=$pdo->prepare("INSERT INTO frzk_reflexion_7d (teilnehmer_id,zeitpunkt,reflexionsgrad,meta_kohaerenz,selbstbezug_index,stabilitaet,marker,bemerkung) VALUES (?,?,?,?,?,?,?,?)");
    $c=0; foreach($groups as $tid=>$rows){ $dr=array_map(fn($x)=>(float)$x['drift_norm'],$rows); $meta=1.0/(1.0+varianceN($dr)); $stab=array_sum(array_map(fn($x)=>(float)$x['stabilitaet'],$rows))/count($rows); $emoTxt=strtolower(implode(',',array_map(fn($x)=>(string)$x['emotion_ids'],$rows))); $self=0.0; foreach($selfWords as $w){ if(str_contains($emoTxt,$w)){$self=1.0; break;}} $grad=0.5*$stab+0.3*$meta+0.2*$self; $marker=$grad<0.33?'niedrig':($grad<0.66?'mittel':'hoch'); $bem=sprintf('Reflexionsgrad=%.4f Meta-Kohärenz=%.4f Selbstbezug=%.4f Stabilität=%.4f',$grad,$meta,$self,$stab); $ins->execute([$tid,end($rows)['zeitpunkt'],$grad,$meta,$self,$stab,$marker,$bem]); $c++; }
    echo "✅ frzk_reflexion_7d erstellt und befüllt ($c Datensätze).\n";
}
?>
