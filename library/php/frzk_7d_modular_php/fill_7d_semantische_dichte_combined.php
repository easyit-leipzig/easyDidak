<?php
// ============================================================================
// fill_7d_semantische_dichte_combined.php
// Modulare 7D-Neufassung der ursprünglichen 3D-Teilnehmersicht-Aggregation.
// Dieses Hauptskript lädt alle Module per require_once und führt sie in der
// Reihenfolge der ursprünglichen FRZK-Befüllungslogik aus.
// ============================================================================

header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', '1');
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
set_time_limit(0);

require_once __DIR__ . '/config_7d.php';
require_once __DIR__ . '/modules/00_schema_7d.php';
require_once __DIR__ . '/modules/01_fill_semantische_dichte_teilnehmer_7d.php';
require_once __DIR__ . '/modules/02_fill_interdependenz_7d.php';
require_once __DIR__ . '/modules/03_fill_loops_7d.php';
require_once __DIR__ . '/modules/04_fill_operatoren_7d.php';
require_once __DIR__ . '/modules/05_fill_reflexion_7d.php';
require_once __DIR__ . '/modules/06_fill_transitions_7d.php';
require_once __DIR__ . '/modules/07_fill_group_semantische_dichte_7d.php';
require_once __DIR__ . '/modules/08_fill_group_dynamics_7d.php';
require_once __DIR__ . '/modules/09_export_7d.php';

$pdo = frzk_pdo();

echo "Truncate/Erzeuge FRZK-7D-Tabellen...\n";
frzk7d_schema($pdo);
frzk7d_truncate($pdo);

frzk7d_fill_semantische_dichte_teilnehmer($pdo, $DIMENSIONS_7D);
frzk7d_fill_interdependenz($pdo, $DIMENSIONS_7D);
frzk7d_fill_loops($pdo);
frzk7d_fill_operatoren($pdo);
frzk7d_fill_reflexion($pdo);
frzk7d_fill_transitions($pdo, $DIMENSIONS_7D);
frzk7d_fill_group_semantische_dichte($pdo, $DIMENSIONS_7D);
frzk7d_fill_group_dynamics($pdo, $DIMENSIONS_7D);
frzk7d_export_all($pdo);

echo "🏁 Fertig: 7D-Teilnehmersicht inkl. Semantik, Interdependenz, Loops, Operatoren, Reflexion, Transitionen und Gruppendynamik erzeugt.\n";
