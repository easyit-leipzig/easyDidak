<?php
// semantic_density.php
// Erwartet: MySQL-DB "icas" (Standard) und Tabelle z.B. "mtr_rueckkopplung_teilnehmer".
// Liefert JSON: { x_labels: [...], y_labels: [...], h: [[...],[...]], counts: [[...]] , meta: {...} }

// ---------------------- Konfiguration ----------------------
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'icas';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// Parametrisierbar über GET
// Beispiel: semantic_density.php?table=mtr_rueckkopplung_teilnehmer&x=ue_zuweisung_teilnehmer_id&y=gruppe_id&agg=avg&xbins=10&ybins=6
$table = isset($_GET['table']) ? $_GET['table'] : 'mtr_rueckkopplung_teilnehmer';
$x_field = isset($_GET['x']) ? $_GET['x'] : null;
$y_field = isset($_GET['y']) ? $_GET['y'] : null;
$agg = isset($_GET['agg']) ? strtolower($_GET['agg']) : 'avg'; // 'avg' | 'count' | 'sum'
$xbins = isset($_GET['xbins']) ? max(1,intval($_GET['xbins'])) : null;
$ybins = isset($_GET['ybins']) ? max(1,intval($_GET['ybins'])) : null;
$limit_rows = isset($_GET['limit']) ? intval($_GET['limit']) : 0; // optional, für große DBs

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: '.$e->getMessage()]);
    exit;
}

// ---------------------- Spalten ermitteln ----------------------
$sth = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table");
$sth->execute([':schema'=>$dbname, ':table'=>$table]);
$cols = $sth->fetchAll(PDO::FETCH_COLUMN);

if (!$cols) {
    http_response_code(400);
    echo json_encode(['error' => "Tabelle '$table' nicht gefunden oder keine Spalten."]);
    exit;
}

// erkenne val_* spalten automatisch (Bewertungen)
$val_cols = array_values(array_filter($cols, function($c){
    $c_low = strtolower($c);
    return (strpos($c_low,'val') === 0) || (strpos($c_low,'bewert') !== false) || (strpos($c_low,'score') !== false) || (strpos($c_low,'rating') !== false);
}));

// fallback: falls keine val-Spalten gefunden, suche nach typischen Namen
if (empty($val_cols)) {
    foreach ($cols as $c) {
        $cl = strtolower($c);
        if (in_array($cl, ['rating','score','bewertung','value','wert'])) {
            $val_cols[] = $c;
        }
    }
}

if (empty($val_cols)) {
    http_response_code(400);
    echo json_encode(['error' => "Keine Bewertungs-/val_-Spalten in Tabelle '$table' gefunden. Bitte mindestens eine val_*-Spalte oder score-Spalte anlegen."]);
    exit;
}

// bestimme x und y falls nicht gesetzt (heuristisch)
if (!$x_field) {
    $cands = ['ue_zuweisung_teilnehmer_id','teilnehmer_id','teilnehmer','id','user_id'];
    foreach ($cands as $c) {
        if (in_array($c, $cols)) { $x_field = $c; break; }
    }
    if (!$x_field) {
        // erstes Nicht-val-Feld
        foreach ($cols as $c) { if (!in_array($c, $val_cols)) { $x_field = $c; break; } }
    }
}
if (!$y_field) {
    $cands = ['gruppe_id','group_id','gruppe','class_id','team_id'];
    foreach ($cands as $c) {
        if (in_array($c, $cols)) { $y_field = $c; break; }
    }
    if (!$y_field) {
        // zweites Nicht-val-Feld (falls vorhanden)
        foreach ($cols as $c) { if (!in_array($c, $val_cols) && $c !== $x_field) { $y_field = $c; break; } }
    }
}

if (!$x_field || !$y_field) {
    http_response_code(400);
    echo json_encode(['error' => 'Konnte keine geeigneten x- oder y-Felder bestimmen. Gib GET-Parameter x und y an. Gefundene Spalten: '.json_encode($cols)]);
    exit;
}

// ---------------------- Daten laden ----------------------
// wir ziehen alle relevanten Spalten (x, y, val_*)
$selectCols = array_unique(array_merge([$x_field, $y_field], $val_cols));
$sql = "SELECT ".implode(",", array_map(function($c){ return "`$c`"; }, $selectCols))." FROM `$table`";
if ($limit_rows > 0) $sql .= " LIMIT ".intval($limit_rows);

