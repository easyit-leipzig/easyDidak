<?php
header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$data = $pdo -> query("select * from _mtr_emotionen") -> fetchAll(PDO::FETCH_ASSOC);
file_put_contents("_mtr_emotionen.json", json_encode($data));  
?>
