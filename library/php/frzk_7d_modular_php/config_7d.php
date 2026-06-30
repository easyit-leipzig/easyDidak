<?php
// ============================================================================
// config_7d.php
// Zentrale Konfiguration und FRZK-Hilfsfunktionen fuer die modulare 7D-v3-
// Teilnehmersicht. v3 fuehrt Skalenvektor, Emotionsvektor und FRZK-Fusion
// explizit getrennt und speichert die Fusionsparameter.
// ============================================================================

const DB_HOST = '127.0.0.1';
const DB_NAME = 'icas_19_4_2';
const DB_USER = 'root';
const DB_PASS = '';
const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

// FRZK-Fusionsparameter. Diese Werte entsprechen dem bisher verwendeten
// Standardrahmen der rekursiven FRZK-Zustandsakkumulation.
const FRZK_ALPHA_EMOTION = 0.35;   // Emotionskopplung
const FRZK_BETA_DAMPING  = 0.15;   // Dichteabhängige Dämpfung
const FRZK_LAMBDA_INTERFERENCE = 0.25; // Hadamard-Interferenz / Überlagerung
const FRZK_DELTA_RESONANCE = 0.20; // Resonanzverstärkung

$DIMENSIONS_7D = ['kognition','sozial','affektiv','motivation','methodik','performanz','regulation'];

function frzk_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function noteToSigned($note): ?float {
    if ($note === null || $note === '') return null;
    $note = (float)$note;
    if ($note < 1 || $note > 6) return null;
    return (3.5 - $note) / 2.5; // 1 -> +1; 3.5 -> 0; 6 -> -1
}
function signedFromRow(array $row, string $field): ?float { return noteToSigned($row[$field] ?? null); }
function addWeighted(array &$sum, array &$weight, string $dim, ?float $value, float $w = 1.0): void {
    if ($value === null) return;
    if (!array_key_exists($dim, $sum)) return;
    $sum[$dim] += $value * $w;
    $weight[$dim] += $w;
}
function normN(array $v): float { $s=0.0; foreach($v as $x){$s += ((float)$x)*((float)$x);} return sqrt($s); }
function varianceN(array $v): float { $n=count($v); if($n===0)return 0.0; $m=array_sum($v)/$n; $s=0.0; foreach($v as $x){$s+=pow((float)$x-$m,2);} return $s/$n; }
function dot7(array $a, array $b, array $dimensions): float { $s=0.0; foreach($dimensions as $dim){$s += (float)($a[$dim]??0.0) * (float)($b[$dim]??0.0);} return $s; }
function cosine7(array $a, array $b, array $dimensions): float { $na=normN($a); $nb=normN($b); if($na<=1e-9 || $nb<=1e-9)return 0.0; return dot7($a,$b,$dimensions)/($na*$nb); }
function hadamard7(array $a, array $b, array $dimensions): array { $out=[]; foreach($dimensions as $dim){$out[$dim]=(float)($a[$dim]??0.0)*(float)($b[$dim]??0.0);} return $out; }
function zeroVector7(array $dimensions): array { return array_fill_keys($dimensions, 0.0); }
function clamp7(array $v, array $dimensions, float $min=-1.5, float $max=1.5): array { foreach($dimensions as $d){$v[$d]=max($min,min($max,(float)($v[$d]??0.0)));} return $v; }

function dominantDimension(array $values, array $dimensions): array {
    $dominantDim = null; $dominantVal = 0.0;
    foreach($dimensions as $dim){ $val=(float)($values[$dim]??0.0); if($dominantDim===null || abs($val)>abs($dominantVal)){ $dominantDim=$dim; $dominantVal=$val; }}
    return [$dominantDim, $dominantVal];
}
function polarityFromValues(array $values): int { $s=array_sum($values); return $s>0 ? 1 : ($s<0 ? -1 : 0); }
function transitionMarker7d(float $delta, ?float $stability=null): string {
    if ($delta < 0.05) $marker='Homöostatisch';
    elseif ($delta < 0.15) $marker='Adaptiv';
    elseif ($delta < 0.30) $marker='Koordinativ';
    elseif ($delta < 0.50) $marker='Transformativ';
    elseif ($delta < 0.80) $marker='Perturbativ';
    else $marker='Kollapsiv';
    if ($stability !== null) {
        if ($stability < 0.30 && $delta > 0.30) $marker .= ' (instabil)';
        elseif ($stability > 0.80 && $delta < 0.10) $marker .= ' (resilient)';
    }
    return $marker;
}
function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->execute([$table,$column]);
    if ((int)$stmt->fetchColumn() === 0) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}
function exportTable(PDO $pdo, string $table, ?string $orderBy=null): void {
    $sql = "SELECT * FROM `$table`" . ($orderBy ? " ORDER BY $orderBy" : '');
    $rows = $pdo->query($sql)->fetchAll();
    file_put_contents(__DIR__ . "/$table.json", json_encode($rows, JSON_FLAGS));
    echo "✅ Exportiert: $table (".count($rows)." Datensätze)\n";
}

