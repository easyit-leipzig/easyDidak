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
('4',
'12',
'50',
'223',
'331',
'378',
'448',
'520',
'680',
'810',
'820',
'844',
'958',
'983',
'1127',
'1583',
'1608',
'1631',
'1690',
'1692',
'1748',
'1762',
'1763',
'1945',
'2032',
'2062',
'2161',
'2185',
'2378',
'2427',
'2442',
'2510',
'2631',
'2744',
'2846',
'2956',
'3133',
'3192',
'3270',
'3337'
)
?>
