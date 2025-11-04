<?php
// analyze_frzk_hub_setzung_v6_fix.php
// FRZK-Hub-Analyse + SQL-Speicherung (korrigierte Version: Variablen initialisiert)

header('Content-Type: text/html; charset=utf-8');

$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

// --- Tabelle frzk_hubs sicherstellen ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_hubs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teilnehmer_id INT NOT NULL,
  gruppe_id INT NOT NULL,
  zeitpunkt DATETIME NOT NULL,
  x_kognition FLOAT,
  y_sozial FLOAT,
  z_affektiv FLOAT,
  z_h FLOAT,
  hub_typ VARCHAR(30),
  `einschätzung` TEXT,
  methodik TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("truncate frzk_hubs");
// Felder definieren
$kognitiv=["mitarbeit","selbststaendigkeit","konzentration","basiswissen","vorbereitet"];
$sozial=["absprachen","themenauswahl","individualisierung","zielgruppen"];
$affektiv=["fleiss","lernfortschritt"];

// Daten holen
$sql="SELECT teilnehmer_id,gruppe_id,erfasst_am,".implode(",",array_merge($kognitiv,$sozial,$affektiv))."
      FROM mtr_rueckkopplung_teilnehmer ORDER BY gruppe_id,erfasst_am";
$rows=$pdo->query($sql)->fetchAll();
if(!$rows) exit("Keine Daten.");

// Hilfsfunktionen
function mean($a){ $a=array_values(array_filter($a,fn($v)=>$v!==null&&$v!==''));
                  return count($a)?array_sum($a)/count($a):0; }
function median($a){ $a=array_values($a); sort($a); $c=count($a); if(!$c) return 0;
                    return ($c%2)?$a[floor($c/2)]:($a[$c/2-1]+$a[$c/2])/2; }
function mad($a){ $m=median($a); $devs=array_map(fn($x)=>abs($x-$m),$a); return median($devs); }
function pickRandom($pool,$n=3){ $pool=array_values($pool); shuffle($pool); return implode(", ", array_slice($pool,0,min($n,count($pool)))); }

// Methodik-Pools
$methodikPool=[
 "kognitiv"=>["Problem-Based Learning","Concept Mapping","Think-Pair-Share","Cognitive Apprenticeship","Leittextarbeit","Sokratische Methode","Scaffolding","Lernaufgaben mit Anforderungszuwachs"],
 "sozial"=>["Gruppenpuzzle","Peer Instruction","World Café","Fishbowl-Diskussion","Design Sprint","Kooperative Fallarbeit","Gruppenfeedback","Rollenzirkulation"],
 "affektiv"=>["Storytelling","Emotionale Ankerarbeit","Ästhetisches Lernen","Rollenspiel","Dramapädagogik","Feedback-Loops","Gamification","Symbolisches Handeln"],
 "systemisch"=>["Design Thinking","Reflexionszirkel","Portfolioarbeit","Selbstorganisierte Lernprojekte","Critical Incident Analysis","Kollegiale Beratung","Adaptive Lernpfade"],
 "integrativ"=>["Projektarbeit","Forschendes Lernen","Stationenlernen","Blended Learning","Lernateliers","Lernlandschaften","Kooperative Simulationen","Transversales Lernfeld"]
];

$data=[];
foreach($rows as $r){ $key=$r['gruppe_id'].'|'.$r['erfasst_am']; $data[$key][]=$r; }

