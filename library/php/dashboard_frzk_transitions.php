<?php
// dashboard_frzk_transitions.php
// Interaktives Dashboard für frzk_transitions (Heatmap, Typ-Verteilung, Δh-Verlauf, Netzwerk)
// Benötigt: frzk_transitions, frzk_semantische_dichte (für Δh-Verläufe).
// Läuft in XAMPP / PHP 8+ ohne zusätzliche Libraries (Chart.js & vis.js via CDN).

header('Content-Type: text/html; charset=utf-8');

// --- DB-Verbindung anpassen falls nötig ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- 1) Transitions laden ---
$transStmt = $pdo->query("SELECT * FROM frzk_transitions ORDER BY zeitpunkt ASC");
$transitions = $transStmt->fetchAll();

// --- 2) Liste aller teilnehmer zur Auswahl (aus frzk_semantische_dichte) ---
$partStmt = $pdo->query("SELECT DISTINCT teilnehmer_id FROM frzk_semantische_dichte ORDER BY teilnehmer_id");
$participants = $partStmt->fetchAll(PDO::FETCH_COLUMN);

// --- 3) Erzeuge Matrix- und Typ-Statistik (aggregiert) ---
$matrix = [];          // matrix[von][nach] = count
$typeStats = [];       // type => count
$edgeWeights = [];     // for network: from->to weight and avg intensity
$intensitySums = [];
$intensityCounts = [];

foreach ($transitions as $t) {
    $von = (int)$t['von_cluster'];
    $nach = (int)$t['nach_cluster'];
    $typ = $t['transition_typ'] ?? 'Unknown';
    $inten = floatval($t['transition_intensitaet']);

    if (!isset($matrix[$von])) $matrix[$von] = [];
    if (!isset($matrix[$von][$nach])) $matrix[$von][$nach] = 0;
    $matrix[$von][$nach]++;

    if (!isset($typeStats[$typ])) $typeStats[$typ] = 0;
    $typeStats[$typ]++;

    // network accumulators
    $key = $von . '->' . $nach;
    if (!isset($edgeWeights[$key])) $edgeWeights[$key] = 0;
    if (!isset($intensitySums[$key])) $intensitySums[$key] = 0;
    if (!isset($intensityCounts[$key])) $intensityCounts[$key] = 0;
    $edgeWeights[$key] += 1;
    $intensitySums[$key] += $inten;
    $intensityCounts[$key] += 1;
}

// compute avg intensities per edge
$edges = [];
foreach ($edgeWeights as $k => $count) {
    list($from, $to) = explode('->', $k);
    $avgInt = $intensityCounts[$k] ? $intensitySums[$k] / $intensityCounts[$k] : 0;
    $edges[] = [
        'from' => (int)$from,
        'to' => (int)$to,
        'count' => $count,
        'avgInt' => round($avgInt, 3)
    ];
}

// --- 4) Prepare JSON blobs for front-end ---
$matrixJson = json_encode($matrix, JSON_UNESCAPED_UNICODE);
$typeStatsJson = json_encode($typeStats, JSON_UNESCAPED_UNICODE);
$edgesJson = json_encode($edges, JSON_UNESCAPED_UNICODE);
$participantsJson = json_encode($participants, JSON_UNESCAPED_UNICODE);
$transitionsJson = json_encode($transitions, JSON_UNESCAPED_UNICODE);

