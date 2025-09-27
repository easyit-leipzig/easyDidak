<?php
/*



*/
// api_generate_feedback.php

header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid JSON body']);
    exit;
}

$required = ['teilnehmer_id','ue_zuweisung_teilnehmer_id','gruppe_id'];
foreach ($required as $r) {
    if (!array_key_exists($r,$input)) {
        http_response_code(400);
        echo json_encode(['error'=>"Missing parameter: {$r}"]);
        exit;
    }
}

$teilnehmer_id = (int)$input['teilnehmer_id'];
$ue_zuweisung_teilnehmer_id = (int)$input['ue_zuweisung_teilnehmer_id'];
$gruppe_id = (int)$input['gruppe_id'];

// -------- Default Config --------
$config = [
    'weights' => [
        'mitarbeit' => [['Extraversion',0.4], ['gewissenhaftigkeit',0.4], ['handlungsdichte',0.2]],
        'konzentration' => [['lernfaehigkeit',0.45], ['gewissenhaftigkeit',0.35], ['belastbarkeit',0.2]],
        'selbststaendigkeit' => [['metakognition',0.7], ['zielorientierung',0.3]],
        'fleiss' => [['gewissenhaftigkeit',0.7], ['performanz_effizienz',0.3]],
        'lernfortschritt' => [['basiswissen',0.4], ['problemlösefähigkeit',0.35], ['kreativität_innovation',0.25]],
        'beherrscht_thema' => [['basiswissen',0.6], ['performanz_effizienz',0.4]],
        'transferdenken' => [['problemlösefähigkeit',0.6], ['kreativität_innovation',0.4]],
        'vorbereitet' => [['gewissenhaftigkeit',0.6], ['zielorientierung',0.4]],
        'themenauswahl' => [['resonanzfähigkeit',0.4], ['anpassungsfaehigkeit',0.6]],
        'materialien' => [['resonanzfähigkeit',0.3], ['kreativität_innovation',0.7]],
        'methodenvielfalt' => [['offenheit_erfahrungen',0.6], ['ko-kreationsfähigkeit',0.4]],
        'individualisierung' => [['soziale_interaktion',0.5], ['metakognition',0.5]],
        'aufforderung' => [['resonanzfähigkeit',0.5], ['Extraversion',0.5]]
    ],
    'score_bins' => ['bin_edges' => [1.5, 2.5, 3.5]],
    'emotions_rules' => [
        [['offenheit_erfahrungen','>=',3.5], ['kreativität_innovation','>=',3.0], ['emit'=>['Neugier','Motivation']]],
        [['gewissenhaftigkeit','>=',3.5], ['performanz_effizienz','>=',3.0], ['emit'=>['Zufriedenheit']]],
        [['basiswissen','>=',3.5], ['gewissenhaftigkeit','>=',3.5], ['emit'=>['Stolz']]],
        [['stressbewaeltigung','<=',2.5], ['belastbarkeit','<=',2.5], ['emit'=>['Frustration']]],
        [['stressbewaeltigung','<=',1.5], ['emit'=>['Ueberforderung']]],
        [['soziale_interaktion','>=',3.5], ['resonanzfähigkeit','>=',3.0], ['emit'=>['Zugehoerigkeit','Vertrauen']]],
        [['metakognition','>=',3.5], ['emit'=>['Nachdenklichkeit']]]
    ],
    'log_path' => __DIR__ . '/logs/feedback.log',
    'log_keep_recent' => 200
];

// -------- CUSTOM WEIGHTS aus Request verwenden --------
if (isset($input['custom_weights']) && is_array($input['custom_weights'])) {
    foreach ($input['custom_weights'] as $ziel => $felder) {
        $weights = [];
        $total = 0;
        foreach ($felder as $pair) {
            $feld = $pair[0];
            $gewicht = floatval($pair[1]);
            $weights[] = [$feld, $gewicht];
            $total += $gewicht;
        }
        if ($total > 0) {
            $config['weights'][$ziel] = array_map(function($w) use ($total) {
                return [$w[0], $w[1] / $total];
            }, $weights);
        }
    }
}

