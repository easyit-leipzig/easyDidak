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
    $sql = "select distinct datum from mtr_rueckkopplung_datenmaske where lehrkraft = '" . $r[$i]["lehrkraft"];
    $k = count( $k );
    $j = 0;
    while( $i < $l ) {
        
        $i += 1;
    } 
     
    $i += 1;
} 





?>
