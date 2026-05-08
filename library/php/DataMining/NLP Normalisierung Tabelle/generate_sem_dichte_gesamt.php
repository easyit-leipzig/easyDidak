<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$dimensions = [
    'kognition',
    'sozial',
    'affektiv',
    'motivation',
    'methodik',
    'performanz',
    'regulation'
];

$eps = 1e-9;

try {

    $pdo->beginTransaction();

    /*
       Nur type=1 neu erzeugen.
       Andere Reihen / ChatGPT-Auswertungen bleiben erhalten.
    */
    $pdo->exec("
        DELETE FROM frzk_semantische_dichte_lehrer_gesamt
        WHERE type = 1
    ");

    /*
       Gruppierung über die ursprüngliche UE-Struktur.
       Jeder Satz aus frzk_semantische_dichte_lehrer bleibt erhalten
       und geht mit seinem nicht-normalisierten Satz-Endzustand sum_* ein.
    */
    $stmtGroups = $pdo->query("
        SELECT id_mtr_rueckkopplung_datenmaske
        FROM frzk_semantische_dichte_lehrer
        GROUP BY id_mtr_rueckkopplung_datenmaske
        ORDER BY id_mtr_rueckkopplung_datenmaske ASC
    ");

    $groups = $stmtGroups->fetchAll();

    $insert = $pdo->prepare("
        INSERT INTO frzk_semantische_dichte_lehrer_gesamt
        (
            type,
            ue_id,
            id_mtr_rueckkopplung_datenmaske,
            teilnehmer_id,

            x_kognition,
            x_sozial,
            x_affektiv,
            x_motivation,
            x_methodik,
            x_performanz,
            x_regulation,

            sum_kognition,
            sum_sozial,
            sum_affektiv,
            sum_motivation,
            sum_methodik,
            sum_performanz,
            sum_regulation,

            h_kognition,
            h_sozial,
            h_affektiv,
            h_motivation,
            h_methodik,
            h_performanz,
            h_regulation,

            token_anzahl,
            funktionsklassen_anzahl_gesamt,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        )
        VALUES
        (
            1,
            :ue_id,
            :id_mtr,
            :teilnehmer_id,

            :x_kognition,
            :x_sozial,
            :x_affektiv,
            :x_motivation,
            :x_methodik,
            :x_performanz,
            :x_regulation,

            :sum_kognition,
            :sum_sozial,
            :sum_affektiv,
            :sum_motivation,
            :sum_methodik,
            :sum_performanz,
            :sum_regulation,

            :h_kognition,
            :h_sozial,
            :h_affektiv,
            :h_motivation,
            :h_methodik,
            :h_performanz,
            :h_regulation,

            :token_anzahl,
            :funktionsklassen_anzahl_gesamt,
            :dominante_dimension,
            :dominante_dimension_wert,
            :polaritaet_gesamt,
            :d_semantisch
        )
    ");

    $created = 0;

    foreach ($groups as $group) {

        $idMtr = (int)$group['id_mtr_rueckkopplung_datenmaske'];

        $stmtRows = $pdo->prepare("
            SELECT *
            FROM frzk_semantische_dichte_lehrer
            WHERE id_mtr_rueckkopplung_datenmaske = ?
            ORDER BY mtr_rueckkopplung_datenmaske_values_id ASC, id ASC
        ");

        $stmtRows->execute([$idMtr]);
        $rows = $stmtRows->fetchAll();

        if (!$rows) {
            continue;
        }

        $ueId = (int)($rows[0]['ue_id'] ?? 0);
        $teilnehmerId = (int)($rows[0]['teilnehmer_id'] ?? 0);

        $sum = [];
        $h = [];

        foreach ($dimensions as $d) {
            $sum[$d] = 0.0;
            $h[$d] = 0;
        }

        $tokenTotal = 0;

        /*
           WICHTIG:
           sum_* aus frzk_semantische_dichte_lehrer sind bereits die
           nicht-normalisierten Satz-Endzustände.

           Deshalb:
           - NICHT mit token_anzahl multiplizieren
           - NICHT durch tokenTotal teilen
           - NICHT aus x_* aggregieren, außer sum_* fehlt wirklich
        */
        foreach ($rows as $row) {

            $tokenTotal += (int)($row['token_anzahl'] ?? 0);

            foreach ($dimensions as $d) {

                $sumField = 'sum_' . $d;
                $xField   = 'x_' . $d;
                $hField   = 'h_' . $d;

                if (isset($row[$sumField]) && $row[$sumField] !== null && $row[$sumField] !== '') {
                    $v = (float)$row[$sumField];
                } else {
                    $v = (float)($row[$xField] ?? 0);
                }

                $sum[$d] += $v;

                if (isset($row[$hField]) && $row[$hField] !== null && $row[$hField] !== '') {
                    $h[$d] += (int)$row[$hField];
                } else {
                    if (abs($v) > $eps) {
                        $h[$d]++;
                    }
                }
            }
        }

        /*
           Semantische Dichte:
           Norm des aggregierten nicht-normalisierten UE-Zustandsvektors.
        */
        $normSq = 0.0;

        foreach ($dimensions as $d) {
            $normSq += $sum[$d] * $sum[$d];
        }

        $dSemantisch = sqrt($normSq);

        /*
           x_* ist ausschließlich die normierte Richtung des Gesamtvektors.
        */
        $x = [];

        foreach ($dimensions as $d) {
            $x[$d] = ($dSemantisch > $eps)
                ? $sum[$d] / $dSemantisch
                : 0.0;
        }

        /*
           Dominante Dimension aus dem aggregierten sum_*-Gesamtzustand.
        */
        $dominanteDimension = null;
        $dominanteWert = 0.0;

        foreach ($dimensions as $d) {
            if ($dominanteDimension === null || abs($sum[$d]) > abs($dominanteWert)) {
                $dominanteDimension = $d;
                $dominanteWert = $sum[$d];
            }
        }

        /*
           Polarität aus der Gesamtsumme des nicht-normalisierten UE-Zustands.
        */
        $sumAll = array_sum($sum);

        if ($sumAll > $eps) {
            $polaritaet = 1;
        } elseif ($sumAll < -$eps) {
            $polaritaet = -1;
        } else {
            $polaritaet = 0;
        }

        /*
           Anzahl aktiver FRZK-Dimensionen.
        */
        $funktionsklassenGesamt = 0;

        foreach ($dimensions as $d) {
            if ($h[$d] > 0) {
                $funktionsklassenGesamt++;
            }
        }

        $insert->execute([
            ':ue_id' => $ueId,
            ':id_mtr' => $idMtr,
            ':teilnehmer_id' => $teilnehmerId,

            ':x_kognition' => $x['kognition'],
            ':x_sozial' => $x['sozial'],
            ':x_affektiv' => $x['affektiv'],
            ':x_motivation' => $x['motivation'],
            ':x_methodik' => $x['methodik'],
            ':x_performanz' => $x['performanz'],
            ':x_regulation' => $x['regulation'],

            ':sum_kognition' => $sum['kognition'],
            ':sum_sozial' => $sum['sozial'],
            ':sum_affektiv' => $sum['affektiv'],
            ':sum_motivation' => $sum['motivation'],
            ':sum_methodik' => $sum['methodik'],
            ':sum_performanz' => $sum['performanz'],
            ':sum_regulation' => $sum['regulation'],

            ':h_kognition' => $h['kognition'],
            ':h_sozial' => $h['sozial'],
            ':h_affektiv' => $h['affektiv'],
            ':h_motivation' => $h['motivation'],
            ':h_methodik' => $h['methodik'],
            ':h_performanz' => $h['performanz'],
            ':h_regulation' => $h['regulation'],

            ':token_anzahl' => $tokenTotal,
            ':funktionsklassen_anzahl_gesamt' => $funktionsklassenGesamt,
            ':dominante_dimension' => $dominanteDimension,
            ':dominante_dimension_wert' => $dominanteWert,
            ':polaritaet_gesamt' => $polaritaet,
            ':d_semantisch' => $dSemantisch
        ]);

        $created++;
    }

    $pdo->commit();

    echo "FRZK-konforme Aggregation type=1 abgeschlossen.\n";
    echo "Erzeugte Gesamtdatensätze: " . $created . "\n";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}