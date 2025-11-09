<?php
// -*- coding: utf-8 -*-
// Tabelle 5.Y: Aggregierte FRZK-Übergänge (erfasst_am)
// Autor: [Dein Name]
// Datum: [heutiges Datum]

$host = 'localhost';
$db   = 'icas';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// 1. Daten auslesen (alle Zeitpunkte pro Gruppe)
$sql = "SELECT gruppe_id, erfasst_am,
               basiswissen, beherrscht_thema, transferdenken, lernfortschritt,
               mitarbeit, selbststaendigkeit, methodenvielfalt, konzentration,
               absprachen, individualisierung, aufforderung, fleiss
        FROM mtr_rueckkopplung_teilnehmer
        WHERE gruppe_id > 0
        ORDER BY gruppe_id, erfasst_am";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// 2. Gruppierte Zeitreihen vorbereiten
$groups = [];
foreach($rows as $row){
    $gid = $row['gruppe_id'];
    if(!isset($groups[$gid])) $groups[$gid] = [];
    // h-Wert berechnen: Mittelwert aller Dimensionen
    $h = array_sum([
        $row['basiswissen'], $row['beherrscht_thema'], $row['transferdenken'], $row['lernfortschritt'],
        $row['mitarbeit'], $row['selbststaendigkeit'], $row['methodenvielfalt'], $row['konzentration'],
        $row['absprachen'], $row['individualisierung'], $row['aufforderung'], $row['fleiss']
    ]) / 12.0;
    // erfasst_am als Timestamp
    $timestamp = strtotime($row['erfasst_am']);
    $groups[$gid][] = ['erfasst_am'=>$timestamp, 'h'=>$h];
}

// 3. dh/dt berechnen
$results = [];
foreach($groups as $gid => $timeSeries){
    $dh = [];
    for($i=1; $i<count($timeSeries); $i++){
        $delta_t = $timeSeries[$i]['erfasst_am'] - $timeSeries[$i-1]['erfasst_am'];
        if($delta_t == 0) continue; // gleiche Zeitpunkte
        $dh[] = ($timeSeries[$i]['h'] - $timeSeries[$i-1]['h']) / $delta_t;
    }
    if(count($dh)==0){
        $mean = 0.0;
        $std  = 0.0;
    } else {
        $mean = round(array_sum($dh)/count($dh), 4);
        $variance = array_sum(array_map(fn($v)=>pow($v-$mean,2), $dh))/count($dh);
        $std  = round(sqrt($variance), 4);
    }

    // Dominanter Transitionstyp ableiten
    $dominant = 'stabil';
    if($mean > 0.0001) $dominant = 'steigend';
    elseif($mean < -0.0001) $dominant = 'fallend';

    // Alle zutreffenden Transitionstypen
    $types = [];
    foreach($dh as $v){
        if($v > 0.0001) $types['steigend'] = true;
        elseif($v < -0.0001) $types['fallend'] = true;
        else $types['stabil'] = true;
    }
    $other_types = implode(', ', array_keys($types));

    $results[] = [
        'gruppe_id'=>$gid,
        'dh_mean'=>$mean,
        'dh_std'=>$std,
        'dominant'=>$dominant,
        'other'=>$other_types
    ];
}

// 4. Tabelle ausgeben
echo "<h2>Tabelle 5.Y: Aggregierte FRZK-Übergänge</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Gruppe</th><th>dh/dt (Mittelwert)</th><th>dh/dt (Std)</th><th>Dominanter Transitionstyp</th><th>Andere zutreffende Transitionstypen</th></tr>";

foreach($results as $r){
    echo "<tr>";
    echo "<td>G{$r['gruppe_id']}</td>";
    echo "<td>{$r['dh_mean']}</td>";
    echo "<td>{$r['dh_std']}</td>";
    echo "<td>{$r['dominant']}</td>";
    echo "<td>{$r['other']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