$insert=$pdo->prepare("
INSERT INTO frzk_hubs
(teilnehmer_id,gruppe_id,zeitpunkt,x_kognition,y_sozial,z_affektiv,z_h,hub_typ,`einschätzung`,methodik)
VALUES (:tid,:gid,:zeit,:x,:y,:z,:zh,:typ,:einsch,:meth)
");

$prevGroupMeans=[];
$summary=["kognitiv"=>0,"sozial"=>0,"affektiv"=>0,"systemisch"=>0,"integrativ"=>0];

echo "<h2>FRZK-Hub-Analyse (v6 → SQL-Speicherung, fix)</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;font-size:13px;width:100%'>";
echo "<tr style='background:#eee'><th>Gruppe</th><th>Zeit</th><th>Teilnehmer</th><th>x</th><th>y</th><th>z</th><th>z_h</th><th>Status</th><th>Typ</th><th>Einschätzung</th><th>Methodik</th></tr>";

foreach($data as $key=>$set){
  list($gid,$zeit)=explode("|",$key);
  $h_all=[];
  foreach($set as $r){
    $x=mean(array_intersect_key($r,array_flip($kognitiv)));
    $y=mean(array_intersect_key($r,array_flip($sozial)));
    $z=mean(array_intersect_key($r,array_flip($affektiv)));
    $h=mean([$x,$y,$z]); $h_all[]=$h;
  }
  $mh=median($h_all); $mad=mad($h_all); $n=count($h_all);
  $threshold=($n<10)?1.0:(($n<20)?1.5:2.0);
  $trend=false;
  if(isset($prevGroupMeans[$gid])){ if(abs($mh-$prevGroupMeans[$gid])>0.5) $trend=true; }
  $prevGroupMeans[$gid]=$mh;

  foreach($set as $r){
    $tid=$r['teilnehmer_id'];
    $x=mean(array_intersect_key($r,array_flip($kognitiv)));
    $y=mean(array_intersect_key($r,array_flip($sozial)));
    $z=mean(array_intersect_key($r,array_flip($affektiv)));
    $h=mean([$x,$y,$z]); $z_h=($mad>0)?($h-$mh)/$mad:0;
    $flag=(abs($z_h)>=$threshold||$trend);
    $status=$flag?"⚡ Hub":"ok";

    // --- Initialize variables to avoid undefined warnings ---
    $typ = "";
    $einsch = "";
    $meth = "";

    if($flag){
      $eps=0.05;
      if($x>$y+$eps && $x>$z+$eps){
        $typ="kognitiv";
        $einsch="Kognitive Aktivierung – Denkherausforderung.";
        $meth=pickRandom($methodikPool["kognitiv"],3);
      } elseif($y>$x+$eps && $y>$z+$eps){
        $typ="sozial";
        $einsch="Soziale Interaktion – Kooperation und Aushandlung.";
        $meth=pickRandom($methodikPool["sozial"],3);
      } elseif($z>$x+$eps && $z>$y+$eps){
        $typ="affektiv";
        $einsch="Affektive Resonanz – emotionale Involvierung.";
        $meth=pickRandom($methodikPool["affektiv"],3);
      } elseif(abs($z_h)>2.5){
        $typ="systemisch";
        $einsch="Systemische Selbstreferenz – Metareflexion.";
        $meth=pickRandom($methodikPool["systemisch"],3);
      } else {
        $typ="integrativ";
        $einsch="Integrativer Hub – multimodale Kohärenz.";
        $meth=pickRandom($methodikPool["integrativ"],3);
      }

      // summary counts
      if(!isset($summary[$typ])) $summary[$typ]=0;
      $summary[$typ]++;

      // SQL Insert (use prepared statement)
      $insert->execute([
        ":tid"=>$tid, ":gid"=>$gid, ":zeit"=>$zeit,
        ":x"=>$x, ":y"=>$y, ":z"=>$z, ":zh"=>$z_h,
        ":typ"=>$typ, ":einsch"=>$einsch, ":meth"=>$meth
      ]);
    } // end if flag

    echo "<tr>
            <td>".htmlspecialchars($gid)."</td>
            <td>".htmlspecialchars($zeit)."</td>
            <td>".htmlspecialchars($tid)."</td>
            <td style='text-align:center;'>".round($x,2)."</td>
            <td style='text-align:center;'>".round($y,2)."</td>
            <td style='text-align:center;'>".round($z,2)."</td>
            <td style='text-align:center;'>".round($z_h,2)."</td>
            <td style='text-align:center;'>".htmlspecialchars($status)."</td>
            <td>".htmlspecialchars($typ)."</td>
            <td>".htmlspecialchars($einsch)."</td>
            <td>".htmlspecialchars($meth)."</td>
         </tr>";
  }
}
echo "</table>";

// Zusammenfassung
echo "<h3>Zusammenfassung erkannter Hubs (Typen)</h3><ul>";
foreach($summary as $t=>$c){ echo "<li><b>".htmlspecialchars(ucfirst($t))."</b>: $c</li>"; }
echo "</ul><p><b>Alle erkannten Hubs wurden in <code>frzk_hubs</code> gespeichert.</b></p>";
?>
