<?php
header('Content-Type: application/json');

$host = "localhost";
$db   = "icas";
$user = "root";
$pass = "";

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("truncate mtr_rueckkopplung_datenmaske_values");
$r = $pdo->query("select id, bemerkung from  mtr_rueckkopplung_datenmaske where bemerkung is not null and length(bemerkung)>50 order by id asc")->fetchAll(PDO::FETCH_ASSOC);
$l = count( $r );
$i = 0;
while( $i < $l ) {
    $pdo->exec("start transaction");
    $tmp = explode("|", $r[$i]["bemerkung"]);
    $k = count($tmp);
    $j = 0;
    while( $j < $k ) {
        if(strlen($tmp[$j])>40) {
            $pdo->exec("insert into mtr_rueckkopplung_datenmaske_values (id_mtr_rueckkopplung_datenmaske, value)
                values (" . $r[$i]["id"] .  ", '" . str_replace("'","", $tmp[$j] ) . "')");
        }
        $j +=1;                                                                                         
    }
    $pdo->exec("commit");
    
    $i += 1;
} 

$pdo->exec("start transaction");
while( $j < $k ) {
    $pdo->exec("insert into mtr_rueckkopplung_datenmaske_values (id_mtr_rueckkopplung_datenmaske, value)
     values (" . $r[$i]["id"] .  ", '" . str_replace("'","", $tmp[$j] ) . "')");
    $j +=1;                                                                                         
}
$pdo->exec("commit");
echo "mtr_rueckkopplung_datenmaske_values befüllt \n";    
// hole alle lehrkräfte aus mtr_rueckkopplung_datenmaske
$sql = "select distinct lehrkraft from mtr_rueckkopplung_datenmaske where thema is not null";
$r = $pdo -> query( $sql )->fetchAll( PDO::FETCH_ASSOC );
// iterriere über lehrkräfte
$l = count( $r );
$i = 0;
while( $i < $l ) {
    // iterriere für lehrkraft über das datum
    $sql = "select distinct datum, gruppe_id from mtr_rueckkopplung_datenmaske where lehrkraft = '" . $r[$i]["lehrkraft"];
    $k = count( $k );
    $j = 0;
    while( $j < $k ) {
        
        $j += 1;
    } 
     
    $i += 1;
} 





?>
