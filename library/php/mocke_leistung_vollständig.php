<?php
/**
 * ICAS – Leistungsaggregation aus emotionalen Rückmeldungen
 * ---------------------------------------------------------
 * Version 2: Bewertung wird immer aktualisiert, auch bei vorhandenen Datensätzen.
 */

$pdo = new PDO('mysql:host=localhost;dbname=icas;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insert_count = 0;
$update_count = 0;
$error_log = [];

// ===============================================================
// 1. Emotionen-Definitionen laden
// ===============================================================
$emotionen = [];
$res = $pdo->query("SELECT id AS code, valenz, aktivierung FROM _mtr_emotionen");
foreach ($res as $r) {
    $emotionen[strtolower($r['code'])] = [
        'v' => (float)$r['valenz'],
        'a' => (float)$r['aktivierung']
    ];
}

// ===============================================================
// 2. Rückmeldungen pro Teilnehmer laden
// ===============================================================
$sql = "SELECT id, teilnehmer_id, gruppe_id, emotions 
        FROM mtr_rueckkopplung_teilnehmer";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ===============================================================
// 3. Aggregation: mittlere Valenz / Aktivierung je Teilnehmer
// ===============================================================
$agg = [];
foreach ($data as $d) {
    $tid = (int)$d['teilnehmer_id'];
    $gid = (int)$d['gruppe_id'];
    if (!$tid || !$gid) continue;

    $codes = array_filter(array_map('trim', explode(',', strtolower($d['emotions']))));
    foreach ($codes as $c) {
        if (!isset($emotionen[$c])) continue;
        $agg[$tid]['val_sum'] = ($agg[$tid]['val_sum'] ?? 0) + $emotionen[$c]['v'];
        $agg[$tid]['act_sum'] = ($agg[$tid]['act_sum'] ?? 0) + $emotionen[$c]['a'];
        $agg[$tid]['count']   = ($agg[$tid]['count'] ?? 0) + 1;
        $agg[$tid]['gruppe']  = $gid;
    }
}

// ===============================================================
// 4. Gruppenmittelwerte (emotionale Dichte)
// ===============================================================
$gruppen_dichte = [];
foreach ($agg as $tid => $vals) {
    $g = $vals['gruppe'];
    $count = $vals['count'] ?: 1;
    $d = abs(($vals['val_sum'] / $count) * ($vals['act_sum'] / $count));
    $gruppen_dichte[$g]['sum']   = ($gruppen_dichte[$g]['sum'] ?? 0) + $d;
    $gruppen_dichte[$g]['count'] = ($gruppen_dichte[$g]['count'] ?? 0) + 1;
}
foreach ($gruppen_dichte as $g => $v) {
    $gruppen_dichte[$g]['avg'] = $v['sum'] / $v['count'];
}

// ===============================================================
// 5. SQL-Statements vorbereiten
// ===============================================================
$check_existing_zuweisung = $pdo->prepare("
    SELECT id FROM ue_zuweisung_teilnehmer 
    WHERE ue_unterrichtseinheit_zw_thema_id = ? AND teilnehmer_id = ?
    LIMIT 1
");

$insert_zuweisung = $pdo->prepare("
    INSERT IGNORE INTO ue_zuweisung_teilnehmer 
    (ue_unterrichtseinheit_zw_thema_id, datum, teilnehmer_id)
    VALUES (?, NOW(), ?)
");

$select_zw = $pdo->query("SELECT id FROM ue_unterrichtseinheit_zw_thema LIMIT 1");
$zw_id_fallback = $select_zw->fetchColumn() ?: 1;

$select_leistung = $pdo->prepare("
    SELECT id FROM mtr_leistung
    WHERE teilnehmer_id = ? AND gruppe_id = ?
    LIMIT 1
");

$update_leistung = $pdo->prepare("
    UPDATE mtr_leistung
    SET 
        leistung = :leistung,
        bewertung = :bewertung,
        verhaltensbeurteilung_code = :vb,
        reflexionshinweis = :reflex
    WHERE id = :id
");

$insert_leistung = $pdo->prepare("
    INSERT INTO mtr_leistung 
    (ue_zuweisung_teilnehmer_id, teilnehmer_id, leistung, gruppe_id, bewertung, verhaltensbeurteilung_code, reflexionshinweis)
    VALUES (:ue_id, :tid, :leistung, :gid, :bewertung, :vb, :reflex)
");

// ===============================================================
// 6. Leistung und Bewertung erzeugen + aktualisieren
// ===============================================================
foreach ($agg as $tid => $vals) {
    try {
        $v = $vals['val_sum'] / ($vals['count'] ?: 1);
        $a = $vals['act_sum'] / ($vals['count'] ?: 1);
        $g = $vals['gruppe'];
        $Dg = $gruppen_dichte[$g]['avg'] ?? 0.5;

        // Leistungsberechnung
        $leistung = 60 + 20 * $v + 10 * $a + 5 * ($Dg - 0.5) + rand(-5, 5);
        $leistung = max(0, min(100, $leistung));

        // Bewertung berechnen
        $bewertung_letter = ($leistung >= 85 ? 'A' : ($leistung >= 70 ? 'B' : ($leistung >= 50 ? 'C' : 'D')));
        $bewertung_numeric = ($bewertung_letter === 'A' ? 4.0 :
                             ($bewertung_letter === 'B' ? 3.0 :
                             ($bewertung_letter === 'C' ? 2.0 : 1.0)));

        // -----------------------------------------------------------
        // 6.a: Zuweisung sicherstellen
        // -----------------------------------------------------------
        $insert_zuweisung->execute([$zw_id_fallback, $tid]);
        $check_existing_zuweisung->execute([$zw_id_fallback, $tid]);
        $ue_zuweisung_id = $check_existing_zuweisung->fetchColumn();

        if (!$ue_zuweisung_id) continue;

        // -----------------------------------------------------------
        // 6.b: Existiert ein Eintrag in mtr_leistung?
        // -----------------------------------------------------------
        $select_leistung->execute([$tid, $g]);
        $leistung_id = $select_leistung->fetchColumn();

        if ($leistung_id) {
            // Immer aktualisieren, auch Bewertung
            $update_leistung->execute([
                ':leistung' => round($leistung, 2),
                ':bewertung' => $bewertung_numeric,
                ':vb' => '',
                ':reflex' => null,
                ':id' => $leistung_id
            ]);
            $update_count++;
        } else {
            // Neu einfügen
            $insert_leistung->execute([
                ':ue_id' => $ue_zuweisung_id,
                ':tid' => $tid,
                ':leistung' => round($leistung, 2),
                ':gid' => $g,
                ':bewertung' => $bewertung_numeric,
                ':vb' => '',
                ':reflex' => null
            ]);
            $insert_count++;
        }
    } catch (PDOException $e) {
        $error_log[] = "DB-Fehler bei teilnehmer $tid: " . $e->getMessage();
    }
}

// ===============================================================
// 7. Abschlussbericht
// ===============================================================
echo "✅ Vorgang abgeschlossen.\n";
echo "Inserts: $insert_count, Updates: $update_count\n";
if (!empty($error_log)) {
    echo "⚠️ Fehler:\n - " . implode("\n - ", $error_log) . "\n";
} else {
    echo "Keine Fehler aufgetreten.\n";
}
?>
