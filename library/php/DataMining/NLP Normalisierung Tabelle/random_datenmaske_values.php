<?php
$sql =  "SELECT frzk_semantische_dichte_lehrer.id, 
mtr_rueckkopplung_datenmaske_values_id, value , x_kognition, x_sozial, 
x_affektiv, x_motivation, x_methodik, x_performanz, x_regulation
FROM `frzk_semantische_dichte_lehrer`, mtr_rueckkopplung_datenmaske_values
WHERE value not like "%absprachen%"  and mtr_rueckkopplung_datenmaske_values_id=mtr_rueckkopplung_datenmaske_values.id
ORDER BY RAND()
LIMIT 20;"


SELECT frzk_semantische_dichte_lehrer.id, 
mtr_rueckkopplung_datenmaske_values_id, value , x_kognition, x_sozial, 
x_affektiv, x_motivation, x_methodik, x_performanz, x_regulation
FROM `frzk_semantische_dichte_lehrer`, mtr_rueckkopplung_datenmaske_values
WHERE mtr_rueckkopplung_datenmaske_values_id=mtr_rueckkopplung_datenmaske_values.id
and frzk_semantische_dichte_lehrer.mtr_rueckkopplung_datenmaske_values_id in 
('2161',
'12',
'680',
'50',
'520',
'1762',
'958',
'223',
'3270',
'1631',
'2378',
'1583',
'810',
'3133',
'3337',
'378',
'4',
'2062',
'2427',
'983',
'331',
'2956',
'2631',
'1748',
'3192',
'2185',
'1692',
'1608',
'1690',
'1945',
'2744',
'2442',
'1127',
'2032',
'2510',
'448',
'820',
'844',
'2846',
'1763'
)
?>
