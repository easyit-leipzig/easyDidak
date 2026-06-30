<?php
function frzk7d_export_all(PDO $pdo): void {
    foreach(['frzk_semantische_dichte_teilnehmer_7d','frzk_interdependenz_7d','frzk_loops_7d','frzk_operatoren_7d','frzk_reflexion_7d','frzk_transitions_7d','frzk_group_semantische_dichte_7d','frzk_group_transitions_7d','frzk_group_reflexion_7d','frzk_group_loops_7d'] as $t){ exportTable($pdo,$t); }
}
?>
