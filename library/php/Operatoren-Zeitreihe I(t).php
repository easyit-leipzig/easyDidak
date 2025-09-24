<?php
// operators_time.php
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4","root","");

// Annahme: Tabelle hat Spalte `zeitpunkt`
$sql = "SELECT zeitpunkt,
        AVG(mitarbeit) as sigma,
        AVG(materialien) as S,
        AVG(absprachen) as D,
        AVG(selbststaendigkeit) as M,
        AVG(transferdenken) as R,
        AVG(lernfortschritt) as E
        FROM mtr_rueckkopplung_teilnehmer
        GROUP BY zeitpunkt ORDER BY zeitpunkt";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["I(t)"=>$data], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
