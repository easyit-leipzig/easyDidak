<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");

$sql = "
SELECT
 gruppe_id,
 AVG(z_affektiv) AS z_mean,
 STDDEV(z_affektiv) AS z_std,
 COUNT(*) AS n
FROM frzk_group_emotion
where gruppe_id < 10
GROUP BY gruppe_id
ORDER BY gruppe_id;
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
file_put_contents("A) Affektives Grundniveau der Gruppe.json",json_encode($data, JSON_PRETTY_PRINT));  
?>
