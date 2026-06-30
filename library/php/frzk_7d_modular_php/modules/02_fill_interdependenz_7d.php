<?php
function frzk7d_fill_interdependenz(PDO $pdo, array $dimensions): void {
    $rows=$pdo->query("SELECT * FROM frzk_semantische_dichte_teilnehmer_7d ORDER BY teilnehmer_id, zeitpunkt")->fetchAll();
    $ins=$pdo->prepare("INSERT INTO frzk_interdependenz_7d (teilnehmer_id,gruppe_id,zeitpunkt,x_kognition,x_sozial,x_affektiv,x_motivation,x_methodik,x_performanz,x_regulation,d_semantisch,korrelationsscore,kohaerenz_index,varianz_7d,bemerkung) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $c=0;
    foreach($rows as $r){
        $v=[]; foreach($dimensions as $d){$v[$d]=(float)$r['sum_'.$d];}
        $vals=array_values($v); $n=count($vals); $pairs=0; $corr=0.0;
        for($i=0;$i<$n;$i++) for($j=$i+1;$j<$n;$j++){ $corr += $vals[$i]*$vals[$j]; $pairs++; }
        $corr=$pairs>0?$corr/$pairs:0.0; $var=varianceN($vals); $koh=1.0/(1.0+$var+abs((float)($r['drift_norm']??0)));
        $bem=sprintf('7D Corr=%.4f Koh=%.4f Var=%.4f D=%.4f',$corr,$koh,$var,(float)$r['d_semantisch']);
        $ins->execute([(int)$r['teilnehmer_id'],(int)$r['gruppe_id'],$r['zeitpunkt'],(float)$r['x_kognition'],(float)$r['x_sozial'],(float)$r['x_affektiv'],(float)$r['x_motivation'],(float)$r['x_methodik'],(float)$r['x_performanz'],(float)$r['x_regulation'],(float)$r['d_semantisch'],$corr,$koh,$var,$bem]); $c++;
    }
    echo "✅ $c Datensätze in frzk_interdependenz_7d eingefügt.\n";
}
?>