?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>FRZK Transitions Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <!-- vis.js for network graph -->
  <script type="text/javascript" src="https://unpkg.com/vis-network@9.1.2/dist/vis-network.min.js"></script>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:16px;background:#fafafa;color:#222}
    h1{font-size:20px;margin-bottom:6px}
    .row{display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e3e3e3;border-radius:8px;padding:12px;box-shadow:0 1px 2px rgba(0,0,0,0.03)}
    .wide{flex:1 1 720px}
    .narrow{flex:0 1 320px}
    table.heat{border-collapse:collapse}
    table.heat td, table.heat th{padding:6px 8px;border:1px solid #eee;text-align:center}
    .controls{display:flex;gap:10px;align-items:center;margin-bottom:8px}
    .small{font-size:12px;color:#666}
    #network{height:360px;width:100%}
    .legend{display:flex;gap:8px;align-items:center;margin-top:8px}
    .legend .box{width:18px;height:14px;border-radius:3px}
    footer{margin-top:18px;font-size:12px;color:#666}
  </style>
</head>
<body>

<h1>FRZK Transitions Dashboard</h1>
<p class="small">Heatmap (Cluster → Cluster), Typverteilung, Δh-Verlauf pro Teilnehmer und Phasenraum-Netzwerk.</p>

<div class="controls card">
  <label for="filterType">Transitionstyp:</label>
  <select id="filterType"><option value="">(alle)</option></select>

  <label for="participant">Teilnehmer:</label>
  <select id="participant"><option value="">(alle)</option></select>

  <button id="btnRefresh">Aktualisieren</button>
  <button id="downloadJSON">Export aktuell (JSON)</button>
</div>

<div class="row">
  <div class="card narrow">
    <h3 style="margin:4px 0 8px 0">Transitions Heatmap</h3>
    <div id="heatmapContainer"></div>
    <div class="legend" id="heatLegend"></div>
  </div>

  <div class="card wide">
    <h3 style="margin:4px 0 8px 0">Transitionstypen (Verteilung)</h3>
    <canvas id="typesChart" height="120"></canvas>
    <h3 style="margin:14px 0 8px 0">Δh-Verlauf (wähle Teilnehmer)</h3>
    <canvas id="lineChart" height="160"></canvas>
  </div>
</div>

<div class="row" style="margin-top:14px">
  <div class="card wide">
    <h3 style="margin:4px 0 8px 0">Cluster-Netzwerk (gerichtet)</h3>
    <div id="network"></div>
  </div>
</div>

<footer>Erstellt: <?= date('Y-m-d H:i:s') ?> — Datenquelle: <code>frzk_transitions</code>, <code>frzk_semantische_dichte</code></footer>

<script>
/* --- initial data (from PHP) --- */
const MATRIX = <?= $matrixJson ?>;
const TYPE_STATS = <?= $typeStatsJson ?>;
const EDGES = <?= $edgesJson ?>;
const PARTICIPANTS = <?= $participantsJson ?>;
const TRANSITIONS = <?= $transitionsJson ?>;

/* populate filters */
const filterType = document.getElementById('filterType');
Object.keys(TYPE_STATS).sort().forEach(t => {
  const opt = document.createElement('option'); opt.value = t; opt.textContent = t + ' ('+TYPE_STATS[t]+')';
  filterType.appendChild(opt);
});
const participantSel = document.getElementById('participant');
PARTICIPANTS.forEach(p => {
  const o = document.createElement('option'); o.value = p; o.textContent = p; participantSel.appendChild(o);
});

/* Heatmap rendering as HTML table with color scale */
function renderHeatmap(matrix, filter) {
  // collect cluster ids
  const clusters = Array.from(new Set([
    ...Object.keys(matrix).map(n=>parseInt(n)),
    ...Object.values(matrix).flatMap(obj => Object.keys(obj).map(k=>parseInt(k)))
  ])).sort((a,b)=>a-b);

  // compute max for color scaling
  let maxVal = 0;
  clusters.forEach(i=>{
    const row = matrix[i] || {};
    clusters.forEach(j=>{
      const v = row[j] || 0;
      if (v>maxVal) maxVal=v;
    });
  });

  const container = document.getElementById('heatmapContainer');
  container.innerHTML = '';
  const table = document.createElement('table');
  table.className='heat';
  const thead = document.createElement('thead');
  const hrow = document.createElement('tr');
  hrow.appendChild(document.createElement('th'));
  clusters.forEach(j => { const th=document.createElement('th'); th.textContent = 'C'+j; hrow.appendChild(th); });
  thead.appendChild(hrow); table.appendChild(thead);

  const tbody = document.createElement('tbody');
  clusters.forEach(i=>{
    const tr = document.createElement('tr');
    const th = document.createElement('th'); th.textContent = 'C'+i; tr.appendChild(th);
    clusters.forEach(j=>{
      const td = document.createElement('td');
      let val = (matrix[i] && matrix[i][j]) ? matrix[i][j] : 0;

      // apply simple filter: if filter provided, recompute counts by scanning TRANSITIONS
      if (filter && filter.type) {
        val = TRANSITIONS.filter(t => {
          return (t.von_cluster==i && t.nach_cluster==j && (filter.type=='' || t.transition_typ==filter.type) &&
                  (filter.participant=='' || t.teilnehmer_id==filter.participant)
                 );
        }).length;
      } else if (filter && filter.participant) {
        val = TRANSITIONS.filter(t => (t.von_cluster==i && t.nach_cluster==j && t.teilnehmer_id==filter.participant)).length;
      }

      td.textContent = val;
      // choose color
      const ratio = maxVal ? (val / maxVal) : 0;
      const color = heatColor(ratio);
      td.style.background = color;
      td.style.color = (ratio>0.6) ? '#fff' : '#222';
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });
  table.appendChild(tbody);
  container.appendChild(table);

  // legend
  const legend = document.getElementById('heatLegend');
  legend.innerHTML = '';
  const steps = 5;
  for (let s=0;s<=steps;s++){
    const box=document.createElement('div'); box.className='box';
    const lbl=document.createElement('div'); lbl.style.fontSize='12px';
    const r = s/steps;
    box.style.background = heatColor(r);
    lbl.textContent = Math.round(r*maxVal);
    legend.appendChild(box); legend.appendChild(lbl);
  }
}
function heatColor(r){
  // r in [0,1] -> color scale blue->yellow->red
  const h = (1 - r) * 240; // hue
  return `hsl(${h}, 85%, ${40 + r*30}%)`;
}

/* Chart: Transition types (bar) */
let typesChart = null;
function renderTypeChart(filtered) {
  // apply filters
  const counts = {};
  TRANSITIONS.forEach(t=>{
    if ((filtered.type && filtered.type!='' && t.transition_typ!==filtered.type) || (filtered.participant && filtered.participant!='' && t.teilnehmer_id!=filtered.participant)) return;
    counts[t.transition_typ] = (counts[t.transition_typ]||0) + 1;
  });
  const labels = Object.keys(counts).sort();
  const data = labels.map(l=>counts[l]);

  const ctx = document.getElementById('typesChart').getContext('2d');
  if (typesChart) typesChart.destroy();
  typesChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{ label: 'Anzahl Übergänge', data: data, backgroundColor: labels.map(()=> 'rgba(54,162,235,0.7)') }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero:true } }
    }
  });
}

