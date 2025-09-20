<?php
// operators_time.php
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4","root","");

// Annahme: Tabelle hat Spalte `zeitpunkt`
$sql = "SELECT zeitpunkt,
        AVG(val_mitarbeit) as sigma,
        AVG(val_materialien) as S,
        AVG(val_absprachen) as D,
        AVG(val_selbststaendigkeit) as M,
        AVG(val_transferdenken) as R,
        AVG(val_lernfortschritt) as E
        FROM mtr_rueckkopplung_teilnehmer
        GROUP BY zeitpunkt ORDER BY zeitpunkt";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["I(t)"=>$data], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
