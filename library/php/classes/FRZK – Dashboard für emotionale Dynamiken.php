<?php
// ===============================================
// 🌐 FRZK – Dashboard für emotionale Dynamiken
// ===============================================

// DB-Verbindung
$pdo = new PDO("mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4", "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// --- Daten laden ---
$sql = "SELECT gruppe_id, zeitpunkt, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);
$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gid = (int)$row['gruppe_id'];
    $t   = $row['zeitpunkt'];
    $js  = json_decode($row['emotions'], true);
    if (!is_array($js)) continue;

    foreach ($js as $block) {
        foreach (($block['wesentliche_emotionen'] ?? []) as $em) {
            $e = $em['emotion'];
            $val = (float)$em['valenz'];
            $act = (float)$em['aktivierung'];
            $score = (float)$em['score'];
            $data[$gid][$t][] = compact('e','val','act','score');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>FRZK – Emotional Dynamics Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>
<style>
body{font-family:system-ui,Arial;margin:20px;background:#fafafa;}
section{margin-bottom:40px;}
canvas, #heatmap, #emergence3D, #network{width:100%;max-height:400px;}
#filters{background:#fff;padding:10px;border-radius:12px;box-shadow:0 0 4px #ccc;margin-bottom:25px;}
</style>
</head>
<body>
<h1>🧠 FRZK – Dashboard für emotionale Dynamiken</h1>

<!-- 🔍 Filter -->
<div id="filters">
  <label>Gruppe:
    <select id="gruppeSel">
      <option value="all">Alle</option>
      <?php foreach(array_keys($data) as $g) echo "<option>$g</option>"; ?>
    </select>
  </label>
</div>

<!-- 1️⃣ Emotionsfrequenzen -->
<section>
  <h2>1️⃣ Emotionsfrequenzanalyse</h2>
  <canvas id="freqChart"></canvas>
</section>

<!-- 2️⃣ Valenz–Aktivierungsraum -->
<section>
  <h2>2️⃣ Valenz–Aktivierungsraum</h2>
  <canvas id="valenzArousal"></canvas>
</section>

<!-- 3️⃣ Dynamische Verläufe -->
<section>
  <h2>3️⃣ Dynamische Emotionsverläufe</h2>
  <canvas id="timeChart"></canvas>
</section>

<!-- 4️⃣ Gruppenemotionale Kohärenz -->
<section>
  <h2>4️⃣ Gruppenemotionale Kohärenz</h2>
  <div id="heatmap"></div>
</section>

<!-- 5️⃣ Semantiknetz -->
<section>
  <h2>5️⃣ Emotionale Semantiknetzwerke</h2>
  <svg id="network" width="800" height="400"></svg>
</section>

<!-- 6️⃣ Emergenzindex -->
<section>
  <h2>6️⃣ Emergenzindex E(g,t)</h2>
  <div id="emergence3D"></div>
</section>

<footer><small>© 2025 FRZK-Projekt | Emotionale Systemdynamik</small></footer>

<script>
// ========== JS-Daten ==========
const raw = <?php echo json_encode($data, JSON_NUMERIC_CHECK); ?>;

// Hilfsfunktionen
function mean(a){return a.length? a.reduce((x,y)=>x+y,0)/a.length:0;}
function std(a){if(a.length<2)return 0;let m=mean(a);return Math.sqrt(a.map(x=>(x-m)**2).reduce((x,y)=>x+y)/(a.length-1));}
function coh(a){let m=mean(a);let s=std(a);return m?1-(s/(m+0.0001)):0;}

// --- 1️⃣ Emotionsfrequenzen ---
const freq={};
for(const g in raw){
  for(const t in raw[g]){
    raw[g][t].forEach(e=>{
      freq[e.e]=(freq[e.e]||0)+1;
    });
  }
}
new Chart(document.getElementById("freqChart"),{
  type:'bar',
  data:{labels:Object.keys(freq),datasets:[{label:'Anzahl',data:Object.values(freq)}]},
  options:{indexAxis:'y',plugins:{legend:{display:false}}}
});

// --- 2️⃣ Valenz–Aktivierung ---
let points=[];
for(const g in raw){
  for(const t in raw[g]){
    raw[g][t].forEach(e=>points.push({x:e.val,y:e.act,label:e.e}));
  }
}
new Chart(document.getElementById("valenzArousal"),{
  type:'scatter',
  data:{datasets:[{label:'Emotionen',data:points}]},
  options:{scales:{x:{title:{text:'Valenz',display:true},min:0,max:1},
                   y:{title:{text:'Aktivierung',display:true},min:0,max:1}}}
});

// --- 3️⃣ Dynamische Verläufe (Valenzmittel) ---
const timeSeries={};
for(const g in raw){
  const times=Object.keys(raw[g]).sort();
  timeSeries[g]={labels:times,vals:times.map(t=>mean(raw[g][t].map(e=>e.val)))};
}
new Chart(document.getElementById("timeChart"),{
  type:'line',
  data:{labels:timeSeries[Object.keys(timeSeries)[0]].labels,
        datasets:Object.entries(timeSeries).map(([g,v])=>({label:`Gruppe ${g}`,data:v.vals}))},
  options:{scales:{y:{min:0,max:1}}}
});

// --- 4️⃣ Heatmap Kohärenz ---
const groups=Object.keys(raw);
const times=[...new Set(groups.flatMap(g=>Object.keys(raw[g])))].sort();
const z=groups.map(g=>times.map(t=>coh(raw[g][t].map(e=>e.score))));
Plotly.newPlot('heatmap',[{z,x:times,y:groups,type:'heatmap',colorscale:'Viridis'}],
  {title:'Kohärenzindex',height:400});

// --- 5️⃣ D3-Semantiknetz (Co-Occurrence) ---
const nodes={},links={};
for(const g in raw){for(const t in raw[g]){
  const emos=raw[g][t].map(e=>e.e);
  for(let i=0;i<emos.length;i++)for(let j=i+1;j<emos.length;j++){
    const a=emos[i],b=emos[j];const key=a+"-"+b;
    links[key]=(links[key]||{source:a,target:b,value:0});links[key].value++;
    nodes[a]=nodes[a]||{id:a};nodes[b]=nodes[b]||{id:b};
  }
}}
const svg=d3.select("#network"),width=+svg.attr("width"),height=+svg.attr("height");
const linkArr=Object.values(links),nodeArr=Object.values(nodes);
const simulation=d3.forceSimulation(nodeArr)
  .force("link",d3.forceLink(linkArr).id(d=>d.id).distance(80))
  .force("charge",d3.forceManyBody().strength(-100))
  .force("center",d3.forceCenter(width/2,height/2));
const link=svg.append("g").selectAll("line").data(linkArr).enter().append("line")
  .attr("stroke","#999").attr("stroke-width",d=>Math.sqrt(d.value));
const node=svg.append("g").selectAll("circle").data(nodeArr).enter().append("circle")
  .attr("r",6).attr("fill","steelblue").call(d3.drag()
    .on("start",dragstarted).on("drag",dragged).on("end",dragended));
node.append("title").text(d=>d.id);
simulation.on("tick",()=>{
  link.attr("x1",d=>d.source.x).attr("y1",d=>d.source.y)
      .attr("x2",d=>d.target.x).attr("y2",d=>d.target.y);
  node.attr("cx",d=>d.x).attr("cy",d=>d.y);
});
function dragstarted(e,d){if(!e.active)simulation.alphaTarget(0.3).restart();d.fx=d.x;d.fy=d.y;}
function dragged(e,d){d.fx=e.x;d.fy=e.y;}
function dragended(e,d){if(!e.active)simulation.alphaTarget(0);d.fx=null;d.fy=null;}

// --- 6️⃣ Emergenzindex E(g,t) ---
const z2=groups.map(g=>times.map(t=>{
  const v=mean(raw[g][t].map(e=>e.val));
  const c=coh(raw[g][t].map(e=>e.score));
  return (v*c).toFixed(2);
}));
Plotly.newPlot('emergence3D',[{z:z2,x:times,y:groups,type:'surface',colorscale:'Viridis'}],
  {title:'Emergenzindex E(g,t)',height:500});
</script>
</body>
</html>