// -------- DB Verbindung --------
$host = '127.0.0.1'; $db = 'icas'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
try { $pdo = new PDO($dsn, $user, $pass, $options); }
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed', 'detail' => $e->getMessage()]);
    exit;
}

// -------- Hilfsfunktionen --------
function map_to_bin(float $s, array $edges) {
    if ($s <= $edges[0]) return 1;
    if ($s <= $edges[1]) return 2;
    if ($s <= $edges[2]) return 3;
    return 4;
}
function weighted_avg_profile(array $profile, array $pairs) {
    $num=0.0; $den=0.0;
    foreach ($pairs as $p) {
        $field = $p[0]; $w = $p[1];
        $v = array_key_exists($field,$profile) ? (float)$profile[$field] : 3.0;
        $num += $v * $w; $den += $w;
    }
    return $den==0 ? 3.0 : $num/$den;
}
function eval_condition(array $profile, array $cond) {
    list($field,$op,$value) = $cond;
    $pv = $profile[$field] ?? 3.0;
    switch ($op) {
        case '>=': return $pv >= $value;
        case '<=': return $pv <= $value;
        case '>':  return $pv >  $value;
        case '<':  return $pv <  $value;
        default: return false;
    }
}

// -------- Persönlichkeitsprofil laden --------
$stmt = $pdo->prepare("SELECT * FROM mtr_persoenlichkeit WHERE teilnehmer_id = :tid ORDER BY datum DESC LIMIT 1");
$stmt->execute([':tid'=>$teilnehmer_id]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    echo json_encode(['error'=>"No personality record for teilnehmer_id={$teilnehmer_id}"]);
    exit;
}

// -------- Profil normalisieren --------
$expected = [
 'offenheit_erfahrungen','gewissenhaftigkeit','Extraversion','vertraeglichkeit',
 'zielorientierung','lernfaehigkeit','anpassungsfaehigkeit','soziale_interaktion',
 'metakognition','stressbewaeltigung','bedeutungsbildung','belastbarkeit',
 'problemlösefähigkeit','kreativität_innovation','ko-kreationsfähigkeit',
 'resonanzfähigkeit','handlungsdichte','performanz_effizienz','basiswissen','note'
];
foreach ($expected as $k) {
    if (!isset($profile[$k]) || $profile[$k] === null || $profile[$k]==='') $profile[$k]=3.0;
    else $profile[$k] = (float)$profile[$k];
}

// -------- Zielgrößen berechnen --------
$insert = [];
foreach ($config['weights'] as $target => $spec) {
    $score = weighted_avg_profile($profile, $spec);
    $insert[$target] = map_to_bin($score, $config['score_bins']['bin_edges']);
}
$insert['basiswissen'] = map_to_bin($profile['basiswissen'], $config['score_bins']['bin_edges']);
$insert['zielgruppen'] = map_to_bin($profile['soziale_interaktion'], $config['score_bins']['bin_edges']);

// -------- Zeile für mtr_rueckkopplung_teilnehmer --------
$insert_row = [
    'ue_zuweisung_teilnehmer_id' => $ue_zuweisung_teilnehmer_id,
    'teilnehmer_id' => $teilnehmer_id,
    'gruppe_id' => $gruppe_id,
    'einrichtung_id' => 1,
    'mitarbeit' => $insert['mitarbeit'],
    'absprachen' => map_to_bin(weighted_avg_profile($profile, [['vertraeglichkeit',0.6],['soziale_interaktion',0.4]]), $config['score_bins']['bin_edges']),
    'selbststaendigkeit' => $insert['selbststaendigkeit'],
    'konzentration' => $insert['konzentration'],
    'fleiss' => $insert['fleiss'],
    'lernfortschritt' => $insert['lernfortschritt'],
    'beherrscht_thema' => $insert['beherrscht_thema'],
    'transferdenken' => $insert['transferdenken'],
    'basiswissen' => $insert['basiswissen'],
    'vorbereitet' => $insert['vorbereitet'],
    'themenauswahl' => $insert['themenauswahl'],
    'materialien' => $insert['materialien'],
    'methodenvielfalt' => $insert['methodenvielfalt'],
    'individualisierung' => $insert['individualisierung'],
    'aufforderung' => $insert['aufforderung'],
    'zielgruppen' => $insert['zielgruppen']
];

