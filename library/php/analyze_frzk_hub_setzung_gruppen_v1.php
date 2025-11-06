<?php
// analyze_frzk_group_hubs.php
// Gruppenbasierte FRZK-Hub-Analyse – erkennt kollektive „Hubs“ pro Gruppe
// (Aggregiert aus mtr_rueckkopplung_teilnehmer, schreibt Ergebnisse in frzk_group_hubs)

header('Content-Type: text/html; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Tabelle frzk_group_hubs sicherstellen ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_hubs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gruppe_id INT NOT NULL,
  zeitpunkt DATETIME NOT NULL,
  avg_x FLOAT,
  avg_y FLOAT,
  avg_z FLOAT,
  avg_h FLOAT,
  var_h FLOAT,
  z_h FLOAT,
  hub_typ VARCHAR(30),
  einschätzung TEXT,
  methodik TEXT,
  n_teilnehmer INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("TRUNCATE frzk_group_hubs");

// --- Skalen definieren ---
$kognitiv = ["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial   = ["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv = ["fleiss","lernfortschritt"];

// --- Daten holen ---
$sql = "SELECT gruppe_id, erfasst_am, ".
        implode(",", array_merge($kognitiv, $sozial, $affektiv)).
        " FROM mtr_rueckkopplung_teilnehmer ORDER BY gruppe_id, erfasst_am";
$rows = $pdo->query($sql)->fetchAll();
if (!$rows) exit("Keine Daten gefunden.");

// --- Hilfsfunktionen ---
function mean($a){ $a=array_values(array_filter($a,fn($v)=>$v!==null&&$v!=='')); return count($a)?array_sum($a)/count($a):0; }
function median($a){ $a=array_values($a); sort($a); $n=count($a); if(!$n) return 0; return ($n%2)?$a[floor($n/2)]:($a[$n/2-1]+$a[$n/2])/2; }
function mad($a){ $m=median($a); $devs=array_map(fn($x)=>abs($x-$m),$a); return median($devs); }
function pickRandom($pool,$n=3){ $pool=array_values($pool); shuffle($pool); return implode(", ", array_slice($pool,0,min($n,count($pool)))); }

// --- Methodik-Pools ---
$methodikPool = [
  "kognitiv"=>["Problem-Based Learning","Concept Mapping","Think-Pair-Share","Cognitive Apprenticeship","Leittextarbeit","Sokratische Methode","Scaffolding","Lernaufgaben mit Anforderungszuwachs"],
  "sozial"=>["Gruppenpuzzle","Peer Instruction","World Café","Fishbowl-Diskussion","Design Sprint","Kooperative Fallarbeit","Gruppenfeedback","Rollenzirkulation"],
  "affektiv"=>["Storytelling","Emotionale Ankerarbeit","Ästhetisches Lernen","Rollenspiel","Dramapädagogik","Feedback-Loops","Gamification","Symbolisches Handeln"],
  "systemisch"=>["Design Thinking","Reflexionszirkel","Portfolioarbeit","Selbstorganisierte Lernprojekte","Critical Incident Analysis","Kollegiale Beratung","Adaptive Lernpfade"],
  "integrativ"=>["Projektarbeit","Forschendes Lernen","Stationenlernen","Blended Learning","Lernateliers","Lernlandschaften","Kooperative Simulationen","Transversales Lernfeld"]
];

// --- Daten nach Gruppe & Zeit bündeln ---
$data = [];
foreach ($rows as $r) {
  $key = $r['gruppe_id'].'|'.$r['erfasst_am'];
  $data[$key][] = $r;
}

// --- Prepared Statement ---
$insert = $pdo->prepare("
INSERT INTO frzk_group_hubs
(gruppe_id, zeitpunkt, avg_x, avg_y, avg_z, avg_h, var_h, z_h, hub_typ, einschätzung, methodik, n_teilnehmer)
VALUES (:gid, :zeit, :x, :y, :z, :h, :varh, :zh, :typ, :einsch, :meth, :n)
");

$summary = ["kognitiv"=>0,"sozial"=>0,"affektiv"=>0,"systemisch"=>0,"integrativ"=>0];
$prevGroupMeans = [];

echo "<h2>FRZK-Hub-Analyse (Gruppenebene)</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;font-size:13px;width:100%'>";
echo "<tr style='background:#eee'><th>Gruppe</th><th>Zeit</th><th>n</th><th>Øx</th><th>Øy</th><th>Øz</th><th>Øh</th><th>z_h</th><th>Status</th><th>Typ</th><th>Einschätzung</th><th>Methodik</th></tr>";

foreach ($data as $key=>$set) {
  list($gid,$zeit) = explode("|",$key);

  // Mittelwerte pro Dimension über alle Teilnehmer
  $x_vals = $y_vals = $z_vals = $h_vals = [];
  foreach ($set as $r) {
    $x = mean(array_intersect_key($r, array_flip($kognitiv)));
    $y = mean(array_intersect_key($r, array_flip($sozial)));
    $z = mean(array_intersect_key($r, array_flip($affektiv)));
    $h = mean([$x,$y,$z]);
    $x_vals[]=$x; $y_vals[]=$y; $z_vals[]=$z; $h_vals[]=$h;
  }

  $avg_x = mean($x_vals);
  $avg_y = mean($y_vals);
  $avg_z = mean($z_vals);
  $avg_h = mean($h_vals);
  $var_h = count($h_vals)>1?array_sum(array_map(fn($v)=>pow($v-$avg_h,2),$h_vals))/(count($h_vals)-1):0;

  $mh = median($h_vals);
  $mad_val = mad($h_vals);
  $z_h = ($mad_val>0)?($avg_h-$mh)/$mad_val:0;

  $trend = false;
  if (isset($prevGroupMeans[$gid]) && abs($avg_h - $prevGroupMeans[$gid])>0.4) $trend=true;
  $prevGroupMeans[$gid]=$avg_h;

  $flag = (abs($z_h)>=1.5 || $trend);
  $status = $flag ? "⚡ Hub" : "ok";

  // --- Hubtyp bestimmen ---
  $typ=""; $einsch=""; $meth="";
  if ($flag) {
    $eps = 0.05;
    if ($avg_x>$avg_y+$eps && $avg_x>$avg_z+$eps) {
      $typ="kognitiv";
      $einsch="Kollektive kognitive Aktivierung – komplexe Denkprozesse in der Gruppe.";
      $meth=pickRandom($methodikPool["kognitiv"],3);
    } elseif ($avg_y>$avg_x+$eps && $avg_y>$avg_z+$eps) {
      $typ="sozial";
      $einsch="Gruppenbezogene Interaktion und Kooperation – soziale Aushandlungsprozesse.";
      $meth=pickRandom($methodikPool["sozial"],3);
    } elseif ($avg_z>$avg_x+$eps && $avg_z>$avg_y+$eps) {
      $typ="affektiv";
      $einsch="Affektive Gruppenresonanz – emotionale Synchronisation.";
      $meth=pickRandom($methodikPool["affektiv"],3);
    } elseif (abs($z_h)>2.5) {
      $typ="systemisch";
      $einsch="Systemische Selbstreferenz der Gruppe – kollektive Metareflexion.";
      $meth=pickRandom($methodikPool["systemisch"],3);
    } else {
      $typ="integrativ";
      $einsch="Integrativer Gruppen-Hub – Kohärenz über multiple Dimensionen.";
      $meth=pickRandom($methodikPool["integrativ"],3);
    }
    $summary[$typ]++;

    // --- SQL speichern ---
    $insert->execute([
      ":gid"=>$gid, ":zeit"=>$zeit, ":x"=>$avg_x, ":y"=>$avg_y, ":z"=>$avg_z,
      ":h"=>$avg_h, ":varh"=>$var_h, ":zh"=>$z_h,
      ":typ"=>$typ, ":einsch"=>$einsch, ":meth"=>$meth,
      ":n"=>count($set)
    ]);
  }

  echo "<tr>
          <td>$gid</td>
          <td>$zeit</td>
          <td style='text-align:center'>".count($set)."</td>
          <td style='text-align:center'>".round($avg_x,2)."</td>
          <td style='text-align:center'>".round($avg_y,2)."</td>
          <td style='text-align:center'>".round($avg_z,2)."</td>
          <td style='text-align:center'>".round($avg_h,2)."</td>
          <td style='text-align:center'>".round($z_h,2)."</td>
          <td style='text-align:center'>$status</td>
          <td>$typ</td>
          <td>$einsch</td>
          <td>$meth</td>
       </tr>";
}

echo "</table>";
echo "<h3>Zusammenfassung erkannter Gruppen-Hubs</h3><ul>";
foreach ($summary as $t=>$c) echo "<li><b>".ucfirst($t)."</b>: $c</li>";
echo "</ul><p><b>Alle Gruppenergebnisse wurden in <code>frzk_group_hubs</code> gespeichert.</b></p>";
?>
