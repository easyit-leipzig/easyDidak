<?php
// --- 1. DB-Zugangsdaten ---
$host = "localhost";
$db   = "icas";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Verbindung erfolgreich<br>";
} catch (PDOException $e) {
    die("❌ Verbindungsfehler: " . $e->getMessage());
}

/* ================================================================
   1) FRZK_LOOPS befüllen
   Idee: Loops = zusammenhängende Lernphasen
   Dauer  = Anzahl Messungen pro Teilnehmer
   Verdichtungsgrad = Ø h_bedeutung
   Stabilität = Varianz-Index (niedrige Varianz = hohe Stabilität)
================================================================ */
$sql = "SELECT teilnehmer_id, COUNT(*) AS dauer, 
               AVG(h_bedeutung) AS verdichtungsgrad,
               1 / (1 + VARIANCE(h_bedeutung)) AS stabilitaet
        FROM frzk_semantische_dichte
        GROUP BY teilnehmer_id";
$rows = $pdo->query($sql)->fetchAll();

$stmt = $pdo->prepare("
    INSERT INTO frzk_loops (teilnehmer_id, verdichtungsgrad, dauer, stabilitaet)
    VALUES (?, ?, ?, ?)
");
foreach ($rows as $r) {
    $stmt->execute([
        $r["teilnehmer_id"],
        round($r["verdichtungsgrad"], 2),
        $r["dauer"],
        round($r["stabilitaet"], 2)
    ]);
}
echo "✅ frzk_loops befüllt<br>";

/* ================================================================
   2) FRZK_OPERATORER befüllen
   Idee: Operatoren = Funktionen aus Emotionen
   sigma    = Mittelwert positiver Emotionen (Freude + Neugier)
   meta     = Selbst-/Gruppenunsicherheit (Unsicherheit + Interesse)
   rekursion= Überforderung als Indikator für Rückkopplungen
   emergenz = Staunen als Indikator für Neuheit
================================================================ */
$sql = "SELECT teilnehmer_id,
               AVG((freude + neugier)/2) AS sigma,
               AVG((unsicherheit + interesse)/2) AS meta,
               AVG(ueberforderung) AS rekursion,
               AVG(staunen) AS emergenz
        FROM mtr_emotions
        GROUP BY teilnehmer_id";
$rows = $pdo->query($sql)->fetchAll();

$stmt = $pdo->prepare("
    INSERT INTO frzk_operatoren (teilnehmer_id, sigma, meta, rekursion, emergenz)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($rows as $r) {
    $stmt->execute([
        $r["teilnehmer_id"],
        round($r["sigma"], 2),
        round($r["meta"], 2),
        round($r["rekursion"], 2),
        round($r["emergenz"], 2)
    ]);
}
echo "✅ frzk_operatoren befüllt<br>";

/* ================================================================
   3) FRZK_REFLEXION befüllen
   Idee: Reflexionen = automatisch aus Cluster- und Teilnehmerdaten
   Ebene wird zufällig zugeordnet (Demo), Kommentar aus Cluster/Emotion
================================================================ */
$ebenen = ["Selbst", "Gruppe", "Lehrkraft", "Forscher"];
$sql = "SELECT sd.teilnehmer_id, sd.cluster_id, AVG(me.unsicherheit) AS unsicherheit
        FROM frzk_semantische_dichte sd
        LEFT JOIN mtr_emotions me ON sd.teilnehmer_id = me.teilnehmer_id
        GROUP BY sd.teilnehmer_id, sd.cluster_id";
$rows = $pdo->query($sql)->fetchAll();

$stmt = $pdo->prepare("
    INSERT INTO frzk_reflexion (teilnehmer_id, ebene, kommentar)
    VALUES (?, ?, ?)
");
foreach ($rows as $r) {
    $ebene = $ebenen[array_rand($ebenen)];
    $kommentar = "Cluster " . $r["cluster_id"] . 
                 " – Reflexion mit Unsicherheitswert " . round($r["unsicherheit"], 2);
    $stmt->execute([$r["teilnehmer_id"], $ebene, $kommentar]);
}
echo "✅ frzk_reflexion befüllt<br>";
/*
🔑 Erklärung

frzk_loops: Dauer = Messanzahl, Verdichtungsgrad = Ø h_bedeutung, Stabilität = Formel auf Varianz.

frzk_operatoren: Werte berechnet aus Emotionstabellen (mtr_emotions).

frzk_reflexion: Kommentare automatisch erzeugt aus cluster_id und unsicherheit.
*/
?>
