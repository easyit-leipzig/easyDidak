<?php
// orientation_loss.php
//Hier wird aus density_map.php die Gradientengröße berechnet und normalisiert → 
header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('density_map.json'), true);

$h = $data['h'];
$ny = count($data['y_labels']);
$nx = count($data['x_labels']);

$grad = []; $maxg = 0;
for ($iy=0;$iy<$ny;$iy++){
  $row = [];
  for ($ix=0;$ix<$nx;$ix++){
    $c = $h[$ix][$iy] ?? null;
    if ($c===null){ $row[] = null; continue; }
    $left  = ($ix>0 && $h[$ix-1][$iy]!==null)?$h[$ix-1][$iy]:$c;
    $right = ($ix<$nx-1 && $h[$ix+1][$iy]!==null)?$h[$ix+1][$iy]:$c;
    $down  = ($iy>0 && $h[$ix][$iy-1]!==null)?$h[$ix][$iy-1]:$c;
    $up    = ($iy<$ny-1 && $h[$ix][$iy+1]!==null)?$h[$ix][$iy+1]:$c;
    $gx = ($right-$left)/2.0;
    $gy = ($up-$down)/2.0;
    $g = sqrt($gx*$gx+$gy*$gy);
    $row[] = $g;
    if ($g>$maxg) $maxg=$g;
  }
  $grad[] = $row;
}
$O = [];
for ($iy=0;$iy<$ny;$iy++){
  $row = [];
  for ($ix=0;$ix<$nx;$ix++){
    if ($grad[$iy][$ix]===null){ $row[] = null; continue; }
    $row[] = 1.0 - $grad[$iy][$ix]/$maxg;
  }
  $O[] = $row;
}
echo json_encode(["meta"=>$data["meta"],"O"=>$O,"x_labels"=>$data["x_labels"],"y_labels"=>$data["y_labels"]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
