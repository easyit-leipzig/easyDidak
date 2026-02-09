<?php
$pdo = new PDO(
 "mysql:host=localhost;dbname=icas;charset=utf8mb4",
 "root","",
 [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("
SELECT
 d.titel,
 b.kontext,
 b.text
FROM _frzk_orverlust_schuelerprofil_definition d
JOIN _frzk_orverlust_kapiteltext_bausteine b ON b.profil_id = d.id
WHERE d.aktiv = 1
ORDER BY d.id, b.kontext
");

$current = null;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($current !== $row["titel"]) {
        echo "\n\n### {$row['titel']}\n";
        $current = $row["titel"];
    }
    echo "\n{$row['text']}\n";
}