try {
    $rows = $pdo->query($sql, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Lesen der Daten: '.$e->getMessage()]);
    exit;
}

// ---------------------- Rohdaten verarbeiten ----------------------
$raw = [];
$x_values_raw = [];
$y_values_raw = [];

foreach ($rows as $r) {
    // Score = Mittelwert über vorhandene val_* Felder (ignoriere NULL/non-numeric)
    $vals = [];
    foreach ($val_cols as $vc) {
        if (isset($r[$vc]) && is_numeric($r[$vc])) $vals[] = floatval($r[$vc]);
    }
    if (count($vals) === 0) continue; // keine Bewertung in dieser Zeile -> überspringen
    $score = array_sum($vals) / count($vals);

    $xv = $r[$x_field];
    $yv = $r[$y_field];

    // skip rows with both x/y null
    if ($xv === null && $yv === null) continue;

    $raw[] = ['x'=>$xv, 'y'=>$yv, 'score'=>$score];
    $x_values_raw[] = $xv;
    $y_values_raw[] = $yv;
}

if (empty($raw)) {
    echo json_encode(['error'=>'Keine verwertbaren Zeilen (keine val- Werte oder filter).']);
    exit;
}

// Hilfsfunktionen
function is_column_numeric($vals) {
    $count = count($vals);
    $num = 0;
    foreach ($vals as $v) {
        if ($v === null) continue;
        if (is_numeric($v)) $num++;
    }
    return ($count>0 && ($num / $count) >= 0.6); // mind. 60% numeric
}

// ---------------------- x/y Typ bestimmen + Binning ----------------------
$x_is_numeric = is_column_numeric($x_values_raw);
$y_is_numeric = is_column_numeric($y_values_raw);

$distinct_x = array_values(array_unique($x_values_raw));
$distinct_y = array_values(array_unique($y_values_raw));
sort($distinct_x);
sort($distinct_y);

// Standardbins wenn numeric und nicht angegeben
if ($x_is_numeric && !$xbins) $xbins = min(12, max(4, intval(count($distinct_x) / 2))); 
if ($y_is_numeric && !$ybins) $ybins = min(12, max(4, intval(count($distinct_y) / 2)));

$mapX = []; $x_labels = [];
$mapY = []; $y_labels = [];

// Erzeuge Kategorien oder Bins
if ($x_is_numeric) {
    $xmin = min(array_filter($x_values_raw, 'is_numeric'));
    $xmax = max(array_filter($x_values_raw, 'is_numeric'));
    $xbins = $xbins ?: 10;
    $xwidth = ($xmax - $xmin) / $xbins;
    for ($i=0;$i<$xbins;$i++) {
        $low = $xmin + $i*$xwidth;
        $high = ($i==$xbins-1) ? $xmax : ($xmin + ($i+1)*$xwidth);
        $x_labels[] = round($low,3).'..'.round($high,3);
    }
} else {
    // categorical labels
    foreach ($distinct_x as $i=>$val) {
        $x_labels[] = (string)$val;
        $mapX[(string)$val] = $i;
    }
}

if ($y_is_numeric) {
    $ymin = min(array_filter($y_values_raw, 'is_numeric'));
    $ymax = max(array_filter($y_values_raw, 'is_numeric'));
    $ybins = $ybins ?: 10;
    $ywidth = ($ymax - $ymin) / $ybins;
    for ($i=0;$i<$ybins;$i++) {
        $low = $ymin + $i*$ywidth;
        $high = ($i==$ybins-1) ? $ymax : ($ymin + ($i+1)*$ywidth);
        $y_labels[] = round($low,3).'..'.round($high,3);
    }
} else {
    foreach ($distinct_y as $i=>$val) {
        $y_labels[] = (string)$val;
        $mapY[(string)$val] = $i;
    }
}

// Matrix initialisieren
$nx = count($x_labels);
$ny = count($y_labels);
$sum = array_fill(0, $nx, array_fill(0, $ny, 0.0));
$count = array_fill(0, $nx, array_fill(0, $ny, 0));

// Aggregation: lege jede Rohzeile in die richtige Zelle
foreach ($raw as $row) {
    // bestimme x index
    if ($x_is_numeric) {
        if (!is_numeric($row['x'])) continue;
        $val = floatval($row['x']);
        // clamp to range
        if ($val <= $xmin) $ix = 0;
        elseif ($val >= $xmax) $ix = $nx-1;
        else $ix = min($nx-1, intval(floor(($val - $xmin) / $xwidth)));
    } else {
        $k = (string)$row['x'];
        if (!isset($mapX[$k])) {
            // neue Kategorie hinzufügen (falls vorkommend)
            $mapX[$k] = count($x_labels);
            $x_labels[] = $k;
            // erweitere Sum/Count-Matrizen
            foreach ($sum as &$col) $col[] = 0.0;
            foreach ($count as &$col) $col[] = 0;
            $nx = count($x_labels);
        }
        $ix = $mapX[$k];
    }

    // bestimme y index
    if ($y_is_numeric) {
        if (!is_numeric($row['y'])) continue;
        $val = floatval($row['y']);
        if ($val <= $ymin) $iy = 0;
        elseif ($val >= $ymax) $iy = $ny-1;
        else $iy = min($ny-1, intval(floor(($val - $ymin) / $ywidth)));
    } else {
        $k = (string)$row['y'];
        if (!isset($mapY[$k])) {
            $mapY[$k] = count($y_labels);
            $y_labels[] = $k;
            // erweitere jede Zeile
            foreach ($sum as &$col) $col[] = 0.0;
            foreach ($count as &$col) $col[] = 0;
            $ny = count($y_labels);
        }
        $iy = $mapY[$k];
    }

    $sum[$ix][$iy] += $row['score'];
    $count[$ix][$iy] += 1;
}

// Endgültige h-Matrix (Aggregationstyp beachten)
$h = array_fill(0, $nx, array_fill(0, $ny, null));
for ($i=0;$i<$nx;$i++) {
    for ($j=0;$j<$ny;$j++) {
        if ($count[$i][$j] === 0) {
            $h[$i][$j] = null;
        } else {
            if ($agg === 'count') $h[$i][$j] = $count[$i][$j];
            elseif ($agg === 'sum') $h[$i][$j] = $sum[$i][$j];
            else $h[$i][$j] = $sum[$i][$j] / $count[$i][$j]; // avg
        }
    }
}

// ---------------------- Ergebnis ausgeben ----------------------
$output = [
    'meta' => [
        'table' => $table,
        'x_field' => $x_field,
        'y_field' => $y_field,
        'val_columns' => $val_cols,
        'aggregation' => $agg,
        'x_is_numeric' => $x_is_numeric,
        'y_is_numeric' => $y_is_numeric,
        'rows_used' => array_sum(array_map(function($r){ return array_sum($r); }, $count)),
        'xbins' => count($x_labels),
        'ybins' => count($y_labels),
    ],
    'x_labels' => $x_labels,
    'y_labels' => $y_labels,
    'h' => $h,
    'counts' => $count
];

echo json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
exit;
?>