// -------- Emotionen berechnen --------
$em_names = [];
foreach ($config['emotions_rules'] as $rule) {
    $emit = [];
    $ok = true;
    foreach ($rule as $cond) {
        if (isset($cond['emit'])) { $emit = $cond['emit']; continue; }
        if (!eval_condition($profile, $cond)) { $ok = false; break; }
    }
    if ($ok && $emit) foreach ($emit as $e) $em_names[] = $e;
}
if (count($em_names) === 0) {
    $em_names[] = ($profile['offenheit_erfahrungen'] >= 3.0) ? 'Interesse' : 'Unsicherheit';
}
$emotions_str = implode(',', array_unique($em_names));

// -------- Boolean-Flags vorbereiten --------
$all_emotion_cols = [
    'freude','zufriedenheit','erfuellung','motivation','dankbarkeit','hoffnung','stolz','selbstvertrauen',
    'neugier','inspiration','zugehoerigkeit','vertrauen','spass','sicherheit','frustration','ueberforderung',
    'angst','langeweile','scham','zweifel','resignation','erschoepfung','interesse','verwirrung',
    'unsicherheit','ueberraschung','erwartung','erleichterung'
];
$emotion_flags = array_fill_keys($all_emotion_cols, 0);
foreach ($em_names as $e) {
    if (array_key_exists($e,$emotion_flags)) $emotion_flags[$e]=1;
    if ($e === 'Nachdenklichkeit') $emotion_flags['Inspiration'] = 1;
}

// -------- Datenbank Insert (Transaktion) --------
$pdo->beginTransaction();
try {
    $cols = array_keys($insert_row);
    $cols[] = 'emotions'; $cols[] = 'bemerkungen';
    $place = implode(',', array_map(fn($c)=>':'.$c, $cols));
    $stmt = $pdo->prepare("INSERT INTO mtr_rueckkopplung_teilnehmer (" . implode(',', $cols) . ") VALUES ({$place})");
    foreach ($insert_row as $k=>$v) $params[':'.$k] = $v;
    $params[':emotions'] = $emotions_str;
    $params[':bemerkungen'] = "auto from mtr_persoenlichkeit id={$profile['id']}";
    $stmt->execute($params);
    $rueck_id = (int)$pdo->lastInsertId();

    $em_cols = array_merge(['ue_zuweisung_teilnehmer_id','teilnehmer_id','datum','emotions'],$all_emotion_cols);
    $place = implode(',', array_map(fn($c)=>':'.$c, $em_cols));
    $stmt2 = $pdo->prepare("INSERT INTO mtr_emotions (" . implode(',',$em_cols) . ") VALUES ({$place})");
    $params2 = [
        ':ue_zuweisung_teilnehmer_id' => $ue_zuweisung_teilnehmer_id,
        ':teilnehmer_id' => $teilnehmer_id,
        ':datum' => date('Y-m-d H:i:s'),
        ':emotions' => $emotions_str
    ];
    foreach ($all_emotion_cols as $c) $params2[':'.$c] = $emotion_flags[$c] ?? 0;
    $stmt2->execute($params2);
    $emotion_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    // -------- Logging --------
    $log = [
        'time' => date('c'),
        'profile_id' => $profile['id'],
        'teilnehmer_id' => $teilnehmer_id,
        'ue_zuweisung_teilnehmer_id' => $ue_zuweisung_teilnehmer_id,
        'rueckkopplung_id' => $rueck_id,
        'mtr_emotions_id' => $emotion_id,
        'emotions' => $em_names,
        'insert_row' => $insert_row
    ];
    $log_line = json_encode($log, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (!is_dir(dirname($config['log_path']))) @mkdir(dirname($config['log_path']),0755,true);
    @file_put_contents($config['log_path'], $log_line, FILE_APPEND | LOCK_EX);

    echo json_encode(['status'=>'ok','rueck_id'=>$rueck_id,'emotion_id'=>$emotion_id,'emotions'=>$em_names]);
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'Insert failed','detail'=>$e->getMessage()]);
    exit;
}