// --------------------------------------------------------------------------
// v3: Emotionsoperator und FRZK-Fusion
// --------------------------------------------------------------------------
function normalizeEmotionIds($raw): array {
    if ($raw === null) return [];
    $raw = trim((string)$raw);
    if ($raw === '') return [];
    $ids = [];
    foreach (explode(',', $raw) as $part) {
        $eid = (int)trim($part);
        if ($eid > 0) $ids[] = $eid;
    }
    return array_values(array_unique($ids));
}

function emotionToVector7(array $emo, array $dimensions): array {
    $ev = zeroVector7($dimensions);
    $v = (float)($emo['valenz'] ?? 0.0);
    $a = (float)($emo['aktivierung'] ?? 0.0);
    $a = max(0.0, min(1.0, $a));
    $label = strtolower((string)($emo['type_name'] ?? '') . ' ' . (string)($emo['fine_label'] ?? '') . ' ' . (string)($emo['emotion'] ?? ''));

    // Grundwirkung: Affekt, Motivation, Regulation.
    $ev['affektiv']   += $v * (0.65 + 0.35 * $a);
    $ev['motivation'] += $v * $a;
    $ev['regulation'] += ($v < 0.0) ? $v * $a : $v * (1.0 - $a);

    // Kognitive Übergangsemotionen: Interesse, Neugier, Überraschung.
    if (str_contains($label, 'kognitiv') || str_contains($label, 'interesse') || str_contains($label, 'neugier') || str_contains($label, 'überraschung') || str_contains($label, 'ueberraschung')) {
        $ev['kognition'] += max(0.0, $v) * (0.25 + 0.25 * $a);
        $ev['methodik']  += max(0.0, $v) * 0.15;
    }

    // Soziale Emotionen: Dankbarkeit, Stolz, Zugehörigkeit.
    if (str_contains($label, 'sozial') || str_contains($label, 'dankbarkeit') || str_contains($label, 'stolz') || str_contains($label, 'zugehör') || str_contains($label, 'zugehoer')) {
        $ev['sozial'] += $v * 0.45;
    }

    // Belastungs- und Unsicherheitsmarker.
    if (str_contains($label, 'frustration') || str_contains($label, 'angst') || str_contains($label, 'unsicherheit') || str_contains($label, 'überforderung') || str_contains($label, 'ueberforderung')) {
        $ev['regulation'] += $v * $a * 0.50;
        $ev['performanz'] += $v * $a * 0.30;
        $ev['methodik']   += $v * $a * 0.15;
    }

    // Entlastende Übergänge: Hoffnung, Erleichterung, Freude.
    if (str_contains($label, 'hoffnung') || str_contains($label, 'erleichterung') || str_contains($label, 'freude')) {
        $ev['motivation'] += max(0.0, $v) * (0.50 + 0.50 * $a);
        $ev['regulation'] += max(0.0, $v) * 0.35;
        $ev['performanz'] += max(0.0, $v) * 0.20;
    }

    return clamp7($ev, $dimensions, -1.5, 1.5);
}

function buildEmotionVector7(array $emotionIds, array $emotionMap, array $dimensions): array {
    $E = zeroVector7($dimensions);
    $valenzSum = 0.0; $aktivSum = 0.0; $count = 0; $validIds = [];
    foreach ($emotionIds as $eid) {
        if (!isset($emotionMap[$eid])) continue;
        $emo = $emotionMap[$eid];
        $ev = emotionToVector7($emo, $dimensions);
        foreach ($dimensions as $dim) $E[$dim] += $ev[$dim];
        $valenzSum += (float)($emo['valenz'] ?? 0.0);
        $aktivSum  += (float)($emo['aktivierung'] ?? 0.0);
        $validIds[] = (int)$eid; $count++;
    }
    if ($count > 0) foreach ($dimensions as $dim) $E[$dim] /= $count;
    return [
        'vector' => $E,
        'ids' => $validIds,
        'valenz' => $count > 0 ? $valenzSum / $count : null,
        'aktivierung' => $count > 0 ? $aktivSum / $count : null,
        'count' => $count,
    ];
}

function fuseFrzkState7(array $S, array $E, array $dimensions, ?array $params=null): array {
    $alpha  = (float)($params['alpha']  ?? FRZK_ALPHA_EMOTION);
    $beta   = (float)($params['beta']   ?? FRZK_BETA_DAMPING);
    $lambda = (float)($params['lambda'] ?? FRZK_LAMBDA_INTERFERENCE);
    $delta  = (float)($params['delta']  ?? FRZK_DELTA_RESONANCE);
    $normS = normN($S);
    $cos = cosine7($S, $E, $dimensions);
    $F = [];
    foreach ($dimensions as $dim) {
        $s = (float)($S[$dim] ?? 0.0);
        $e = (float)($E[$dim] ?? 0.0);
        $F[$dim] = $s
            + $alpha * $e * exp(-$beta * $normS)
            - $lambda * ($s * $e)
            + $delta * $cos * $e;
    }
    return clamp7($F, $dimensions, -2.0, 2.0);
}
?>
