<?php
$sql =  "SELECT frzk_semantische_dichte_lehrer.id, 
mtr_rueckkopplung_datenmaske_values_id, value , x_kognition, x_sozial, 
x_affektiv, x_motivation, x_methodik, x_performanz, x_regulation
FROM `frzk_semantische_dichte_lehrer`, mtr_rueckkopplung_datenmaske_values
WHERE value not in (select concat("%", real_value, "%") from frzk_funktionsklassen_weight_absprachen)
 and mtr_rueckkopplung_datenmaske_values_id=mtr_rueckkopplung_datenmaske_values.id
ORDER BY RAND()
LIMIT 40;"

select * from mtr_rueckkopplung_datenmaske_values where value in 
(select concat("%", real_value, "%") from frzk_funktionsklassen_weight_absprachen)

SELECT distinct value, count(id) FROM `mtr_rueckkopplung_datenmaske_values` 
WHERE value in (select real_value from frzk_funktionsklassen_weight_absprachen) 
or value like "%absprachen%";

select `icas`.`frzk_semantische_dichte_lehrer`.`id` AS `id`,`icas`.`frzk_semantische_dichte_lehrer`.`mtr_rueckkopplung_datenmaske_values_id` AS `mtr_rueckkopplung_datenmaske_values_id`,
`icas`.`mtr_rueckkopplung_datenmaske_values`.`value` AS `value`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_kognition` AS `x_kognition`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_sozial` AS `x_sozial`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_affektiv` AS `x_affektiv`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_motivation` AS `x_motivation`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_methodik` AS `x_methodik`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_performanz` AS `x_performanz`,
`icas`.`frzk_semantische_dichte_lehrer`.`x_regulation` AS `x_regulation` 
from (`icas`.`frzk_semantische_dichte_lehrer` join `icas`.`mtr_rueckkopplung_datenmaske_values`) where `icas`.`frzk_semantische_dichte_lehrer`.`mtr_rueckkopplung_datenmaske_values_id` = `icas`.`mtr_rueckkopplung_datenmaske_values`.`id` 
and `icas`.`frzk_semantische_dichte_lehrer`.`mtr_rueckkopplung_datenmaske_values_id` in ('4','12','52','255','370','378','409','559','680','810','820','844','958','983','1127','1583','1608','1631','1690','1692','1748','1762','1763','1945','2032','2062','2161','2185','2378','2427','2442','2510','2631','2744','2846','2956','3133','3192','3270','3337')
?>
