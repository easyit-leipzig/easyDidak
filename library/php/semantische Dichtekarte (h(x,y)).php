<?php
// density_map.php
header('Content-Type: application/json; charset=utf-8');
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4","root","");

// Parameter
$xbins = 12;
$ybins = 6;

$meta = [
    "table" => "mtr_rueckkopplung_teilnehmer",
    "x_field" => "ue_zuweisung_teilnehmer_id",
    "y_field" => "gruppe_id",
    "val_columns" => [
        "val_mitarbeit","val_absprachen","val_selbststaendigkeit","val_konzentration",
        "val_fleiss","val_lernfortschritt","val_beherrscht_thema","val_transferdenken",
        "val_basiswissen","val_vorbereitet","val_themenauswahl","val_materialien",
        "val_methodenvielfalt","val_individualisierung","val_aufforderung","val_zielgruppen",
        "val_emotions"
    ],
    "aggregation" => "avg",
    "xbins" => $xbins,
    "ybins" => $ybins
];

// min/max ermitteln
$range = $pdo->query("SELECT MIN({$meta['x_field']}) as xmin, MAX({$meta['x_field']}) as xmax,
                             MIN({$meta['y_field']}) as ymin, MAX({$meta['y_field']}) as ymax
                      FROM {$meta['table']}")->fetch(PDO::FETCH_ASSOC);

$dx = ($range['xmax'] - $range['xmin']) / $xbins;
$dy = ($range['ymax'] - $range['ymin']) / $ybins;

$result = [
    "meta" => $meta,
    "x_labels" => [],
    "y_labels" => [],
    "h" => [],
    "counts" => []
];

// Achsen-Beschriftung
for ($i=0; $i<$xbins; $i++) {
    $result["x_labels"][] = ($range['xmin']+$i*$dx) . ".." . ($range['xmin']+($i+1)*$dx);
}
for ($j=0; $j<$ybins; $j++) {
    $result["y_labels"][] = ($range['ymin']+$j*$dy) . ".." . ($range['ymin']+($j+1)*$dy);
}

// Raster füllen
for ($i=0; $i<$xbins; $i++) {
    $xlo = $range['xmin']+$i*$dx;
    $xhi = $xlo+$dx;
    $rowH = []; $rowC = [];
    for ($j=0; $j<$ybins; $j++) {
        $ylo = $range['ymin']+$j*$dy;
        $yhi = $ylo+$dy;
        $sql = "SELECT AVG((" . implode("+",$meta['val_columns']) . ")/".count($meta['val_columns']).") as hval, COUNT(*) as c
                FROM {$meta['table']}
                WHERE {$meta['x_field']} >= $xlo AND {$meta['x_field']} < $xhi
                  AND {$meta['y_field']} >= $ylo AND {$meta['y_field']} < $yhi";
        $cell = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        $rowH[] = $cell['c']>0 ? floatval($cell['hval']) : null;
        $rowC[] = intval($cell['c']);
    }
    $result["h"][] = $rowH;
    $result["counts"][] = $rowC;
}
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
