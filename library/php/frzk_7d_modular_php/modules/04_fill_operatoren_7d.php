<?php
function frzk7d_fill_operatoren(PDO $pdo): void {
    $ins=$pdo->prepare("INSERT INTO frzk_operatoren_7d (teilnehmer_id,gruppe_id,zeitpunkt,sigma,M,R,E,operator_status,bemerkung) VALUES (?,?,?,?,?,?,?,?,?)");
    $c=0; foreach($pdo->query("SELECT * FROM frzk_semantische_dichte_teilnehmer_7d ORDER BY teilnehmer_id, zeitpunkt") as $r){
        $sigma=(float)$r['d_semantisch']; $M=(float)($r['stabilitaet']??0); $R=1.0/(1.0+(float)($r['drift_norm']??0)); $E=abs((float)($r['d_semantisch_delta']??0)) + ((int)($r['dominanzwechsel']??0)*0.25);
        $status=$E>0.8?'emergent-kritisch':($R>0.85&&$M>0.75?'kohärent-resilient':'adaptiv');
        $bem=sprintf('σ=%.4f M=%.4f R=%.4f E=%.4f | %s',$sigma,$M,$R,$E,$status);
        $ins->execute([(int)$r['teilnehmer_id'],(int)$r['gruppe_id'],$r['zeitpunkt'],$sigma,$M,$R,$E,$status,$bem]); $c++;
    }
    echo "✅ $c Operator-Datensätze in frzk_operatoren_7d eingefügt (σ,M,R,E).\n";
}
?>
