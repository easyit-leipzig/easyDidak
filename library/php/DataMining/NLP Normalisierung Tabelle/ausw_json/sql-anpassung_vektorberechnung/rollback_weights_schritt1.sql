START TRANSACTION;

UPDATE frzk_funktionsklasse_weight SET kognition = 0.800, sozial = 0.000, affektiv = 0.200, motivation = 0.000, methodik = 0.400, performanz = 0.200, regulation = 0.000 WHERE funktionsklasse_id = 200;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.900, sozial = 0.000, affektiv = 0.000, motivation = 0.400, methodik = 0.500, performanz = 0.600, regulation = 0.600 WHERE funktionsklasse_id = 4;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.800, sozial = 0.000, affektiv = 0.000, motivation = 0.400, methodik = 0.400, performanz = 0.600, regulation = 0.600 WHERE funktionsklasse_id = 12;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.700, sozial = 0.000, affektiv = 0.000, motivation = 0.500, methodik = 0.600, performanz = 0.700, regulation = 0.600 WHERE funktionsklasse_id = 13;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.600, sozial = 0.000, affektiv = 0.000, motivation = 0.300, methodik = 0.300, performanz = 0.500, regulation = 0.400 WHERE funktionsklasse_id = 11;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.600, sozial = 0.000, affektiv = 0.000, motivation = 0.500, methodik = 0.900, performanz = 0.600, regulation = 0.700 WHERE funktionsklasse_id = 7;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.900, sozial = 0.000, affektiv = 0.000, motivation = 0.500, methodik = 0.600, performanz = 0.600, regulation = 0.700 WHERE funktionsklasse_id = 14;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.500, sozial = 0.750, affektiv = 0.000, motivation = 0.700, methodik = 0.600, performanz = 0.500, regulation = 0.600 WHERE funktionsklasse_id = 18;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.000, sozial = 0.400, affektiv = 0.850, motivation = 0.900, methodik = 0.000, performanz = 0.400, regulation = 0.200 WHERE funktionsklasse_id = 202;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.200, sozial = 0.200, affektiv = 0.400, motivation = 0.300, methodik = 0.300, performanz = 0.300, regulation = 0.900 WHERE funktionsklasse_id = 204;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.600, sozial = 0.000, affektiv = 0.000, motivation = 0.400, methodik = 0.400, performanz = 0.900, regulation = 0.600 WHERE funktionsklasse_id = 1;
UPDATE frzk_funktionsklasse_weight SET kognition = 0.300, sozial = 0.000, affektiv = 0.300, motivation = 0.300, methodik = 0.200, performanz = 0.900, regulation = 0.200 WHERE funktionsklasse_id = 206;
UPDATE frzk_funktionsklasse_weight SET kognition = -0.200, sozial = -0.200, affektiv = -0.400, motivation = -0.300, methodik = -0.300, performanz = -0.300, regulation = -0.900 WHERE funktionsklasse_id = 205;
UPDATE frzk_funktionsklasse_weight SET kognition = -0.300, sozial = 0.000, affektiv = -0.300, motivation = -0.300, methodik = -0.200, performanz = -0.900, regulation = -0.200 WHERE funktionsklasse_id = 208;

COMMIT;