/* Line chart: Δh over time for participant */
let lineChart = null;
function renderLineChart(participantId) {
  const ctx = document.getElementById('lineChart').getContext('2d');

  const rows = participantId ? TRANSITIONS.filter(t=>t.teilnehmer_id==participantId) : [];
  // if no transitions, try semantische dichte h values to show Δh
  let times = [], values = [];
  if (participantId) {
    // fetch semantische dichte h series via AJAX (call to same PHP file)
    fetch('?action=hdatas&pid=' + encodeURIComponent(participantId))
      .then(r=>r.json())
      .then(series=>{
        times = series.map(s=>s.zeitpunkt);
        values = series.map(s=>parseFloat(s.h_bedeutung));
        drawLine(times, values);
      });
  } else {
    drawLine([], []);
  }

  function drawLine(times, values){
    if (lineChart) lineChart.destroy();
    lineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: times,
        datasets: [{
          label: 'h_bedeutung (Δh über Zeit)',
          data: values,
          tension: 0.3,
          fill: false,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        scales: {
          x: { display:true, title: { display: true, text:'Zeit' } },
          y: { beginAtZero:false }
        }
      }
    });
  }
}

/* Network: vis.js */
function renderNetwork(filtered) {
  const container = document.getElementById('network');
  container.innerHTML = '';
  // build nodes from edges
  const nodeIds = new Set();
  EDGES.forEach(e=>{ nodeIds.add(e.from); nodeIds.add(e.to); });
  const nodes = Array.from(nodeIds).map(id => ({ id: id, label: 'C'+id }));
  // edges aggregated with weight and avg
  const visEdges = EDGES
    .filter(e => {
      if (filtered.type && filtered.type!='') {
        // if filtered by type, include only edges that have such transitions
        return TRANSITIONS.some(t => t.von_cluster==e.from && t.nach_cluster==e.to && t.transition_typ==filtered.type && (filtered.participant=='' || t.teilnehmer_id==filtered.participant));
      }
      if (filtered.participant && filtered.participant!='') {
        return TRANSITIONS.some(t => t.von_cluster==e.from && t.nach_cluster==e.to && t.teilnehmer_id==filtered.participant);
      }
      return true;
    })
    .map(e => ({
      from: e.from,
      to: e.to,
      value: e.count,
      title: 'count: '+e.count + ' | avgInt: '+e.avgInt,
      label: String(e.count),
      arrows: 'to'
    }));

  const data = { nodes: nodes, edges: visEdges };
  const options = {
    nodes: { shape: 'dot', size: 16 },
    edges: { width: 2, arrows: { to: { enabled: true, scaleFactor: 0.6 } } },
    physics: { stabilization: true }
  };
  new vis.Network(container, data, options);
}

/* initial render */
function refresh() {
  const filter = { type: filterType.value, participant: participantSel.value };
  renderHeatmap(MATRIX, filter.type || filter.participant ? filter : null);
  renderTypeChart(filter);
  renderLineChart(filter.participant || '');
  renderNetwork(filter);
}
document.getElementById('btnRefresh').addEventListener('click', refresh);

/* download current JSON */
document.getElementById('downloadJSON').addEventListener('click', ()=>{
  const filter = { type: filterType.value, participant: participantSel.value };
  const payload = {
    matrix: MATRIX,
    types: TYPE_STATS,
    edges: EDGES,
    filter: filter
  };
  const blob = new Blob([JSON.stringify(payload, null, 2)], {type:'application/json'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href=url; a.download = 'frzk_transitions_dashboard_export.json'; a.click();
  URL.revokeObjectURL(url);
});

/* participant change triggers line chart fetch */
participantSel.addEventListener('change', ()=> renderLineChart(participantSel.value));

/* server side route for h series (AJAX) */
</script>

<?php
// server-side AJAX endpoint for h-series per participant
if (isset($_GET['action']) && $_GET['action'] === 'hdatas' && isset($_GET['pid'])) {
    $pid = (int)$_GET['pid'];
    $s = $pdo->prepare("SELECT zeitpunkt, h_bedeutung FROM frzk_semantische_dichte WHERE teilnehmer_id = :pid ORDER BY zeitpunkt");
    $s->execute([":pid"=>$pid]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows);
    exit;
}
?>

</body>
</html>
