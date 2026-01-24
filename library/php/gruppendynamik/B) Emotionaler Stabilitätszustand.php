<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
// ----------------------------
// B) Kohärenz & Stabilität
// ----------------------------
$sql = "
    SELECT
        gruppe_id,
        ROUND(AVG(kohärenz), 4)   AS kohärenz_mean,
        ROUND(STDDEV_POP(kohärenz), 4) AS kohärenz_std,

        ROUND(AVG(stabilitaet), 4) AS stabilitaet_mean,
        ROUND(STDDEV_POP(stabilitaet), 4) AS stabilitaet_std,

        ROUND(AVG(dynamik), 4)    AS dynamik_mean,
        ROUND(STDDEV_POP(dynamik), 4) AS dynamik_std,

        COUNT(*) AS n
    FROM frzk_group_emotion
    WHERE kohärenz IS NOT NULL
      AND stabilitaet IS NOT NULL
      and gruppe_id < 10
    GROUP BY gruppe_id
    ORDER BY gruppe_id
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------
// JSON-Ausgabe
// ----------------------------
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("B) Emotionaler Stabilitätszustand.json",json_encode($data, JSON_PRETTY_PRINT));
?>
