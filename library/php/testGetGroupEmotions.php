<?php
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require_once( "classes\Emotions.php");
$param = new \stdClass();
$param -> pdo = $pdo;
$myEmotions = new \Emotions($param);
$i =  $myEmotions;
?>
