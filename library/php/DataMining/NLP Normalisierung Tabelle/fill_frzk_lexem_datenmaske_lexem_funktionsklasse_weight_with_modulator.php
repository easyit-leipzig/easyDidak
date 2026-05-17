<?php

require_once("../../functions/tokenize.php");
require_once("../../functions/analyseLastArrayField.php");

$pdo = new PDO(
    "mysql:host=localhost;dbname=icas_19_4_2;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("TRUNCATE frzk_lexem_datenmaske_lexem_funktionsklasse_weight");
$pdo->exec("TRUNCATE frzk_lexem_mapping_not_recognized");

$r_vals = $pdo->query("
    SELECT id, id_mtr_rueckkopplung_datenmaske, value
    FROM sql_rueckkopplung_datenmaske_values
    WHERE LENGTH(value) > 40
      AND value NOT IN (
          SELECT real_value
          FROM frzk_funktionsklassen_weight_absprachen
      )
    ORDER BY id
")->fetchAll();

$stmtMap = $pdo->prepare("
    SELECT funktionsklasse_id, wortart
    FROM frzk_lexem_mapping
    WHERE lexem = ?
    LIMIT 1
");

$stmtMod = $pdo->prepare("
    SELECT 
        lm.modulator_id,
        lm.gewicht,
        m.polaritaet,
        m.intensitaet,
        m.ziel_dimension,
        m.operator_typ
    FROM frzk_lexem_modulator lm
    INNER JOIN frzk_modulator m
        ON lm.modulator_id = m.id
    WHERE lm.lexem = ?
      AND m.aktiv = 1
    LIMIT 1
");

$stmtDatenmaske = $pdo->prepare("
    SELECT gruppe_id, teilnehmer_id, fach, datum, lehrkraft_id, thema
    FROM mtr_rueckkopplung_datenmaske
    WHERE thema IS NOT NULL
      AND id = ?
    LIMIT 1
");

$stmtGruppe = $pdo->prepare("
    SELECT uhrzeit_start
    FROM ue_gruppen
    WHERE id = ?
    LIMIT 1
");

$stmtUe = $pdo->prepare("
    SELECT id
    FROM ue_unterrichtseinheit
    WHERE datum = ?
      AND gruppe_id = ?
      AND zeit = ?
    LIMIT 1
");

$stmtWeight = $pdo->prepare("
    SELECT *
    FROM frzk_funktionsklasse_weight
    WHERE funktionsklasse_id = ?
    LIMIT 1
");

$stmtNot = $pdo->prepare("
    INSERT INTO frzk_lexem_mapping_not_recognized (lexem, anz)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE anz = anz + 1
");

$stmtInsert = $pdo->prepare("
    INSERT INTO frzk_lexem_datenmaske_lexem_funktionsklasse_weight
    (
        mtr_rueckkopplung_datenmaske_values_id,
        id_mtr_rueckkopplung_datenmaske,
        funktionsklasse_id,
        lexem,
        wortart,
        modulator_id,
        modulator_polaritaet,
        modulator_intensitaet,
        modulator_ziel_dimension,
        ue_id,
        gruppe_id,
        fach,
        thema,
        unterthema,
        lehrkraft,
        datum_zeit,
        teilnehmer_id,
        kognition,
        sozial,
        affektiv,
        motivation,
        methodik,
        performanz,
        regulation
    )
    VALUES
    (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?
    )
");

foreach ($r_vals as $val) {

    $tokens = tokenize($val["value"]);
    $tokens = checkLastItem($tokens);

    $stmtDatenmaske->execute([$val["id_mtr_rueckkopplung_datenmaske"]]);
    $datenmaske = $stmtDatenmaske->fetch();

    if (!$datenmaske) {
        continue;
    }

    $tmp = explode(":", $datenmaske["thema"], 2);
    $thema = $tmp[0] ?? "";
    $unterthema = $tmp[1] ?? "";

    if ((int)$datenmaske["gruppe_id"] === 0) {
        $uhrzeit_start = "00:00:00";
        $ue_id = 0;
    } else {
        $stmtGruppe->execute([$datenmaske["gruppe_id"]]);
        $gruppe = $stmtGruppe->fetch();

        $uhrzeit_start = $gruppe["uhrzeit_start"] ?? "00:00:00";

        $stmtUe->execute([
            $datenmaske["datum"],
            $datenmaske["gruppe_id"],
            $uhrzeit_start
        ]);

        $ue = $stmtUe->fetch();
        $ue_id = $ue["id"] ?? 0;
    }

    foreach ($tokens as $lexem) {

        $lexem = trim($lexem);

        if ($lexem === "") {
            continue;
        }

        $stmtMap->execute([$lexem]);
        $map = $stmtMap->fetch();

        $stmtMod->execute([$lexem]);
        $mod = $stmtMod->fetch();

        if (!$map && !$mod) {
            $stmtNot->execute([$lexem]);
            continue;
        }

        if ($map) {
            $funktionsklasse_id = (int)$map["funktionsklasse_id"];
            $wortart = $map["wortart"];

            $stmtWeight->execute([$funktionsklasse_id]);
            $weight = $stmtWeight->fetch();

            if (!$weight) {
                $stmtNot->execute([$lexem]);
                continue;
            }

            $kognition  = $weight["kognition"];
            $sozial     = $weight["sozial"];
            $affektiv   = $weight["affektiv"];
            $motivation = $weight["motivation"];
            $methodik   = $weight["methodik"];
            $performanz = $weight["performanz"];
            $regulation = $weight["regulation"];

        } else {
            $funktionsklasse_id = 0;
            $wortart = "modulator";

            $kognition  = 0;
            $sozial     = 0;
            $affektiv   = 0;
            $motivation = 0;
            $methodik   = 0;
            $performanz = 0;
            $regulation = 0;
        }

        $modulator_id = $mod["modulator_id"] ?? null;
        $modulator_polaritaet = $mod["polaritaet"] ?? null;

        $modulator_intensitaet = null;
        if ($mod) {
            $modulator_intensitaet =
                (float)$mod["intensitaet"] * (float)$mod["gewicht"];
        }

        $modulator_ziel_dimension = $mod["ziel_dimension"] ?? null;

        $stmtInsert->execute([
            $val["id"],
            $val["id_mtr_rueckkopplung_datenmaske"],
            $funktionsklasse_id,
            $lexem,
            $wortart,
            $modulator_id,
            $modulator_polaritaet,
            $modulator_intensitaet,
            $modulator_ziel_dimension,
            $ue_id,
            $datenmaske["gruppe_id"],
            $datenmaske["fach"],
            $thema,
            $unterthema,
            $datenmaske["lehrkraft_id"],
            $datenmaske["datum"] . " " . $uhrzeit_start,
            $datenmaske["teilnehmer_id"],
            $kognition,
            $sozial,
            $affektiv,
            $motivation,
            $methodik,
            $performanz,
            $regulation
        ]);
    }
}

echo "FRZK-Lexem-Mapping inkl. Modulatoren abgeschlossen.\n";

?>