<?php
// ==========================================================================
// fill_frzk_group_semantische_dichte.php
// Aggregation der FRZK-Werte auf Gruppenebene
// + Transitions (frzk_group_transitions)
// + Reflexion (frzk_group_reflexion)
// + Loops (frzk_group_loops)
// ==========================================================================

header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ERROR);

$pdo = new PDO("mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "🚀 Starte Aggregation: Gruppen-Semantik, Transitions, Reflexion & Loops\n";

// --------------------------------------------------------------------------
// 1️⃣ Basisstruktur
// --------------------------------------------------------------------------
$pdo->exec("TRUNCATE frzk_group_semantische_dichte");
$pdo->exec("
INSERT INTO frzk_group_semantische_dichte (ue_id, gruppe_id, zeitpunkt)
SELECT id, gruppe_id, CONCAT(datum, ' ', zeit)
FROM ue_unterrichtseinheit
WHERE datum IS NOT NULL AND zeit IS NOT NULL
");
echo "✅ Basisstruktur erzeugt.\n";

// --------------------------------------------------------------------------
// 2️⃣ Gruppen-Semantik
// --------------------------------------------------------------------------
$rows = $pdo->query("SELECT * FROM frzk_group_semantische_dichte ORDER BY id")->fetchAll();
foreach ($rows as $r) {
    $gid = (int)$r["gruppe_id"];
    $zeit = $r["zeitpunkt"];
    $id = (int)$r["id"];

    $vals = $pdo->query("
        SELECT * FROM frzk_semantische_dichte
        WHERE gruppe_id=$gid AND zeitpunkt='$zeit'
    ")->fetchAll();
    $n = count($vals);
    if (!$n) continue;

    $x = array_sum(array_column($vals,"x_kognition"))/$n;
    $y = array_sum(array_column($vals,"y_sozial"))/$n;
    $z = array_sum(array_column($vals,"z_affektiv"))/$n;
    $h = ($x+$y+$z)/3;

    $prev = $pdo->prepare("SELECT h_bedeutung FROM frzk_group_semantische_dichte
                            WHERE gruppe_id=:g AND zeitpunkt<:t
                            ORDER BY zeitpunkt DESC LIMIT 1");
    $prev->execute([":g"=>$gid,":t"=>$zeit]);
    $prevH = ($p=$prev->fetch()) ? (float)$p["h_bedeutung"] : $h;
    $dh = $h - $prevH;

    $mean = ($x+$y+$z)/3;
    $var = (($x-$mean)**2+($y-$mean)**2+($z-$mean)**2)/3;
    $stab = max(0,1-$var);

    if ($h < 1.5) $cl=1;
    elseif ($h < 2.2) $cl=2;
    else $cl=3;

    $a = abs($dh);
    if ($a<0.05)$mark="⚖️ Homöostatisch";
    elseif($a<0.15)$mark="🌱 Adaptiv";
    elseif($a<0.3)$mark="🔄 Koordinativ";
    elseif($a<0.5)$mark="🌊 Transformativ";
    elseif($a<0.8)$mark="⚡ Perturbativ";
    else $mark="💥 Kollapsiv";
    if($stab<0.3&&$a>0.3)$mark.=" (instabil)";
    elseif($stab>0.8&&$a<0.1)$mark.=" (resilient)";

    $pdo->prepare("
        UPDATE frzk_group_semantische_dichte
        SET anz_tn=:n,x_kognition=:x,y_sozial=:y,z_affektiv=:z,
            h_bedeutung=:h,dh_dt=:dh,stabilitaet_score=:s,
            cluster_id=:c,transitions_marker=:m
        WHERE id=:id
    ")->execute([
        ":n"=>$n,":x"=>$x,":y"=>$y,":z"=>$z,":h"=>$h,
        ":dh"=>$dh,":s"=>$stab,":c"=>$cl,":m"=>$mark,":id"=>$id
    ]);
}
echo "✅ Gruppen-Semantik berechnet.\n";

// --------------------------------------------------------------------------
// 3️⃣ Transitions (siehe vorherige Version)
// --------------------------------------------------------------------------
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_transitions (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 zeitpunkt DATETIME NOT NULL,
 von_cluster INT,
 nach_cluster INT,
 delta_h FLOAT,
 delta_stabilitaet FLOAT,
 transition_typ VARCHAR(50),
 transition_intensitaet FLOAT,
 marker VARCHAR(10),
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$rows = $pdo->query("SELECT * FROM frzk_group_semantische_dichte ORDER BY gruppe_id, zeitpunkt")->fetchAll();
$groups=[];
foreach($rows as $r)$groups[$r["gruppe_id"]][]=$r;

$ins=$pdo->prepare("INSERT INTO frzk_group_transitions
(gruppe_id,zeitpunkt,von_cluster,nach_cluster,delta_h,delta_stabilitaet,
 transition_typ,transition_intensitaet,marker,bemerkung)
VALUES(:g,:t,:v,:n,:dh,:ds,:typ,:inten,:m,:bem)");
$cT=0;
foreach($groups as $gid=>$rws){
 if(count($rws)<2)continue;
 for($i=1;$i<count($rws);$i++){
  $p=$rws[$i-1];$c=$rws[$i];
  $dh=(float)$c["h_bedeutung"]-(float)$p["h_bedeutung"];
  $ds=(float)$c["stabilitaet_score"]-(float)$p["stabilitaet_score"];
  $v=(int)$p["cluster_id"];$n=(int)$c["cluster_id"];
  $inten=min(1,(abs($dh)+abs($ds))/2);
  if($n!==$v&&$dh>0.5){$typ="Sprung";$m="🚀";}
  elseif($dh>0.4&&$ds>0){$typ="Stabilisierung";$m="🌀";}
  elseif($dh<-0.4&&$ds<0){$typ="Destabilisierung";$m="⚡";}
  elseif(abs($dh)<0.2&&abs($ds)<0.1){$typ="Neutral";$m="•";}
  else{$typ="Rückkopplung";$m="🔄";}
  $bem=sprintf("Δh=%.3f Δstab=%.3f Cluster %d→%d Typ=%s Intensität=%.2f",
                $dh,$ds,$v,$n,$typ,$inten);
  $ins->execute([":g"=>$gid,":t"=>$c["zeitpunkt"],":v"=>$v,":n"=>$n,
                 ":dh"=>$dh,":ds"=>$ds,":typ"=>$typ,":inten"=>$inten,
                 ":m"=>$m,":bem"=>$bem]);
  $cT++;
 }}
echo "✅ Gruppentransitionen berechnet ($cT)\n";

// --------------------------------------------------------------------------
// 4️⃣ Reflexion
// --------------------------------------------------------------------------
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_reflexion (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 zeitpunkt DATETIME NOT NULL,
 reflexionsgrad FLOAT,
 meta_kohärenz FLOAT,
 selbstbezug_index FLOAT,
 reflexions_marker VARCHAR(20),
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$rows=$pdo->query("SELECT * FROM frzk_group_semantische_dichte ORDER BY gruppe_id, zeitpunkt")->fetchAll();
$groups=[];
foreach($rows as $r)$groups[$r["gruppe_id"]][]=$r;

$ins=$pdo->prepare("INSERT INTO frzk_group_reflexion
(gruppe_id,zeitpunkt,reflexionsgrad,meta_kohärenz,selbstbezug_index,reflexions_marker,bemerkung)
VALUES(:g,:t,:grad,:meta,:self,:mark,:bem)");
$cR=0;
$selfWords=["selbst","ich","bewusst","reflexion","identität","zweifel","motivation","vertrauen"];
foreach($groups as $gid=>$entries){
 $dhVals=array_column($entries,"dh_dt");
 $mDh=array_sum($dhVals)/max(1,count($dhVals));
 $varDh=array_sum(array_map(fn($v)=>pow($v-$mDh,2),$dhVals))/max(1,count($dhVals));
 $meta=max(0,1-$varDh);
 $stab=array_sum(array_column($entries,"stabilitaet_score"))/max(1,count($entries));
 $selfIndex=0; // placeholder (wie vorher)
 $grad=0.6*$stab+0.4*$selfIndex;
 $mark=$grad<0.33?"niedrig":($grad<0.66?"mittel":"hoch");
 $bem=sprintf("Reflexionsgrad %.2f | Meta-Kohärenz %.2f | Stabilität %.2f",$grad,$meta,$stab);
 $ins->execute([":g"=>$gid,":t"=>end($entries)["zeitpunkt"],":grad"=>$grad,
                ":meta"=>$meta,":self"=>$selfIndex,":mark"=>$mark,":bem"=>$bem]);
 $cR++;
}
echo "✅ Gruppenreflexion berechnet ($cR)\n";

// --------------------------------------------------------------------------
// 5️⃣ Group Loops
// --------------------------------------------------------------------------
echo "→ Analysiere Gruppenschleifen (frzk_group_loops)...\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_loops (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 loop_start DATETIME NOT NULL,
 loop_ende DATETIME NOT NULL,
 dauer INT,
 typ VARCHAR(30),
 intensitaet FLOAT,
 zyklus_muster TEXT,
 marker VARCHAR(10),
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$data=$pdo->query("SELECT * FROM frzk_group_semantische_dichte ORDER BY gruppe_id, zeitpunkt")->fetchAll();
$groups=[];
foreach($data as $d)$groups[$d["gruppe_id"]][]=$d;

$ins=$pdo->prepare("INSERT INTO frzk_group_loops
(gruppe_id,loop_start,loop_ende,dauer,typ,intensitaet,zyklus_muster,marker,bemerkung)
VALUES(:g,:s,:e,:d,:t,:i,:z,:m,:bem)");
$cL=0;
foreach($groups as $gid=>$entries){
 $count=count($entries);
 if($count<3)continue;
 $clusters=array_column($entries,"cluster_id");
 $hs=array_column($entries,"h_bedeutung");
 $zs=implode("→",$clusters);
 for($i=2;$i<$count;$i++){
  $dh1=$hs[$i-2]-$hs[$i-1];
  $dh2=$hs[$i-1]-$hs[$i];
  if($dh1*$dh2<0 && abs($dh1)>0.2 && abs($dh2)>0.2){
    $typ="Oszillation";$mark="🔁";
  } elseif($clusters[$i]==$clusters[$i-2]){
    $typ="Rückkopplung";$mark="🔄";
  } elseif(abs($hs[$i]-$hs[$i-2])<0.1){
    $typ="Attraktor";$mark="🌀";
  } elseif(abs($dh1)>0.3&&abs($dh2)>0.3&&$dh1*$dh2<0){
    $typ="Pendelbewegung";$mark="⚖️";
  } elseif(abs($dh1)<0.05&&abs($dh2)<0.05){
    $typ="Plateau";$mark="🪶";
  } else continue;

  $inten=min(1,(abs($dh1)+abs($dh2))/2);
  $start=$entries[$i-2]["zeitpunkt"];
  $ende=$entries[$i]["zeitpunkt"];
  $dauer=$i-($i-2);
  $bem=sprintf("Loop %s Δh1=%.2f Δh2=%.2f Intensität=%.2f",$typ,$dh1,$dh2,$inten);
  $ins->execute([":g"=>$gid,":s"=>$start,":e"=>$ende,":d"=>$dauer,
                 ":t"=>$typ,":i"=>$inten,":z"=>$zs,":m"=>$mark,":bem"=>$bem]);
  $cL++;
 }
}
echo "✅ Group Loops berechnet ($cL Einträge)\n";

// ==========================================================================
// 7️⃣ Gruppen-Interdependenz
// ==========================================================================
echo "→ Berechne frzk_group_interdependenz...\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_interdependenz (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 zeitpunkt DATETIME NOT NULL,
 x_kognition FLOAT,
 y_sozial FLOAT,
 z_affektiv FLOAT,
 h_bedeutung FLOAT,
 korrelationsscore FLOAT,
 kohärenz_index FLOAT,
 varianz_xyz FLOAT,
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$data = $pdo->query("SELECT * FROM frzk_group_semantische_dichte ORDER BY gruppe_id, zeitpunkt")->fetchAll();
$groups = [];
foreach ($data as $r) $groups[$r["gruppe_id"]][] = $r;

$ins = $pdo->prepare("INSERT INTO frzk_group_interdependenz
(gruppe_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung,
 korrelationsscore, kohärenz_index, varianz_xyz, bemerkung)
VALUES (:g,:t,:x,:y,:z,:h,:corr,:koh,:var,:bem)");

$cI = 0;
foreach ($groups as $gid => $rows) {
    foreach ($rows as $r) {
        $x = (float)$r["x_kognition"];
        $y = (float)$r["y_sozial"];
        $z = (float)$r["z_affektiv"];
        $h = (float)$r["h_bedeutung"];

        $corr = ($x*$y + $y*$z + $x*$z) / 3.0;
        $koh  = 1 - (abs($x-$y) + abs($y-$z) + abs($x-$z)) / 9.0;
        if ($koh < 0) $koh = 0;
        $mean = ($x + $y + $z) / 3.0;
        $var  = (($x-$mean)**2 + ($y-$mean)**2 + ($z-$mean)**2) / 3.0;

        $bem = sprintf("x=%.2f y=%.2f z=%.2f h=%.2f | Corr=%.3f Koh=%.3f Var=%.3f",
                       $x,$y,$z,$h,$corr,$koh,$var);

        $ins->execute([
            ":g"=>$gid, ":t"=>$r["zeitpunkt"], ":x"=>$x, ":y"=>$y, ":z"=>$z, ":h"=>$h,
            ":corr"=>$corr, ":koh"=>$koh, ":var"=>$var, ":bem"=>$bem
        ]);
        $cI++;
    }
}
echo "✅ frzk_group_interdependenz erstellt ($cI Einträge)\n";

// ==========================================================================
// 8️⃣ Gruppen-Operatoren (σ, M, R, E)
// ==========================================================================
echo "→ Berechne frzk_group_operatoren...\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_operatoren (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 zeitpunkt DATETIME NOT NULL,
 x_kognition FLOAT,
 y_sozial FLOAT,
 z_affektiv FLOAT,
 h_bedeutung FLOAT,
 dh_dt FLOAT,
 stabilitaet_score FLOAT,
 operator_sigma FLOAT,
 operator_meta FLOAT,
 operator_resonanz FLOAT,
 operator_emer FLOAT,
 operator_level FLOAT,
 dominanter_operator VARCHAR(20),
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Emotionen-Kategorisierung laden
$emoMap = [];
$stmt = $pdo->query("SELECT emotion, type_name FROM _mtr_emotionen");
while ($row = $stmt->fetch()) {
    $emoMap[strtolower(trim($row["emotion"]))] = strtolower(trim($row["type_name"]));
}

$rows = $pdo->query("
    SELECT gruppe_id, zeitpunkt, x_kognition AS x, y_sozial AS y, z_affektiv AS z,
           h_bedeutung AS h, dh_dt, stabilitaet_score, emotions
    FROM frzk_group_semantische_dichte
    ORDER BY gruppe_id, zeitpunkt
")->fetchAll();

$ins = $pdo->prepare("INSERT INTO frzk_group_operatoren
(gruppe_id, zeitpunkt, x_kognition, y_sozial, z_affektiv, h_bedeutung, dh_dt, stabilitaet_score,
 operator_sigma, operator_meta, operator_resonanz, operator_emer, operator_level, dominanter_operator, bemerkung)
VALUES (:g,:t,:x,:y,:z,:h,:dh,:stab,:sig,:meta,:res,:emer,:lvl,:dom,:bem)");

$cO = 0;
foreach ($rows as $r) {
    $gid  = (int)$r["gruppe_id"];
    $x    = (float)$r["x"];
    $y    = (float)$r["y"];
    $z    = (float)$r["z"];
    $h    = (float)$r["h"];
    $dh   = (float)$r["dh_dt"];
    $stab = (float)$r["stabilitaet_score"];
    $emoStr = strtolower((string)$r["emotions"]);
    $emos = array_filter(array_map("trim", explode(",", $emoStr)));

    $types = [];
    foreach ($emos as $e) if (isset($emoMap[$e])) $types[] = $emoMap[$e];

    $sigma = min(1, $x / 3 + (in_array("kognitiv", $types) ? 0.3 : 0));
    $pos   = count(array_filter($types, fn($t)=>$t=="positiv"));
    $neg   = count(array_filter($types, fn($t)=>$t=="negativ"));
    $meta  = ($pos && $neg ? 0.7 : 0.3) + max(0,(1-$stab)/2);
    $res   = min(1, $y / 3 + ($stab > 0.7 ? 0.2 : 0));
    $emer  = min(1, abs($dh) + ($z > 1.5 ? 0.2 : 0));
    $lvl   = ($sigma + $meta + $res + $emer) / 4;
    $ops   = ["σ"=>$sigma, "M"=>$meta, "R"=>$res, "E"=>$emer];
    arsort($ops);
    $dom = array_key_first($ops);

    $bem = sprintf("σ=%.2f M=%.2f R=%.2f E=%.2f | Level=%.2f dh/dt=%.3f",
                   $sigma,$meta,$res,$emer,$lvl,$dh);

    $ins->execute([
        ":g"=>$gid, ":t"=>$r["zeitpunkt"], ":x"=>$x, ":y"=>$y, ":z"=>$z,
        ":h"=>$h, ":dh"=>$dh, ":stab"=>$stab, ":sig"=>$sigma, ":meta"=>$meta,
        ":res"=>$res, ":emer"=>$emer, ":lvl"=>$lvl, ":dom"=>$dom, ":bem"=>$bem
    ]);
    $cO++;
}
echo "✅ frzk_group_operatoren erstellt ($cO Einträge)\n";

// ==========================================================================
// 9️⃣ JSON-Exporte für die neuen Tabellen
// ==========================================================================
foreach (["frzk_group_interdependenz","frzk_group_operatoren"] as $t) {
    $rows = $pdo->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__."/$t.json", json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "📄 Exportiert: $t (" . count($rows) . " Einträge)\n";
}
// ==========================================================================
// 🔟 Gruppen-Hubs (Granulare Klassifikation σ–M–R–E)
// ==========================================================================
echo "→ Berechne frzk_group_hubs (granular)...\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_group_hubs (
 id INT AUTO_INCREMENT PRIMARY KEY,
 gruppe_id INT NOT NULL,
 zeitpunkt DATETIME NOT NULL,
 operator_sigma FLOAT,
 operator_meta FLOAT,
 operator_resonanz FLOAT,
 operator_emer FLOAT,
 stabilitaet_score FLOAT,
 hub_score FLOAT,
 hub_typ VARCHAR(80),
 bedeutungszentrum VARCHAR(120),
 bemerkung TEXT,
 INDEX(gruppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$rows = $pdo->query("SELECT * FROM frzk_group_operatoren ORDER BY gruppe_id, zeitpunkt")->fetchAll();

$grouped = [];
foreach ($rows as $r) $grouped[$r["gruppe_id"]][] = $r;
$insHub = $pdo->prepare("
INSERT INTO frzk_group_hubs
(gruppe_id, zeitpunkt, operator_sigma, operator_meta, operator_resonanz, operator_emer,
 stabilitaet_score, hub_score, hub_typ, bedeutungszentrum, bemerkung)
 
VALUES (:gid, :zeit, :sig, :meta, :res, :emer, :stab, :score, :typ, :bz, :bem)
");
/*
$insHub = $pdo->prepare("
INSERT INTO frzk_group_hubs
(gruppe_id, zeitpunkt, operator_sigma, operator_meta, operator_resonanz, operator_emer,
 stabilitaet_score, hub_score, hub_typ, bedeutungszentrum, bemerkung)
VALUES (:gid, :zeit, :sig, :meta, :res, :emer, :stab, :score, :typ, :bz, :bem)
ON DUPLICATE KEY UPDATE
 operator_sigma = VALUES(operator_sigma),
 operator_meta = VALUES(operator_meta),
 operator_resonanz = VALUES(operator_resonanz),
 operator_emer = VALUES(operator_emer),
 stabilitaet_score = VALUES(stabilitaet_score),
 hub_score = VALUES(hub_score),
 hub_typ = VALUES(hub_typ),
 bedeutungszentrum = VALUES(bedeutungszentrum),
 bemerkung = VALUES(bemerkung)
");
*/
$countH = 0;
foreach ($grouped as $gid => $entries) {
    foreach ($entries as $e) {
        $σ = (float)$e["operator_sigma"];
        $M = (float)$e["operator_meta"];
        $R = (float)$e["operator_resonanz"];
        $E = (float)$e["operator_emer"];
        $stab = (float)$e["stabilitaet_score"];
        $score = ($σ + $M + $R + $E) / 4;

        // --- granulare Klassifikation ---
// --- tiefere granulare Klassifikation ---
if ($σ > 0.85 && $R > 0.75 && $stab > 0.8) {
    $typ = "Resonant-Kohärent (hyperstabil)";
    $bz  = "Kognitiv-sozialer Integrationskern – Primärkohärenz";
} elseif ($σ > 0.75 && $R > 0.6 && $stab > 0.7) {
    $typ = "Resonant-Kohärent (stabil)";
    $bz  = "Kognitiv-sozialer Integrationskern – Sekundärfeld";
} elseif ($E > 0.9 && $M > 0.7 && $stab < 0.6) {
    $typ = "Emergent-Synergisch (hyperdynamisch)";
    $bz  = "Transformationszentrum – Selbstorganisationsknoten";
} elseif ($E > 0.8 && $M > 0.6) {
    $typ = "Emergent-Synergisch (hochdynamisch)";
    $bz  = "Transformationszentrum – Innovationscluster";
} elseif ($M > 0.8 && $stab > 0.75 && $σ > 0.6) {
    $typ = "Meta-Semantisch (reflexiv-stabil)";
    $bz  = "Meta-Koordinationszentrum – Strukturkern";
} elseif ($M > 0.7 && $stab > 0.6) {
    $typ = "Meta-Semantisch (reflexiv)";
    $bz  = "Meta-Koordinationszentrum – Prozessknoten";
} elseif ($σ > 0.8 && $E < 0.3 && $R < 0.5) {
    $typ = "Semantisch-Fokussiert (analytisch)";
    $bz  = "Kognitives Bedeutungszentrum – Wissenskern";
} elseif ($σ > 0.75 && $E < 0.4) {
    $typ = "Semantisch-Fokussiert (kognitiv)";
    $bz  = "Kognitives Bedeutungszentrum – Diskursfeld";
} elseif ($R > 0.85 && $stab > 0.8 && $E < 0.3) {
    $typ = "Sozial-Resonant (meta-homöostatisch)";
    $bz  = "Soziales Kohärenzfeld – Primärstruktur";
} elseif ($R > 0.75 && $stab > 0.8 && $E < 0.3) {
    $typ = "Sozial-Resonant (homöostatisch)";
    $bz  = "Soziales Kohärenzfeld – Sekundärstruktur";
} elseif ($E > 0.7 && $stab < 0.4) {
    $typ = "Perturbativ-Instabil (Übergangsphase)";
    $bz  = "Instabiler Übergang – Turbulenzfeld";
} elseif ($score > 0.9 && $σ > 0.8 && $M > 0.8 && $R > 0.8 && $E > 0.8) {
    $typ = "Kohärent-Emergent (integral)";
    $bz  = "Bedeutungs-Superhub – holarchisches Zentrum";
} elseif ($score > 0.8 && $σ > 0.7 && $M > 0.7 && $R > 0.7 && $E > 0.7) {
    $typ = "Kohärent-Emergent (integrativ)";
    $bz  = "Bedeutungs-Superhub – intermediäres Zentrum";
} else {
    // feinere Tendenzen
    $maxOp = max($σ, $M, $R, $E);
    $delta = max(abs($σ-$M), abs($M-$R), abs($R-$E)); // Maß für Divergenz
    $dom = "";
    switch ($maxOp) {
        case $σ:
            $dom = ($delta < 0.2) ? "Semantisch-Kohärent" : "Semantisch-Tendenziell";
            $bz  = ($delta < 0.2) ? "Kognitiver Knoten – balanciert" : "Kognitiver Knoten – fokussiert";
            break;
        case $M:
            $dom = ($stab > 0.7) ? "Meta-Stabil" : "Reflexiv-Tendenziell";
            $bz  = ($stab > 0.7) ? "Meta-Knoten – regulativ" : "Meta-Knoten – explorativ";
            break;
        case $R:
            $dom = ($stab > 0.7) ? "Sozial-Stabil" : "Sozial-Tendenziell";
            $bz  = ($stab > 0.7) ? "Sozialer Knoten – kohärent" : "Sozialer Knoten – adaptiv";
            break;
        case $E:
            $dom = ($stab < 0.5) ? "Emergent-Volatil" : "Emergent-Tendenziell";
            $bz  = ($stab < 0.5) ? "Affektiver Integrator – instabil" : "Affektiver Integrator – stabilisierend";
            break;
        default:
            $dom = "Neutral";
            $bz  = "Kein dominanter Schwerpunkt";
    }
    $typ = $dom;
}

        $bem = sprintf(
            "σ=%.2f M=%.2f R=%.2f E=%.2f | Score=%.2f Typ=%s Stab=%.2f",
            $σ,$M,$R,$E,$score,$typ,$stab
        );
try {
        $insHub->execute([
            ":gid"=>$gid,
            ":zeit"=>$e["zeitpunkt"],
            ":sig"=>$σ,
            ":meta"=>$M,
            ":res"=>$R,
            ":emer"=>$E,
            ":stab"=>$stab,
            ":score"=>$score,
            ":typ"=>$typ,
            ":bz"=>$bz,
            ":bem"=>$bem
        ]);
    
} catch (Exception $e) {
    
}
        $countH++;
    }
}

echo "✅ frzk_group_hubs erstellt ($countH Einträge, granular klassifiziert)\n";

$rows = $pdo->query("SELECT * FROM frzk_group_hubs")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__."/frzk_group_hubs.json", json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "📄 Exportiert: frzk_group_hubs (" . count($rows) . " Einträge)\n";

$sql="select ue_id from frzk_group_semantische_dichte";
$rows = $pdo->query($sql)->fetchAll();

foreach ($rows as $r) {
    $sql = "SELECT frzk_group_semantische_dichte.* FROM frzk_group_semantische_dichte order by id";
    $rows_tn = $pdo->query($sql)->fetchAll();
    $l = count( $rows_tn );
    $i = 0;
    while( $i < $l ) {
        $sql_sd_tn = "select count(id) as anz_tn, avg(x_kognition) as x_kognition, avg(y_sozial) as y_sozial, avg(z_affektiv) as z_affektiv from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
        $rows_sd_tn = $pdo->query($sql_sd_tn)->fetchAll();
        //$pdo->exec("update frzk_tmp_group_semantische_dichte set anz_tn=" . $rows_sd_tn[0]["anz_tn"] . ", x_kognition=" . $rows_sd_tn[0]["x_kognition"] . ", y_sozial=" . $rows_sd_tn[0]["y_sozial"] . ", z_affektiv=" . $rows_sd_tn[0]["z_affektiv"] . "  where id=" . $rows_tn[$i]["id"]);
        $tnIds = "";
        /*
        foreach ($rows_sd_tn as $sd_tn) {
            $tnIds .= $sd_tn["teilnehmer_id"] . ",";
        }
        $tnIds = substr($tnIds, 0, -1);
        */
        $sql_sd_em = "select emotions from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
        $rows_sd_em = $pdo->query($sql_sd_em)->fetchAll();
        $tnEmotions = "";
        foreach ($rows_sd_em as $sd_em) {
            $tnEmotions .= $sd_em["emotions"] . ",";
        }
        $tnEmotions = substr($tnEmotions, 0, -1);
        $tnEmotionsArr = explode( ",", $tnEmotions );
            $stmt = $pdo->query("SELECT id, emotion, valenz, aktivierung FROM _mtr_emotionen");
            $emotionMatrix = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $emotionMatrix[(int)$row['id']] = [
                    'emotion' => $row['emotion'],
                    'valenz' => (float)$row['valenz'],
                    'aktivierung' => (float)$row['aktivierung'],
                ];
            }

            // --- 2) Schwellenwerte für „wesentliche“ Emotionen ---
            $minValenz = 0.7;
            $minAktivierung = 0.5;
            $datensaetze[$rows_tn["id"]]['emotionen']=$tnEmotionsArr;
            // --- 3) JSON-Ausgabe vorbereiten ---
            $ergebnisse = [];

            foreach ($datensaetze as $datensatz) {
                $alle = $datensatz['emotionen'];
                $anzahl = array_count_values($alle);

                $wesentliche = [];

                foreach ($anzahl as $id => $count) {
                    if (!isset($emotionMatrix[$id])) continue;
                    $val = $emotionMatrix[$id]['valenz'];
                    $act = $emotionMatrix[$id]['aktivierung'];

                    // Bedingung: mehrfach & hohe Gewichtung
                    if (/*$count > 1 && */$val >= $minValenz && $act >= $minAktivierung) {
                        $wesentliche[] = [
                            'id' => $id,
                            'emotion' => $emotionMatrix[$id]['emotion'],
                            'anzahl' => $count,
                            'valenz' => $val,
                            'aktivierung' => $act,
                            'score' => ($val + $act) / 2
                        ];
                    }
                }

                $ergebnisse[] = [
                    //'datensatz_id' => $rows_tn[$i]["id"],
                    //'gruppe_id' => $datensatz['gruppe_id'],
                    'alle_emotionen' => $alle,
                    'anzahl_emotionen' => $anzahl,
                    'wesentliche_emotionen' => $wesentliche
                ];
            }
            $js = json_encode( $ergebnisse );
            $pdo->exec("update frzk_group_semantische_dichte set emotions ='" . json_encode( $ergebnisse ) . "' where id=" . $rows_tn[$i]["id"]);
        $i += 1;
    }
    if( $tnIds != "") {
    }
}

echo "✅ Aggregation abgeschlossen: {$written} Datensätze aktualisiert.\n";
echo "📄 JSON exportiert: frzk_tmp_group_semantische_dichte.json\n";
// --------------------------------------------------------------------------
// 6️⃣ JSON Exporte
// --------------------------------------------------------------------------
foreach(["frzk_group_semantische_dichte","frzk_group_transitions",
         "frzk_group_reflexion","frzk_group_loops"] as $t){
 $rows=$pdo->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC);
 file_put_contents(__DIR__."/$t.json",json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo "📄 Exportiert: $t (" . count($rows) . " Einträge)\n";
}

echo "🏁 Fertig: Alle Gruppendynamiken (Semantik + Transition + Reflexion + Loops) berechnet.\n";
?>
